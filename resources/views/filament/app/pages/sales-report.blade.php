<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filter Form --}}
        <x-filament-panels::form wire:submit="refreshReport">
            {{ $this->form }}

            <x-filament::button type="submit" class="mt-4">
                Generate Report
            </x-filament::button>
        </x-filament-panels::form>

        @if(!empty($reportData))
            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Billed</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                        ₦{{ number_format($reportData['sales']['total_billed'], 2) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                        {{ $reportData['sales']['invoice_count'] }} invoices
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Collected</div>
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
                        ₦{{ number_format($reportData['collections']['total_collected'], 2) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                        {{ $reportData['collections']['payment_count'] }} payments
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Average Invoice</div>
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2">
                        ₦{{ number_format($reportData['sales']['average_invoice'], 2) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                        Per invoice
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Average Payment</div>
                    <div class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-2">
                        ₦{{ number_format($reportData['collections']['average_payment'], 2) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                        Per payment
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Top Customers --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Top 10 Customers</h3>
                    </div>
                    <div class="p-6">
                        @if(count($reportData['top_customers']) > 0)
                            <div class="space-y-3">
                                @foreach($reportData['top_customers'] as $customer)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $customer['name'] }}</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            ₦{{ number_format($customer['total'], 2) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No customers found for this period.</p>
                        @endif
                    </div>
                </div>

                {{-- Invoice Status Breakdown --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Invoice Status Breakdown</h3>
                    </div>
                    <div class="p-6">
                        @if(count($reportData['status_breakdown']) > 0)
                            <div class="space-y-3">
                                @foreach($reportData['status_breakdown'] as $status => $data)
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                ({{ $data['count'] }})
                                            </span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            ₦{{ number_format($data['amount'], 2) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No invoices found for this period.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Payment Methods --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Payment Methods Breakdown</h3>
                </div>
                <div class="p-6">
                    @if(count($reportData['payment_methods']) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach($reportData['payment_methods'] as $method => $data)
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $method }}</div>
                                    <div class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                                        ₦{{ number_format($data['amount'], 2) }}
                                    </div>
                                    <div class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                        {{ $data['count'] }} transaction{{ $data['count'] != 1 ? 's' : '' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">No payments recorded for this period.</p>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
