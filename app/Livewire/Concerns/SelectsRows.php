<?php

namespace App\Livewire\Concerns;

/**
 * Row checkboxes + header "pilih semua" for list screens. The component lists the
 * ids that the header checkbox toggles via selectableIds().
 */
trait SelectsRows
{
    /** @var list<int> */
    public array $selected = [];

    /** @return list<int> */
    abstract protected function selectableIds(): array;

    public function toggleAll(): void
    {
        $ids = $this->selectableIds();
        $selected = array_map('intval', $this->selected);

        $this->selected = $ids !== [] && array_diff($ids, $selected) === []
            ? array_values(array_diff($selected, $ids))
            : array_values(array_unique([...$selected, ...$ids]));
    }

    public function allSelected(): bool
    {
        $ids = $this->selectableIds();

        return $ids !== [] && array_diff($ids, array_map('intval', $this->selected)) === [];
    }
}
