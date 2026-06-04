<x-layout>
    <x-slot name="title">Payment Dashboard - Admin</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Payment Dashboard</h1>
                <p class="text-sm text-gray-500">
                    {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }}
                    &ndash;
                    {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.payments.unsettled') }}" class="inline-flex items-center px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700">Unsettled</a>
                <a href="{{ route('admin.payments.all') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700">All Transactions</a>
            </div>
        </div>

        {{-- Paystack balance --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Paystack Available Balance</p>
                    <p class="text-3xl font-bold text-gray-900">&#8358;{{ number_format($paystackBalance['available'] ?? 0, 2) }}</p>
                    @if(!empty($paystackBalance['error']))
                        <p class="text-xs text-red-500 mt-1">{{ $paystackBalance['error'] }}</p>
                    @endif
                </div>
                <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-100 text-green-600 text-xl font-bold">&#8358;</span>
            </div>
        </div>

        {{-- Stat cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @php
                $cards = [
                    ['label' => 'Platform Revenue', 'value' => '&#8358;' . number_format($stats['total_revenue'] ?? 0, 2), 'sub' => 'Commission (period)', 'color' => 'text-purple-600'],
                    ['label' => 'Transaction Volume', 'value' => '&#8358;' . number_format($stats['total_volume'] ?? 0, 2), 'sub' => ($stats['total_transactions'] ?? 0) . ' transactions', 'color' => 'text-blue-600'],
                    ['label' => 'Paid to Merchants', 'value' => '&#8358;' . number_format($stats['total_to_merchants'] ?? 0, 2), 'sub' => 'Settled portion', 'color' => 'text-green-600'],
                    ['label' => 'Today / This Month', 'value' => '&#8358;' . number_format($stats['today_revenue'] ?? 0, 2), 'sub' => 'Month: &#8358;' . number_format($stats['month_revenue'] ?? 0, 2), 'color' => 'text-indigo-600'],
                    ['label' => 'Unsettled Payments', 'value' => number_format($stats['unsettled_count'] ?? 0), 'sub' => '&#8358;' . number_format($stats['unsettled_amount'] ?? 0, 2) . ' owed', 'color' => 'text-amber-600'],
                    ['label' => 'Avg Transaction', 'value' => '&#8358;' . number_format($stats['avg_transaction'] ?? 0, 2), 'sub' => 'Per payment', 'color' => 'text-gray-700'],
                    ['label' => 'Avg Commission', 'value' => '&#8358;' . number_format($stats['avg_commission'] ?? 0, 2), 'sub' => 'Per payment', 'color' => 'text-gray-700'],
                ];
            @endphp
            @foreach($cards as $card)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $card['label'] }}</p>
                    <p class="mt-2 text-2xl font-bold {{ $card['color'] }}">{!! $card['value'] !!}</p>
                    <p class="mt-1 text-xs text-gray-500">{!! $card['sub'] !!}</p>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Recent transactions --}}
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Transactions</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left">Reference</th>
                                <th class="px-4 py-3 text-left">Merchant</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3 text-right">Commission</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-left">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($recentTransactions as $txn)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $txn->reference ?? $txn->paystack_reference ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ $txn->merchant_name ?? '—' }}</div>
                                        <div class="text-xs text-gray-400">{{ $txn->customer_name ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-900">&#8358;{{ number_format($txn->total_amount, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-purple-600">&#8358;{{ number_format($txn->platform_commission, 2) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @php $s = strtolower($txn->status ?? ''); @endphp
                                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $s === 'success' ? 'bg-green-100 text-green-700' : ($s === 'failed' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">{{ ucfirst($txn->status ?? 'unknown') }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 text-xs">{{ optional($txn->created_at)->format('M d, Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No transactions found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Top merchants --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Top Merchants</h2>
                </div>
                <ul class="divide-y divide-gray-100">
                    @forelse($topMerchants as $merchant)
                        <li class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 truncate">{{ $merchant->merchant_name ?? '—' }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $merchant->merchant_email ?? '' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900">&#8358;{{ number_format($merchant->total_volume, 2) }}</p>
                                    <p class="text-xs text-gray-400">{{ $merchant->transaction_count }} txns</p>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-center text-gray-400">No merchant activity.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Revenue trend --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mt-6">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900">Daily Revenue Trend</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-right">Transactions</th>
                            <th class="px-4 py-3 text-right">Volume</th>
                            <th class="px-4 py-3 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($revenueTrend as $day)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-700">{{ \Carbon\Carbon::parse($day->date)->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ number_format($day->count) }}</td>
                                <td class="px-4 py-3 text-right text-gray-900">&#8358;{{ number_format($day->volume, 2) }}</td>
                                <td class="px-4 py-3 text-right text-purple-600">&#8358;{{ number_format($day->revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No revenue data for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layout>
