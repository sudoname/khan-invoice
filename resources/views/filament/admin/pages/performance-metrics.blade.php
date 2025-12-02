<x-filament-panels::page>
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @endpush

    <div class="space-y-6">
        <!-- Refresh Button -->
        <div class="flex justify-end">
            <x-filament::button wire:click="refreshMetrics" color="primary">
                Refresh Metrics
            </x-filament::button>
        </div>

        <!-- Performance Charts (24 Hours) -->
        @if(count($metrics['charts']['labels']) > 0)
        <x-filament::section>
            <x-slot name="heading">
                Performance Trends (Last 24 Hours)
            </x-slot>

            <x-slot name="description">
                Historical performance data showing query time and cache performance over time
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Query Time Chart -->
                <div class="bg-white p-3 rounded-lg border border-gray-200">
                    <h4 class="text-xs font-semibold text-gray-700 mb-2">Database Query Time</h4>
                    <div style="height: 200px;">
                        <canvas id="queryTimeChart"></canvas>
                    </div>
                </div>

                <!-- Cache Write/Read Time Chart -->
                <div class="bg-white p-3 rounded-lg border border-gray-200">
                    <h4 class="text-xs font-semibold text-gray-700 mb-2">Cache Write/Read Time</h4>
                    <div style="height: 200px;">
                        <canvas id="cacheTimeChart"></canvas>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const rawLabels = @json($metrics['charts']['labels']);
                const currentTime = new Date();

                // Convert labels to show relative time (e.g., "2h ago", "1h ago", "now")
                const labels = rawLabels.map((label, index) => {
                    if (rawLabels.length - 1 === index) {
                        return 'Now';
                    }
                    const hoursAgo = rawLabels.length - 1 - index;
                    return hoursAgo === 1 ? '1h ago' : hoursAgo + 'h ago';
                });

                // Query Time Chart
                const queryCtx = document.getElementById('queryTimeChart').getContext('2d');
                new Chart(queryCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Query Time (ms)',
                            data: @json($metrics['charts']['query_times']),
                            borderColor: 'rgb(147, 51, 234)',
                            backgroundColor: 'rgba(147, 51, 234, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Time (ms)'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Time'
                                }
                            }
                        }
                    }
                });

                // Cache Time Chart
                const cacheCtx = document.getElementById('cacheTimeChart').getContext('2d');
                new Chart(cacheCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Cache Write/Read Time (ms)',
                            data: @json($metrics['charts']['cache_times']),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Time (ms)'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Time'
                                }
                            }
                        }
                    }
                });
            });
        </script>
        @else
        <x-filament::section>
            <x-slot name="heading">
                Performance Trends (Last 24 Hours)
            </x-slot>

            <div class="p-8 text-center">
                <p class="text-gray-600 mb-4">No performance data available yet. Performance metrics are logged hourly.</p>
                <p class="text-sm text-gray-500">Run <code class="bg-gray-100 px-2 py-1 rounded">php artisan performance:log</code> to manually log metrics now.</p>
            </div>
        </x-filament::section>
        @endif

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
                        @php
                            $label = match($table) {
                                'invoices' => 'Private Invoices',
                                'public_invoices' => 'Public Invoices',
                                default => ucwords(str_replace('_', ' ', $table))
                            };
                        @endphp
                        <div class="bg-white border border-gray-200 rounded-lg p-3">
                            <p class="text-xs text-gray-500">{{ $label }}</p>
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
