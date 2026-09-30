<?php

namespace App\Support;

use App\Enums\Animal;
use App\Models\Certificate;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Editor Sijil template (settings "certificate.*") + the values printed on each
 * A5 certificate. Fixed texts come from the template; per-certificate values
 * (name, ibadah, location, date, numbers) come from the order.
 */
final class CertificateTemplate
{
    public const DEFAULTS = [
        'title' => 'SIJIL DIGITAL',
        'intro' => 'Dengan ini kami mengesahkan bahawa',
        'close' => 'telah menyertai',
        'jazak' => 'Jazakallah Khair',
        'qr_url' => 'https://www.nadiqurban.com',
        'color' => '#42481c',
    ];

    /** Sample values for the editor preview (not stored). */
    public const SAMPLE = [
        'name' => 'NAMA PESERTA',
        'service' => 'QURBAN',
        'detail' => 'LEMBU BAHAGIAN (1)',
        'country' => 'WILAYAH UGANDA, AFRIKA',
        'date' => '25 MEI 2026',
        'certificate_no' => 'NQ-SIJIL-2027-0248',
        'tracking_no' => 'NQ-QB-LE-001248',
    ];

    public const BACKGROUND_KEY = 'certificate.background';

    public function __construct(private readonly Settings $settings) {}

    /** @return array<string, string> */
    public function template(): array
    {
        $values = [];

        foreach (self::DEFAULTS as $key => $default) {
            $values[$key] = (string) $this->settings->get('certificate.'.$key, $default);
        }

        return $values;
    }

    /** @param  array<string, string>  $values */
    public function save(array $values): void
    {
        $this->settings->setMany(collect($values)->only(array_keys(self::DEFAULTS))
            ->mapWithKeys(fn ($v, $k) => ['certificate.'.$k => $v])->all());
    }

    public function reset(): void
    {
        $this->settings->setMany(collect(self::DEFAULTS)->mapWithKeys(fn ($v, $k) => ['certificate.'.$k => $v])->all());
        $this->setBackground(null);
    }

    public function backgroundPath(): ?string
    {
        $path = $this->settings->get(self::BACKGROUND_KEY);

        return is_string($path) && $path !== '' && Storage::disk('local')->exists($path) ? $path : null;
    }

    public function setBackground(?string $path): void
    {
        $old = $this->backgroundPath();

        if ($old && $old !== $path) {
            Storage::disk('local')->delete($old);
        }

        $this->settings->set(self::BACKGROUND_KEY, $path);
    }

    /** Background as a data URI (private disk → inline for preview + PDF). */
    public function backgroundDataUri(): ?string
    {
        $path = $this->backgroundPath();

        if (! $path) {
            return null;
        }

        $disk = Storage::disk('local');

        return 'data:'.($disk->mimeType($path) ?: 'image/png').';base64,'.base64_encode((string) $disk->get($path));
    }

    /**
     * Everything the certificate partial needs.
     *
     * @param  array<string, string>  $values  sample/per-certificate values (+ optional template overrides)
     * @return array<string, string|null>
     */
    public function render(array $values): array
    {
        return array_merge($this->template(), self::SAMPLE, $values, ['background' => $this->backgroundDataUri()]);
    }

    /** @return array<string, string> */
    public static function valuesFor(Certificate $certificate): array
    {
        $order = $certificate->order;
        $animal = mb_strtoupper($order->animal->label());

        return [
            'name' => $certificate->recipient_name,
            'service' => mb_strtoupper($order->service->label()),
            'detail' => $order->animal === Animal::Goat ? $animal.' (1 EKOR)' : $animal.' BAHAGIAN ('.$certificate->position.')',
            'country' => mb_strtoupper($order->country->name),
            'date' => mb_strtoupper(tarikh($order->implementation_date ?? $certificate->generated_at)),
            'certificate_no' => $certificate->certificate_no,
            'tracking_no' => $order->order_no,
        ];
    }

    /**
     * A5 PDF download, one page per certificate.
     *
     * @param  list<array<string, string|null>>  $pages  render() output
     */
    public function download(array $pages, string $filename): StreamedResponse
    {
        $pdf = Pdf::view('pdf.certificates', ['certificates' => $pages])->format('a5');

        return response()->streamDownload(function () use ($pdf) {
            echo base64_decode($pdf->base64());
        }, $filename, ['Content-Type' => 'application/pdf']);
    }
}
