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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Revenue</div>
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
                        ₦{{ number_format($reportData['revenue']['total'], 2) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                        From paid invoices
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Expenses</div>
                    <div class="text-3xl font-bold text-red-600 dark:text-red-400 mt-2">
                        ₦{{ number_format($reportData['expenses']['total'], 2) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                        Paid expenses
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Net Income</div>
                    <div class="text-3xl font-bold {{ $reportData['net_income'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} mt-2">
                        ₦{{ number_format($reportData['net_income'], 2) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                        Profit Margin: {{ number_format($reportData['profit_margin'], 1) }}%
                    </div>
                </div>
            </div>

            {{-- Detailed Breakdown --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Expenses by Category --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Expenses by Category</h3>
                    </div>
                    <div class="p-6">
                        @if(count($reportData['expenses']['by_category']) > 0)
                            <div class="space-y-3">
                                @foreach($reportData['expenses']['by_category'] as $category => $amount)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ ucfirst($category) }}</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            ₦{{ number_format($amount, 2) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No expenses found for this period.</p>
                        @endif
                    </div>
                </div>

                {{-- Monthly Comparison --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Monthly Revenue vs Expenses</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            @for($month = 1; $month <= 12; $month++)
                                @php
                                    $revenue = $reportData['revenue']['by_month'][$month] ?? 0;
                                    $expenses = $reportData['expenses']['by_month'][$month] ?? 0;
                                    $monthName = DateTime::createFromFormat('!m', $month)->format('F');
                                @endphp
                                @if($revenue > 0 || $expenses > 0)
                                    <div class="border-b border-gray-100 dark:border-gray-700 pb-2">
                                        <div class="flex justify-between text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            <span>{{ $monthName }}</span>
                                            <span class="{{ ($revenue - $expenses) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                ₦{{ number_format($revenue - $expenses, 2) }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 flex justify-between">
                                            <span>Revenue: ₦{{ number_format($revenue, 2) }}</span>
                                            <span>Expenses: ₦{{ number_format($expenses, 2) }}</span>
                                        </div>
                                    </div>
                                @endif
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
