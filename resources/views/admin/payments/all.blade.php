<x-layout>
    <x-slot name="title">All Transactions - Admin</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">All Transactions</h1>
                <p class="text-sm text-gray-500">{{ $transactions->total() }} total transactions</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.payments.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200">Dashboard</a>
                <a href="{{ route('admin.payments.unsettled') }}" class="inline-flex items-center px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700">Unsettled</a>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6 grid grid-cols-1 sm:grid-cols-4 gap-3">
            <input type="text" name="merchant" value="{{ request('merchant') }}" placeholder="Merchant name" class="rounded-lg border-gray-300 text-sm focus:ring-purple-500 focus:border-purple-500">
            <select name="status" class="rounded-lg border-gray-300 text-sm focus:ring-purple-500 focus:border-purple-500">
                <option value="">All statuses</option>
                @foreach(['success', 'failed', 'pending', 'abandoned'] as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="rounded-lg border-gray-300 text-sm focus:ring-purple-500 focus:border-purple-500">
            <div class="flex gap-2">
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="flex-1 rounded-lg border-gray-300 text-sm focus:ring-purple-500 focus:border-purple-500">
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700">Filter</button>
            </div>
        </form>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Reference</th>
                            <th class="px-4 py-3 text-left">Merchant</th>
                            <th class="px-4 py-3 text-left">Customer</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-right">Commission</th>
                            <th class="px-4 py-3 text-center">Settled</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-left">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($transactions as $txn)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $txn->reference ?? $txn->paystack_reference ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $txn->merchant_name ?? '—' }}</div>
                                    <div class="text-xs text-gray-400">{{ $txn->merchant_email ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $txn->customer_name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-gray-900">&#8358;{{ number_format($txn->total_amount, 2) }}</td>
                                <td class="px-4 py-3 text-right text-purple-600">&#8358;{{ number_format($txn->platform_commission, 2) }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($txn->settled_to_merchant)
                                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">Yes</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-700">No</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @php $s = strtolower($txn->status ?? ''); @endphp
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $s === 'success' ? 'bg-green-100 text-green-700' : ($s === 'failed' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">{{ ucfirst($txn->status ?? 'unknown') }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ optional($txn->created_at)->format('M d, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No transactions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($transactions->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $transactions->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layout>
