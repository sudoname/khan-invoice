<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Current</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                    ₦{{ number_format($summary['current'], 2) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Not yet due</div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">1-30 Days</div>
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">
                    ₦{{ number_format($summary['1_30'], 2) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Slightly overdue</div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">31-60 Days</div>
                <div class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">
                    ₦{{ number_format($summary['31_60'], 2) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Moderately overdue</div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">61-90 Days</div>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                    ₦{{ number_format($summary['61_90'], 2) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Seriously overdue</div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Over 90 Days</div>
                <div class="text-2xl font-bold text-red-800 dark:text-red-600 mt-1">
                    ₦{{ number_format($summary['over_90'], 2) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Critical</div>
            </div>

            <div class="bg-primary-50 dark:bg-primary-900/20 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-primary-600 dark:text-primary-400">Total Outstanding</div>
                <div class="text-2xl font-bold text-primary-700 dark:text-primary-300 mt-1">
                    ₦{{ number_format($summary['total'], 2) }}
                </div>
                <div class="text-xs text-primary-600 dark:text-primary-400 mt-1">All periods</div>
            </div>
        </div>

        {{-- Aging Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Aging by Customer</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Breakdown of outstanding amounts by customer and aging period</p>
            </div>

            @if(count($agingData) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Customer
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Current
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    1-30 Days
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    31-60 Days
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    61-90 Days
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Over 90 Days
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($agingData as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $row['customer'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700 dark:text-gray-300">
                                        ₦{{ number_format($row['current'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-yellow-600 dark:text-yellow-400">
                                        ₦{{ number_format($row['1_30'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-orange-600 dark:text-orange-400">
                                        ₦{{ number_format($row['31_60'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 dark:text-red-400">
                                        ₦{{ number_format($row['61_90'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-800 dark:text-red-600">
                                        ₦{{ number_format($row['over_90'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900 dark:text-white">
                                        ₦{{ number_format($row['total'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-100 dark:bg-gray-900">
                            <tr class="font-bold">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    TOTAL
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                    ₦{{ number_format($summary['current'], 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-yellow-700 dark:text-yellow-300">
                                    ₦{{ number_format($summary['1_30'], 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-orange-700 dark:text-orange-300">
                                    ₦{{ number_format($summary['31_60'], 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-700 dark:text-red-300">
                                    ₦{{ number_format($summary['61_90'], 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-900 dark:text-red-500">
                                    ₦{{ number_format($summary['over_90'], 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                    ₦{{ number_format($summary['total'], 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No outstanding invoices</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">All invoices are either paid or in draft status.</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
