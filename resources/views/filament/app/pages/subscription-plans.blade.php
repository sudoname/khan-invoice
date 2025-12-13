<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Current Plan Info --}}
        @if($currentSubscription = $this->getCurrentSubscription())
            <div class="bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-primary-900 dark:text-primary-100">
                            Current Plan: {{ $currentSubscription->plan->name }}
                        </h3>
                        <p class="text-sm text-primary-700 dark:text-primary-300">
                            Billing: {{ ucfirst($currentSubscription->billing_cycle) }}
                            @if($currentSubscription->current_period_end)
                                " Renews on {{ $currentSubscription->current_period_end->format('M d, Y') }}
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('filament.app.pages.my-subscription') }}"
                       class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                        Manage Subscription ’
                    </a>
                </div>
            </div>
        @endif

        {{-- Billing Cycle Toggle --}}
        <div class="flex justify-center">
            <div class="inline-flex items-center gap-3 bg-gray-100 dark:bg-gray-800 rounded-lg p-1"
                 x-data="{ cycle: 'monthly' }">
                <button @click="cycle = 'monthly'"
                        :class="cycle === 'monthly' ? 'bg-white dark:bg-gray-700 shadow-sm' : ''"
                        class="px-4 py-2 text-sm font-medium rounded-md transition-all">
                    Monthly
                </button>
                <button @click="cycle = 'yearly'"
                        :class="cycle === 'yearly' ? 'bg-white dark:bg-gray-700 shadow-sm' : ''"
                        class="px-4 py-2 text-sm font-medium rounded-md transition-all">
                    Yearly
                    <span class="ml-1 text-xs text-green-600 dark:text-green-400 font-semibold">Save 17%</span>
                </button>
            </div>
        </div>

        {{-- Plans Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" x-data="{ cycle: 'monthly' }">
            @foreach($this->getPlans() as $plan)
                <div class="relative bg-white dark:bg-gray-800 rounded-lg border-2
                            {{ $currentSubscription && $currentSubscription->plan_id === $plan->id
                               ? 'border-primary-500 shadow-lg'
                               : 'border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-700' }}
                            transition-all duration-200">

                    {{-- Popular Badge --}}
                    @if($plan->is_popular)
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <span class="bg-gradient-to-r from-primary-500 to-primary-600 text-white text-xs font-bold px-3 py-1 rounded-full shadow-md">
                                MOST POPULAR
                            </span>
                        </div>
                    @endif

                    <div class="p-6 space-y-4">
                        {{-- Plan Name --}}
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ $plan->name }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ $plan->description }}
                            </p>
                        </div>

                        {{-- Price --}}
                        <div>
                            <div x-show="cycle === 'monthly'">
                                <span class="text-4xl font-bold text-gray-900 dark:text-white">
                                    {{ $plan->formatted_monthly_price }}
                                </span>
                                <span class="text-gray-600 dark:text-gray-400">/month</span>
                            </div>
                            <div x-show="cycle === 'yearly'" x-cloak>
                                <span class="text-4xl font-bold text-gray-900 dark:text-white">
                                    {{ $plan->formatted_yearly_price }}
                                </span>
                                <span class="text-gray-600 dark:text-gray-400">/year</span>
                                @if($plan->yearly_savings > 0)
                                    <div class="text-sm text-green-600 dark:text-green-400 font-medium mt-1">
                                        Save {{ $plan->yearly_savings }}%
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Features List --}}
                        <ul class="space-y-3 text-sm">
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300">
                                    {{ $plan->max_invoices === -1 ? 'Unlimited' : $plan->max_invoices }} invoices/month
                                </span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300">
                                    {{ $plan->max_customers === -1 ? 'Unlimited' : $plan->max_customers }} customers
                                </span>
                            </li>
                            @if($plan->sms_credits_monthly > 0)
                                <li class="flex items-start gap-2">
                                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700 dark:text-gray-300">
                                        {{ number_format($plan->sms_credits_monthly) }} SMS credits/month
                                    </span>
                                </li>
                            @endif
                            @if($plan->whatsapp_credits_monthly > 0)
                                <li class="flex items-start gap-2">
                                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700 dark:text-gray-300">
                                        {{ number_format($plan->whatsapp_credits_monthly) }} WhatsApp messages/month
                                    </span>
                                </li>
                            @endif
                            @if($plan->api_access)
                                <li class="flex items-start gap-2">
                                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700 dark:text-gray-300">
                                        API Access
                                        @if($plan->api_requests_monthly > 0)
                                            ({{ number_format($plan->api_requests_monthly) }} requests/month)
                                        @endif
                                    </span>
                                </li>
                            @endif
                            @if($plan->multi_currency)
                                <li class="flex items-start gap-2">
                                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700 dark:text-gray-300">Multi-currency support</span>
                                </li>
                            @endif
                            @if($plan->recurring_invoices)
                                <li class="flex items-start gap-2">
                                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700 dark:text-gray-300">Recurring invoices</span>
                                </li>
                            @endif
                            @if($plan->priority_support)
                                <li class="flex items-start gap-2">
                                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700 dark:text-gray-300">Priority support</span>
                                </li>
                            @endif
                        </ul>

                        {{-- CTA Button --}}
                        <div class="pt-4">
                            @if($currentSubscription && $currentSubscription->plan_id === $plan->id)
                                <button disabled
                                        class="w-full py-2 px-4 rounded-lg font-medium bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed">
                                    Current Plan
                                </button>
                            @else
                                <button wire:click="selectPlan({{ $plan->id }}, cycle)"
                                        class="w-full py-2 px-4 rounded-lg font-medium transition-colors
                                               {{ $plan->is_popular
                                                  ? 'bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white'
                                                  : 'bg-primary-600 hover:bg-primary-700 text-white' }}">
                                    {{ $plan->isFree() ? 'Get Started Free' : 'Select Plan' }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Help Text --}}
        <div class="text-center text-sm text-gray-600 dark:text-gray-400 mt-8">
            Need help choosing a plan? <a href="mailto:support@kinvoice.ng" class="text-primary-600 hover:text-primary-700 font-medium">Contact us</a>
        </div>
    </div>
</x-filament-panels::page>
