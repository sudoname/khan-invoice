<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Refresh Button -->
        <div class="flex justify-end">
            <x-filament::button wire:click="refreshMetrics" color="primary">
                Refresh Metrics
            </x-filament::button>
        </div>

        <!-- Database Metrics -->
        <x-filament::section>
            <x-slot name="heading">
                Database Performance
            </x-slot>

            <x-slot name="description">
                Database connection and query performance metrics
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Connection</h4>
                    <p class="text-lg font-bold text-gray-900">{{ $metrics['database']['connection'] }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Query Time</h4>
                    <p class="text-lg font-bold text-gray-900">{{ $metrics['database']['query_time'] }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Total Tables</h4>
                    <p class="text-lg font-bold text-gray-900">{{ count($metrics['database']['table_counts']) }}</p>
                </div>
            </div>

            <div class="mt-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Table Record Counts</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach($metrics['database']['table_counts'] as $table => $count)
                        <div class="bg-white border border-gray-200 rounded-lg p-3">
                            <p class="text-xs text-gray-500">{{ ucfirst($table) }}</p>
                            <p class="text-xl font-bold text-purple-600">{{ is_numeric($count) ? number_format($count) : $count }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-filament::section>

        <!-- Cache Metrics -->
        <x-filament::section>
            <x-slot name="heading">
                Cache Performance
            </x-slot>

            <x-slot name="description">
                Cache driver and performance metrics
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Cache Driver</h4>
                    <p class="text-lg font-bold text-gray-900">{{ $metrics['cache']['driver'] }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Write/Read Time</h4>
                    <p class="text-lg font-bold text-gray-900">{{ $metrics['cache']['write_read_time'] }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Status</h4>
                    <p class="text-lg font-bold @if($metrics['cache']['status'] === 'Working') text-green-600 @else text-red-600 @endif">
                        {{ $metrics['cache']['status'] }}
                    </p>
                </div>
            </div>
        </x-filament::section>

        <!-- Application Metrics -->
        <x-filament::section>
            <x-slot name="heading">
                Application Information
            </x-slot>

            <x-slot name="description">
                System and application configuration details
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($metrics['application'] as $key => $value)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-gray-600 mb-2">{{ ucwords(str_replace('_', ' ', $key)) }}</h4>
                        <p class="text-lg font-bold text-gray-900">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <!-- System Health Indicators -->
        <x-filament::section>
            <x-slot name="heading">
                Health Indicators
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-green-50 border-l-4 border-green-500 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-green-800 mb-1">Database</h4>
                    <p class="text-xs text-green-600">Connected and responsive</p>
                </div>
                <div class="@if($metrics['cache']['status'] === 'Working') bg-green-50 border-green-500 @else bg-red-50 border-red-500 @endif border-l-4 rounded-lg p-4">
                    <h4 class="text-sm font-semibold @if($metrics['cache']['status'] === 'Working') text-green-800 @else text-red-800 @endif mb-1">Cache</h4>
                    <p class="text-xs @if($metrics['cache']['status'] === 'Working') text-green-600 @else text-red-600 @endif">{{ $metrics['cache']['status'] }}</p>
                </div>
                <div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-blue-800 mb-1">Application</h4>
                    <p class="text-xs text-blue-600">Running Laravel {{ $metrics['application']['laravel_version'] }}</p>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
