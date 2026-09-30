<x-layouts::app :title="$title">
    <x-ui.page-header :title="$title" :breadcrumb="$breadcrumb" />

    <x-ui.card padding="p-0">
        <x-ui.empty-state :icon="$icon" title="Skrin ini sedang dibina">
            Modul <b class="text-ink-2">{{ $title }}</b> akan dilaksanakan dalam Fasa {{ $phase }}.
        </x-ui.empty-state>
    </x-ui.card>
</x-layouts::app>
