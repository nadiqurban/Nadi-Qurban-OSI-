<?php

namespace App\Support;

/**
 * Minimal user-agent parser for login history ("Chrome · Windows 11", "Safari · iPhone").
 */
final class UserAgent
{
    public function __construct(private readonly string $ua) {}

    public static function parse(?string $ua): self
    {
        return new self((string) $ua);
    }

    public function browser(): ?string
    {
        return match (true) {
            str_contains($this->ua, 'Edg/') => 'Edge',
            str_contains($this->ua, 'OPR/') || str_contains($this->ua, 'Opera') => 'Opera',
            str_contains($this->ua, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($this->ua, 'Firefox/') || str_contains($this->ua, 'FxiOS') => 'Firefox',
            str_contains($this->ua, 'Chrome/') || str_contains($this->ua, 'CriOS') => 'Chrome',
            str_contains($this->ua, 'Safari/') => 'Safari',
            default => null,
        };
    }

    public function platform(): ?string
    {
        return match (true) {
            str_contains($this->ua, 'iPhone') => 'iPhone',
            str_contains($this->ua, 'iPad') => 'iPad',
            str_contains($this->ua, 'Android') => 'Android',
            str_contains($this->ua, 'Windows NT 10') => 'Windows 11',
            str_contains($this->ua, 'Windows') => 'Windows',
            str_contains($this->ua, 'Mac OS X') || str_contains($this->ua, 'Macintosh') => 'macOS',
            str_contains($this->ua, 'CrOS') => 'ChromeOS',
            str_contains($this->ua, 'Linux') => 'Linux',
            default => null,
        };
    }

    /** desktop | mobile | tablet */
    public function device(): string
    {
        return match (true) {
            str_contains($this->ua, 'iPad') || str_contains($this->ua, 'Tablet') => 'tablet',
            str_contains($this->ua, 'Mobile') || str_contains($this->ua, 'iPhone') || str_contains($this->ua, 'Android') => 'mobile',
            default => 'desktop',
        };
    }
}
