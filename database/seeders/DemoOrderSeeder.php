<?php

namespace Database\Seeders;

use App\Enums\OrderStage;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderStageHistory;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Local/demo only: the orders from Tempahan & Pelanggan.dc.html (NQ-…-001240 → 001248)
 * plus three "Diterima" orders waiting in Pengesahan Bayaran. Prices come from the
 * product catalogue (PRD §12.4), so they differ from the prototype's hard-coded ones.
 */
class DemoOrderSeeder extends Seeder
{
    private const EXTRA_NAMES = [
        'Ahmad bin Ismail', 'Siti Fatimah binti Ali', 'Mohd Hafiz bin Razak', 'Nur Aisyah binti Kamal',
        'Zainal bin Abidin', 'Rohana binti Yusof', 'Faizal bin Sulaiman', 'Halimah binti Daud',
    ];

    public function run(): void
    {
        if (Order::query()->exists()) {
            return;
        }

        $rows = $this->rows();

        $channels = ['FPX Maybank', 'FPX CIMB', 'DuitNow QR', 'FPX Bank Islam', 'Kad Kredit'];
        $products = Product::query()->with('package')->get()->keyBy('name');

        DB::transaction(function () use ($rows, $channels, $products) {
            foreach (array_reverse($rows) as $i => [$seq, $name, $phone, $productName, $qty, $status, $method, $fpx, $address, $postcode, $city, $state]) {
                /** @var Product $product */
                $product = $products[$productName];
                $created = now()->subHours(3 + $i * 5);

                $customer = Customer::query()->create(compact('name', 'phone', 'address', 'postcode', 'city', 'state') + [
                    'email' => Str::of($name)->before(' ')->lower()->append('@email.com')->toString(),
                ]);

                $stage = match ($status) {
                    OrderStatus::Completed => OrderStage::Completed,
                    OrderStatus::InProgress => OrderStage::Executing,
                    default => OrderStage::Received,
                };

                $order = new Order([
                    'order_no' => sprintf('NQ-%s-%s-%06d', $product->service->code(), $product->animal->code(), $seq),
                    'tracking_no' => sprintf('NQT-2027-%06d', $seq),
                    'tracking_token' => Str::random(40),
                    'customer_id' => $customer->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'service' => $product->service,
                    'animal' => $product->animal,
                    'package_name' => $product->package->name,
                    'country_id' => $product->country_id,
                    'quantity' => $qty,
                    'year' => 2027,
                    'implementation_date' => '2027-06-18',
                    'unit_price_sen' => $product->price_sen,
                    'subtotal_sen' => $product->price_sen * $qty,
                    'discount_sen' => 0,
                    'total_sen' => $product->price_sen * $qty,
                    'payment_method' => $method,
                    'status' => $status,
                    'stage' => $stage,
                    'accepted_at' => $status === OrderStatus::Accepted ? $created->copy()->addHour() : null,
                ]);
                $order->created_at = $created;
                $order->updated_at = $created;
                $order->save();

                foreach (range(1, $qty) as $pos) {
                    $order->participants()->create([
                        'position' => $pos,
                        'name' => $pos === 1 ? $name : self::EXTRA_NAMES[($pos - 2) % count(self::EXTRA_NAMES)],
                    ]);
                }

                $verified = in_array($status, [OrderStatus::InProgress, OrderStatus::Completed], true);
                $payment = Payment::query()->create([
                    'order_id' => $order->id,
                    'method' => $method,
                    'channel' => $method === PaymentMethod::Fpx ? $channels[$i % count($channels)] : $method->label(),
                    'gateway_status' => $fpx,
                    'amount_sen' => $order->total_sen,
                    'paid_at' => $status === OrderStatus::Draft || $status === OrderStatus::AwaitingPayment ? null : $created->copy()->addMinutes(30),
                    'status' => $verified ? PaymentStatus::Verified : PaymentStatus::Pending,
                    'verified_at' => $verified ? $created->copy()->addHours(4) : null,
                ]);

                if (in_array($seq, [1249, 1250, 1248, 1245], true)) {
                    $payment->addMediaFromString($this->receiptPng($order->order_no, $name, rm($order->total_sen)))
                        ->usingFileName('resit-'.$order->order_no.'.png')
                        ->toMediaCollection('proof');
                }

                foreach (OrderStage::cases() as $n => $s) {
                    if ($s->position() > $stage->position()) {
                        break;
                    }

                    OrderStageHistory::query()->create([
                        'order_id' => $order->id,
                        'stage' => $s,
                        'created_at' => $created->copy()->addHours($n * 20),
                    ]);
                }
            }

            DB::table('sequences')->updateOrInsert(['name' => 'order'], ['next_value' => 1252, 'created_at' => now(), 'updated_at' => now()]);
        });
    }

