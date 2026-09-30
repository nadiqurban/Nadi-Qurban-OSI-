<?php

namespace App\Support;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Inline assets for server-rendered PDFs (Browsershot renders an HTML string, so
 * fonts and images are embedded as data URIs).
 */
final class PdfAssets
{
    /** @font-face rules for Inter 400–800 (+ Amiri for Arabic/Jawi). */
    public static function fontFaces(): string
    {
        static $css = null;

        if ($css !== null) {
            return $css;
        }

        $css = '';

        foreach ([400, 500, 600, 700, 800] as $weight) {
            $css .= self::face('Inter', $weight, "inter-latin-{$weight}-normal.woff2");
        }

        foreach ([400, 700] as $weight) {
            $css .= self::face('Amiri', $weight, "amiri-arabic-{$weight}-normal.woff2");
        }

        return $css;
    }

    public static function image(string $publicPath): string
    {
        $path = public_path($publicPath);
        $mime = str_ends_with($path, '.webp') ? 'image/webp' : 'image/png';

        return is_file($path) ? 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path)) : '';
    }

    /** QR code as inline SVG markup. */
    public static function qr(string $text, int $size = 134): string
    {
        return (string) QrCode::format('svg')->size($size)->margin(0)->errorCorrection('M')->generate($text);
    }

    private static function face(string $family, int $weight, string $file): string
    {
        $path = resource_path('fonts/'.$file);

        if (! is_file($path)) {
            return '';
        }

        $data = base64_encode((string) file_get_contents($path));

        return "@font-face{font-family:'{$family}';font-weight:{$weight};font-style:normal;src:url(data:font/woff2;base64,{$data}) format('woff2');}";
    }
}
