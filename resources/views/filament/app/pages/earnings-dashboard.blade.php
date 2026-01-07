<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Payments Received -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Payments</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                            ₦{{ number_format($stats['total_payments'], 2) }}
                        </p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                        <x-heroicon-o-currency-dollar class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                </div>
            </div>

            <!-- Total Fees Paid -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Fees</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                            ₦{{ number_format($stats['total_fees'], 2) }}
                        </p>
                    </div>
                    <div class="p-3 bg-orange-100 dark:bg-orange-900 rounded-full">
                        <x-heroicon-o-minus-circle class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                    </div>
                </div>
            </div>

            <!-- Net Received -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Net Received</p>
                        <p class="text-2xl font-bold text-success-600 dark:text-success-400 mt-2">
                            ₦{{ number_format($stats['net_received'], 2) }}
                        </p>
                    </div>
                    <div class="p-3 bg-success-100 dark:bg-success-900 rounded-full">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-success-600 dark:text-success-400" />
                    </div>
                </div>
            </div>

            <!-- Available Balance -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Available Balance</p>
                        <p class="text-2xl font-bold text-primary-600 dark:text-primary-400 mt-2">
                            ₦{{ number_format($stats['available_balance'], 2) }}
                        </p>
                    </div>
                    <div class="p-3 bg-primary-100 dark:bg-primary-900 rounded-full">
                        <x-heroicon-o-wallet class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                </div>
            </div>

            <!-- Total Payouts -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Payouts</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                            ₦{{ number_format($stats['total_payouts'], 2) }}
                        </p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                        <x-heroicon-o-banknotes class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </div>

            <!-- Payout Fees -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Payout Fees</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                            ₦{{ number_format($stats['payout_fees'], 2) }}
                        </p>
                    </div>
                    <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-full">
                        <x-heroicon-o-receipt-percent class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                    </div>
                </div>
            </div>

            <!-- Pending Payouts -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 md:col-span-2">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pending Payouts</p>
                        <p class="text-2xl font-bold text-warning-600 dark:text-warning-400 mt-2">
                            ₦{{ number_format($stats['pending_payouts'], 2) }}
                        </p>
                    </div>
                    <div class="p-3 bg-warning-100 dark:bg-warning-900 rounded-full">
                        <x-heroicon-o-clock class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings Chart -->
        @if(count($earningsChart) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Earnings Over Last 12 Months
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="text-left py-2 px-3 text-gray-700 dark:text-gray-300">Month</th>
                            <th class="text-right py-2 px-3 text-gray-700 dark:text-gray-300">Gross Amount</th>
                            <th class="text-right py-2 px-3 text-gray-700 dark:text-gray-300">Fees</th>
                            <th class="text-right py-2 px-3 text-gray-700 dark:text-gray-300">Net Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($earningsChart as $item)
                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="py-3 px-3 text-gray-900 dark:text-white font-medium">{{ $item['month'] }}</td>
                            <td class="py-3 px-3 text-right text-gray-900 dark:text-white">
                                ₦{{ number_format($item['amount'], 2) }}
                            </td>
                            <td class="py-3 px-3 text-right text-orange-600 dark:text-orange-400">
                                ₦{{ number_format($item['fees'], 2) }}
                            </td>
                            <td class="py-3 px-3 text-right text-success-600 dark:text-success-400 font-semibold">
                                ₦{{ number_format($item['net'], 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Recent Payouts -->
        @if(count($recentPayouts) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Recent Payouts
                </h3>
                <a href="{{ route('filament.app.resources.payouts.index') }}"
                   class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                    View All →
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="text-left py-2 px-3 text-gray-700 dark:text-gray-300">Reference</th>
                            <th class="text-right py-2 px-3 text-gray-700 dark:text-gray-300">Amount</th>
                            <th class="text-center py-2 px-3 text-gray-700 dark:text-gray-300">Type</th>
                            <th class="text-center py-2 px-3 text-gray-700 dark:text-gray-300">Status</th>
                            <th class="text-right py-2 px-3 text-gray-700 dark:text-gray-300">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentPayouts as $payout)
                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="py-3 px-3 text-gray-900 dark:text-white font-mono text-xs">
                                {{ $payout['reference'] }}
                            </td>
                            <td class="py-3 px-3 text-right text-gray-900 dark:text-white font-semibold">
                                ₦{{ number_format($payout['net_amount'], 2) }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                    {{ $payout['payout_type'] === 'STANDARD' ? 'bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300' : '' }}
                                    {{ $payout['payout_type'] === 'INSTANT' ? 'bg-warning-100 text-warning-700 dark:bg-warning-900 dark:text-warning-300' : '' }}
                                    {{ $payout['payout_type'] === 'MANUAL' ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' : '' }}">
                                    {{ $payout['payout_type'] }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                    {{ $payout['status'] === 'COMPLETED' ? 'bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300' : '' }}
                                    {{ $payout['status'] === 'PENDING' ? 'bg-warning-100 text-warning-700 dark:bg-warning-900 dark:text-warning-300' : '' }}
                                    {{ $payout['status'] === 'PROCESSING' ? 'bg-info-100 text-info-700 dark:bg-info-900 dark:text-info-300' : '' }}
                                    {{ $payout['status'] === 'FAILED' ? 'bg-danger-100 text-danger-700 dark:bg-danger-900 dark:text-danger-300' : '' }}">
                                    {{ $payout['status'] }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right text-gray-600 dark:text-gray-400">
                                {{ $payout['created_at'] }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>
