<x-filament-panels::page>
    @php
        $subscription = $this->getSubscription();
        $usageSummary = $this->getUsageSummary();
    @endphp

    <div class="space-y-6">
        @if($subscription && $usageSummary['has_subscription'])
            {{-- Subscription Overview --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $subscription->plan->name }} Plan
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ ucfirst($subscription->billing_cycle) }} billing
                                " {{ $subscription->plan->currency }} {{ number_format($subscription->amount, 0) }}/{{ $subscription->billing_cycle === 'yearly' ? 'year' : 'month' }}
                            </p>
                        </div>
                        <div class="text-right">
                            @if($subscription->status === 'active')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Active
                                </span>
                            @elseif($subscription->status === 'canceled')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    Canceled
                                </span>
                            @elseif($subscription->status === 'past_due')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    Past Due
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Current Period</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $subscription->current_period_start?->format('M d') }} - {{ $subscription->current_period_end?->format('M d, Y') }}
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Next Billing Date</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $subscription->current_period_end?->format('M d, Y') ?? 'N/A' }}
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Days Until Renewal</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $subscription->daysUntilRenewal() }} days
                            </p>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('filament.app.pages.subscription-plans') }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                            Change Plan
                        </a>

                        @if($subscription->status === 'active')
                            <button wire:click="cancelSubscription"
                                    wire:confirm="Are you sure you want to cancel your subscription? You will continue to have access until the end of your billing period."
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                Cancel Subscription
                            </button>
                        @elseif($subscription->status === 'canceled')
                            <button wire:click="reactivateSubscription"
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                Reactivate Subscription
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Usage Statistics --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Current Usage</h3>

                <div class="space-y-6">
                    {{-- Invoices --}}
                    @if(isset($usageSummary['usage']['invoices']))
                        @php $invoiceUsage = $usageSummary['usage']['invoices']; @endphp
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Invoices</span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $invoiceUsage['used'] }} / {{ $invoiceUsage['unlimited'] ? '' : $invoiceUsage['limit'] }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all {{ $invoiceUsage['percentage'] >= 90 ? 'bg-red-600' : ($invoiceUsage['percentage'] >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                     style="width: {{ min($invoiceUsage['percentage'], 100) }}%"></div>
                            </div>
                        </div>
                    @endif

                    {{-- Customers --}}
                    @if(isset($usageSummary['usage']['customers']))
                        @php $customerUsage = $usageSummary['usage']['customers']; @endphp
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Customers</span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $customerUsage['used'] }} / {{ $customerUsage['unlimited'] ? '' : $customerUsage['limit'] }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all {{ $customerUsage['percentage'] >= 90 ? 'bg-red-600' : ($customerUsage['percentage'] >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                     style="width: {{ min($customerUsage['percentage'], 100) }}%"></div>
                            </div>
                        </div>
                    @endif

                    {{-- SMS Credits --}}
                    @if(isset($usageSummary['usage']['sms']) && $usageSummary['usage']['sms']['limit'] > 0)
                        @php $smsUsage = $usageSummary['usage']['sms']; @endphp
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">SMS Credits</span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $smsUsage['used'] }} / {{ $smsUsage['unlimited'] ? '' : $smsUsage['limit'] }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all {{ $smsUsage['percentage'] >= 90 ? 'bg-red-600' : ($smsUsage['percentage'] >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                     style="width: {{ min($smsUsage['percentage'], 100) }}%"></div>
                            </div>
                        </div>
                    @endif

                    {{-- WhatsApp Credits --}}
                    @if(isset($usageSummary['usage']['whatsapp']) && $usageSummary['usage']['whatsapp']['limit'] > 0)
                        @php $whatsappUsage = $usageSummary['usage']['whatsapp']; @endphp
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">WhatsApp Messages</span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $whatsappUsage['used'] }} / {{ $whatsappUsage['unlimited'] ? '' : $whatsappUsage['limit'] }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all {{ $whatsappUsage['percentage'] >= 90 ? 'bg-red-600' : ($whatsappUsage['percentage'] >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                     style="width: {{ min($whatsappUsage['percentage'], 100) }}%"></div>
                            </div>
                        </div>
                    @endif

                    {{-- API Requests --}}
                    @if(isset($usageSummary['usage']['api']) && $usageSummary['usage']['api']['limit'] > 0)
                        @php $apiUsage = $usageSummary['usage']['api']; @endphp
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">API Requests</span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ number_format($apiUsage['used']) }} / {{ $apiUsage['unlimited'] ? '' : number_format($apiUsage['limit']) }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all {{ $apiUsage['percentage'] >= 90 ? 'bg-red-600' : ($apiUsage['percentage'] >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                     style="width: {{ min($apiUsage['percentage'], 100) }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>

                <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    Usage resets on {{ $subscription->current_period_end?->format('M d, Y') }}
                </p>
            </div>

            {{-- Recent Payments --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Payments</h3>

                @php $transactions = $this->getPaymentTransactions(); @endphp

                @if($transactions->count() > 0)
                    <div class="space-y-3">
                        @foreach($transactions as $transaction)
                            <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $transaction->formatted_type }}
                                    </p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ $transaction->created_at->format('M d, Y g:i A') }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $transaction->formatted_amount }}
                                    </p>
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                        {{ $transaction->status === 'successful' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                           ($transaction->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                           'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                                        {{ ucfirst($transaction->status) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('filament.app.pages.payment-history') }}"
                           class="text-sm font-medium text-primary-600 hover:text-primary-700">
                            View all payments ’
                        </a>
                    </div>
                @else
                    <p class="text-sm text-gray-600 dark:text-gray-400">No payment history yet.</p>
                @endif
            </div>

        @else
            {{-- No Subscription --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No Active Subscription</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 max-w-md mx-auto">
                    Choose a plan to unlock all features and start managing your invoices efficiently.
                </p>
                <div class="mt-6">
                    <a href="{{ route('filament.app.pages.subscription-plans') }}"
                       class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                        View Plans
                    </a>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
