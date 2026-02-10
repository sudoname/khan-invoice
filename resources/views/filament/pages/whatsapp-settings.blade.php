<x-filament-panels::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-6 flex gap-3">
            {{ $this->getFormActions() }}
        </div>
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
