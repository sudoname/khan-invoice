<x-filament-panels::page>
    <form wire:submit="create">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button
                type="submit"
                size="lg"
                icon="heroicon-m-sparkles"
            >
                Generate Design
            </x-filament::button>
        </div>
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
