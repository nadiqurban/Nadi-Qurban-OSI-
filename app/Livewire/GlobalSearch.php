<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Livewire\Component;

/**
 * ⌘K / Ctrl+K command palette (header search): orders (no., tracking, name,
 * phone), customers, vendors, invoices and leads — each group only when the
 * user may view that module. Keyboard navigation lives in Alpine.
 */
class GlobalSearch extends Component
{
    public string $q = '';

    /**
     * @return list<array{group: string, icon: string, title: string, subtitle: string, url: string}>
     */
    public function results(): array
    {
        $term = trim($this->q);

        if (mb_strlen($term) < 2) {
            return [];
        }

        /** @var User $user */
        $user = auth()->user();
        $like = '%'.addcslashes($term, '%_\\').'%';
        $rows = [];

        if ($user->can('orders.view') || $user->isVendorPic()) {
            Order::query()->visibleTo($user)->search($term)->with(['customer', 'country'])->latest()->limit(5)->get()
                ->each(function (Order $o) use (&$rows) {
                    $rows[] = ['group' => 'Tempahan', 'icon' => 'shopping-cart-simple', 'title' => $o->order_no.' · '.$o->customer->name,
                        'subtitle' => $o->ibadahLabel().' · '.$o->country->name.' · '.$o->status->label(), 'url' => route('orders.show', $o)];
                });
        }

        if ($user->can('orders.view')) {
            $digits = preg_replace('/\D+/', '', $term) ?? '';
            Customer::query()->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('code', 'like', $like)
                ->when(strlen($digits) >= 4, fn ($w) => $w->orWhere('phone', 'like', '%'.$digits.'%')))
                ->limit(4)->get()->each(function (Customer $c) use (&$rows) {
                    $rows[] = ['group' => 'Pelanggan', 'icon' => 'user', 'title' => $c->name, 'subtitle' => $c->code.' · '.$c->phone, 'url' => route('orders.index', ['q' => $c->phone])];
                });
        }

        if ($user->can('vendors.view') && ! $user->isVendorPic()) {
            Vendor::query()->with('country')->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('code', 'like', $like)->orWhere('company', 'like', $like))
                ->limit(4)->get()->each(function (Vendor $v) use (&$rows) {
                    $rows[] = ['group' => 'Vendor', 'icon' => 'truck', 'title' => $v->name, 'subtitle' => $v->code.' · '.$v->country->name, 'url' => route('vendors.show', $v)];
                });
        }

        if ($user->can('finance.view')) {
            Invoice::query()->where(fn ($q) => $q->where('invoice_no', 'like', $like)->orWhere('customer_name', 'like', $like)->orWhere('order_no', 'like', $like))
                ->latest()->limit(4)->get()->each(function (Invoice $i) use (&$rows) {
                    $rows[] = ['group' => 'Invois', 'icon' => 'receipt', 'title' => $i->invoice_no.' · '.$i->customer_name, 'subtitle' => rm($i->total_sen).' · '.$i->status->label(), 'url' => route('finance.invoice', $i)];
                });
        }

        if ($user->can('crm.view')) {
            Lead::query()->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('lead_no', 'like', $like)->orWhere('company', 'like', $like)->orWhere('phone', 'like', $like))
                ->latest()->limit(4)->get()->each(function (Lead $l) use (&$rows) {
                    $rows[] = ['group' => 'Lead', 'icon' => 'users-three', 'title' => $l->name, 'subtitle' => $l->lead_no.' · '.$l->stage->label().' · '.rm($l->value_sen), 'url' => route('crm.show', $l)];
                });
        }

        return $rows;
    }

    public function render(): mixed
    {
        return view('livewire.global-search', ['results' => $this->results()]);
    }
}