    /**
     * [seq, name, phone, product, qty, status, method, fpx result, address, postcode, city, state]
     *
     * @return list<array{int, string, string, string, int, OrderStatus, PaymentMethod, ?string, string, string, string, string}>
     */
    private function rows(): array
    {
        return [
            [1240, 'Ibrahim bin Musa', '018-4455661', 'Qurban Kambing Nigeria', 1, OrderStatus::InProgress, PaymentMethod::BankTransfer, null, 'No. 8, Jalan Kenanga 2', '43000', 'Kajang', 'Selangor'],
            [1241, 'Aminah binti Salleh', '016-1122334', 'Qurban Lembu Uganda', 1, OrderStatus::Completed, PaymentMethod::Cheque, null, 'No. 21, Jalan Mawar 5', '81100', 'Johor Bahru', 'Johor'],
            [1242, 'Zulkifli bin Aziz', '014-5566778', 'Aqiqah Kambing Malaysia', 2, OrderStatus::Draft, PaymentMethod::Fpx, 'berjaya', 'No. 3, Lorong Seroja', '11900', 'Bayan Lepas', 'Pulau Pinang'],
            [1243, 'Rosmah binti Idris', '012-8877665', 'Qurban Lembu Uganda', 7, OrderStatus::Cancelled, PaymentMethod::BankTransfer, null, 'No. 45, Jalan Teratai 1', '30450', 'Ipoh', 'Perak'],
            [1244, 'Zulhilmi bin Abdullah', '011-22334455', 'Nazar Kambing Chad', 2, OrderStatus::InProgress, PaymentMethod::Cheque, null, 'No. 17, Jalan Cempaka', '15050', 'Kota Bharu', 'Kelantan'],
            [1245, 'Siti Khadijah bt Yusof', '017-6543210', 'Dam Kambing Makkah', 1, OrderStatus::Completed, PaymentMethod::Fpx, 'berjaya', 'No. 9, Jalan Dahlia 4', '50480', 'Kuala Lumpur', 'Kuala Lumpur'],
            [1246, 'Mohd Firdaus bin Omar', '019-2345671', 'Qurban Unta Somalia', 1, OrderStatus::AwaitingPayment, PaymentMethod::BankTransfer, null, 'No. 66, Jalan Anggerik', '25200', 'Kuantan', 'Pahang'],
            [1247, 'Nurul Aina binti Rahim', '013-9988776', 'Aqiqah Kambing Malaysia', 1, OrderStatus::Completed, PaymentMethod::Cheque, null, 'No. 5, Jalan Bunga Raya', '75450', 'Melaka', 'Melaka'],
            [1248, 'Ahmad Zaki bin Hassan', '012-3456789', 'Qurban Lembu Uganda', 1, OrderStatus::InProgress, PaymentMethod::Fpx, 'gagal', 'No. 12, Jalan Melati 3, Taman Sri Indah', '40150', 'Shah Alam', 'Selangor'],
            [1249, 'Farid bin Kassim', '012-7788990', 'Qurban Lembu Uganda', 7, OrderStatus::Accepted, PaymentMethod::Fpx, 'berjaya', 'No. 30, Jalan Tulip 7', '47301', 'Petaling Jaya', 'Selangor'],
            [1250, 'Nor Azlina binti Hamid', '013-2233445', 'Aqiqah Kambing Malaysia', 2, OrderStatus::Accepted, PaymentMethod::BankTransfer, null, 'No. 14, Jalan Kemboja', '70200', 'Seremban', 'Negeri Sembilan'],
            [1251, 'Hafiz bin Rahman', '017-8899001', 'Dam Kambing Makkah', 1, OrderStatus::Accepted, PaymentMethod::Cheque, null, 'No. 2, Jalan Melur', '05100', 'Alor Setar', 'Kedah'],
        ];
    }

    /** A simple bank-receipt style PNG for the proof viewer (GD). */
    private function receiptPng(string $orderNo, string $name, string $amount): string
    {
        $img = imagecreatetruecolor(600, 780);
        $white = (int) imagecolorallocate($img, 255, 255, 255);
        $olive = (int) imagecolorallocate($img, 66, 72, 28);
        $grey = (int) imagecolorallocate($img, 100, 116, 139);
        $green = (int) imagecolorallocate($img, 22, 163, 74);
        imagefilledrectangle($img, 0, 0, 600, 780, $white);
        imagefilledrectangle($img, 0, 0, 600, 90, $olive);
        imagestring($img, 5, 30, 35, 'RESIT PEMBAYARAN - FPX', $white);
        $lines = [['Status', 'BERJAYA'], ['No. Rujukan', 'FPX'.substr(md5($orderNo), 0, 10)], ['Penerima', 'NADI QURBAN SDN BHD'], ['Pembayar', mb_strtoupper($name)], ['Rujukan', $orderNo], ['Jumlah', $amount], ['Tarikh', now()->format('d/m/Y H:i')]];
        foreach ($lines as $n => [$k, $v]) {
            imagestring($img, 4, 30, 140 + $n * 70, $k, $grey);
            imagestring($img, 5, 30, 162 + $n * 70, $v, $n === 0 ? $green : $olive);
        }
        ob_start();
        imagepng($img);

        return (string) ob_get_clean();
    }
}
