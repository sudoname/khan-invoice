<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaction Reference</p>
            <p class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $transaction->transaction_reference }}</p>
        </div>

        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
            <p class="mt-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ $transaction->status === 'successful' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                       ($transaction->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                       'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                    {{ ucfirst($transaction->status) }}
                </span>
            </p>
        </div>

        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Amount</p>
            <p class="mt-1 text-sm text-gray-900 dark:text-white font-semibold">{{ $transaction->formatted_amount }}</p>
        </div>

        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</p>
            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $transaction->formatted_type }}</p>
        </div>

        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Payment Gateway</p>
            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ ucfirst($transaction->payment_gateway) }}</p>
        </div>

        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Date</p>
            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $transaction->created_at->format('M d, Y g:i A') }}</p>
        </div>

        @if($transaction->paystack_reference)
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Paystack Reference</p>
                <p class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $transaction->paystack_reference }}</p>
            </div>
        @endif

        @if($transaction->description)
            <div class="col-span-2">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</p>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $transaction->description }}</p>
            </div>
        @endif
    </div>

    @if($transaction->metadata)
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Additional Information</p>
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3 text-xs font-mono overflow-x-auto">
                <pre class="text-gray-700 dark:text-gray-300">{{ json_encode($transaction->metadata, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    @endif
</div>
