<x-layout>
    <x-slot name="title">Unsettled Payments - Admin</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Unsettled Payments</h1>
                <p class="text-sm text-gray-500">Successful payments not yet settled to merchants</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.payments.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200">Dashboard</a>
                <a href="{{ route('admin.payments.all') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700">All Transactions</a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Total Unsettled</p>
                <p class="mt-2 text-2xl font-bold text-amber-600">&#8358;{{ number_format($totalUnsettled, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Count</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($totalCount) }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Reference</th>
                            <th class="px-4 py-3 text-left">Merchant</th>
                            <th class="px-4 py-3 text-left">Bank / Account</th>
                            <th class="px-4 py-3 text-right">Merchant Amount</th>
                            <th class="px-4 py-3 text-left">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($unsettledPayments as $txn)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $txn->reference ?? $txn->paystack_reference ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $txn->merchant_name ?? '—' }}</div>
                                    <div class="text-xs text-gray-400">{{ $txn->merchant_email ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    <div>{{ $txn->merchant_bank ?? '—' }}</div>
                                    <div class="text-xs text-gray-400 font-mono">{{ $txn->merchant_account ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-amber-600">&#8358;{{ number_format($txn->merchant_amount, 2) }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ optional($txn->created_at)->format('M d, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No unsettled payments. All caught up.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($unsettledPayments->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $unsettledPayments->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layout>
