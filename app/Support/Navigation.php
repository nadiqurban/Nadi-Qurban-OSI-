<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Sidebar menu from config/navigation.php, filtered by "{module}.view".
 * Sections with no visible items are dropped (PRD §4: hidden when "Tiada").
 */
class Navigation
{
    /**
     * @return Collection<int, array{section: string, items: Collection<int, array<string, mixed>>}>
     */
    public function for(?User $user): Collection
    {
        $groups = new Collection;
        $menu = config('navigation');

        foreach (is_array($menu) ? $menu : [] as $group) {
            if (! is_array($group) || ! is_array($group['items'] ?? null)) {
                continue;
            }

            /** @var Collection<int, array<string, mixed>> $items */
            $items = new Collection;

            foreach ($group['items'] as $item) {
                if (is_array($item) && is_string($item['route'] ?? null) && $this->visible($item, $user)) {
                    $items->push($item + [
                        'href' => Route::has($item['route']) ? route($item['route']) : '#',
                        'is_active' => request()->routeIs(...(array) ($item['active'] ?? $item['route'])),
                        'badge_count' => $this->badge($item),
                    ]);
                }
            }

            if ($items->isNotEmpty()) {
                $groups->push(['section' => (string) ($group['section'] ?? ''), 'items' => $items]);
            }
        }

        return $groups;
    }

    /** @param  array<mixed>  $item */
    private function visible(array $item, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        // Personal settings (profile, security) are open to every user.
        if (($item['always'] ?? false) === true) {
            return true;
        }

        return is_string($item['module'] ?? null) && $user->can($item['module'].'.view');
    }

    /** @param  array<mixed>  $item */
    private function badge(array $item): ?int
    {
        $class = $item['badge'] ?? null;

        if (! is_string($class) || ! class_exists($class)) {
            return null;
        }

        $count = app($class)();

        return is_int($count) && $count > 0 ? $count : null;
    }
}
