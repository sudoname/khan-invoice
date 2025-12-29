<x-layout>
    <section class="gradient-bg text-white py-12">
        <div class="max-w-4xl mx-auto px-4">
            <h1 class="text-3xl font-bold text-center">Complete Your Subscription</h1>
        </div>
    </section>

    <section class="py-12">
        <div class="max-w-4xl mx-auto px-4">
            <div class="grid md:grid-cols-2 gap-8">
                <!-- Order Summary -->
                <div class="bg-white rounded-xl shadow-lg p-8">
                    <h2 class="text-2xl font-bold mb-6">Order Summary</h2>

                    <div class="space-y-4">
                        <div>
                            <h3 class="text-xl font-semibold">{{ $plan->name }} Plan</h3>
                            <p class="text-gray-600">{{ $plan->description }}</p>
                        </div>

                        <!-- Billing Cycle Toggle -->
                        <div class="border-t pt-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-3">Choose Billing Cycle:</label>
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <button type="button" onclick="selectCycle('monthly')" id="monthlyBtn"
                                    class="billing-cycle-btn px-4 py-3 rounded-lg border-2 transition {{ $billingCycle === 'monthly' ? 'border-purple-600 bg-purple-50 text-purple-700 font-semibold' : 'border-gray-300 hover:border-purple-300' }}">
                                    <div class="text-center">
                                        <div class="font-semibold">Monthly</div>
                                        <div class="text-sm">₦{{ number_format($plan->price_monthly) }}/mo</div>
                                    </div>
                                </button>
                                <button type="button" onclick="selectCycle('yearly')" id="yearlyBtn"
                                    class="billing-cycle-btn px-4 py-3 rounded-lg border-2 transition {{ $billingCycle === 'yearly' ? 'border-purple-600 bg-purple-50 text-purple-700 font-semibold' : 'border-gray-300 hover:border-purple-300' }}">
                                    <div class="text-center">
                                        <div class="font-semibold">Annual</div>
                                        <div class="text-sm">₦{{ number_format($plan->price_yearly) }}/yr</div>
                                        @if($plan->price_yearly < $plan->price_monthly * 12)
                                        <div class="text-xs text-green-600 font-semibold mt-1">Save {{ round((1 - ($plan->price_yearly / ($plan->price_monthly * 12))) * 100) }}%</div>
                                        @endif
                                    </div>
                                </button>
                            </div>
                        </div>

                        <div class="border-t pt-4">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Billing Cycle:</span>
                                <span class="font-semibold capitalize" id="cycleDisplay">{{ $billingCycle }}</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total:</span>
                                <span class="text-purple-600" id="totalAmount">₦{{ number_format($amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <h3 class="text-lg font-semibold mt-8 mb-4">Features Included:</h3>
                    <div class="space-y-2">
                        <div class="flex">
                            <svg class="w-5 h-5 text-green-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-sm">
                                @if($plan->max_invoices == -1)
                                    Unlimited invoices
                                @else
                                    {{ $plan->max_invoices }} invoices per month
                                @endif
                            </span>
                        </div>
                        
                        <div class="flex">
                            <svg class="w-5 h-5 text-green-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-sm">
                                @if($plan->max_customers == -1)
                                    Unlimited customers
                                @else
                                    {{ $plan->max_customers }} customers
                                @endif
                            </span>
                        </div>
                        
                        @if($plan->sms_credits_monthly > 0)
                        <div class="flex">
                            <svg class="w-5 h-5 text-green-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-sm">{{ number_format($plan->sms_credits_monthly) }} SMS credits per month</span>
                        </div>
                        @endif
                        
                        @if($plan->multi_currency)
                        <div class="flex">
                            <svg class="w-5 h-5 text-green-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-sm">Multi-currency support</span>
                        </div>
                        @endif
                        
                        @if($plan->api_access)
                        <div class="flex">
                            <svg class="w-5 h-5 text-green-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-sm">API access</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Payment Section -->
                <div class="bg-white rounded-xl shadow-lg p-8">
                    <h2 class="text-2xl font-bold mb-6">Payment</h2>
                    
                    <form id="paymentForm">
                        <button type="button" onclick="initiatePayment()" id="payButton" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-4 rounded-lg font-semibold text-lg transition">
                            Pay ₦{{ number_format($amount) }} Now
                        </button>
                    </form>
                    
                    <div class="mt-6 text-center text-sm text-gray-600">
                        <p class="mb-2">Secured payment powered by Paystack</p>
                        <div class="flex justify-center items-center space-x-2">
                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span>256-bit SSL encryption</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
    // Plan data
    const planData = {
        slug: "{{ $plan->slug }}",
        monthlyPrice: {{ $plan->price_monthly }},
        yearlyPrice: {{ $plan->price_yearly }}
    };

    let selectedCycle = "{{ $billingCycle }}";

    function selectCycle(cycle) {
        selectedCycle = cycle;
        const amount = cycle === 'monthly' ? planData.monthlyPrice : planData.yearlyPrice;

        // Update UI
        document.getElementById('cycleDisplay').textContent = cycle.charAt(0).toUpperCase() + cycle.slice(1);
        document.getElementById('totalAmount').textContent = '₦' + amount.toLocaleString();
        document.getElementById('payButton').textContent = 'Pay ₦' + amount.toLocaleString() + ' Now';

        // Update button styles
        const monthlyBtn = document.getElementById('monthlyBtn');
        const yearlyBtn = document.getElementById('yearlyBtn');

        if (cycle === 'monthly') {
            monthlyBtn.className = 'billing-cycle-btn px-4 py-3 rounded-lg border-2 transition border-purple-600 bg-purple-50 text-purple-700 font-semibold';
            yearlyBtn.className = 'billing-cycle-btn px-4 py-3 rounded-lg border-2 transition border-gray-300 hover:border-purple-300';
        } else {
            yearlyBtn.className = 'billing-cycle-btn px-4 py-3 rounded-lg border-2 transition border-purple-600 bg-purple-50 text-purple-700 font-semibold';
            monthlyBtn.className = 'billing-cycle-btn px-4 py-3 rounded-lg border-2 transition border-gray-300 hover:border-purple-300';
        }
    }

    function initiatePayment() {
        fetch("{{ route('checkout.initialize') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                plan_slug: planData.slug,
                billing_cycle: selectedCycle
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.authorization_url;
            } else {
                alert("Payment initialization failed: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An error occurred. Please try again.");
        });
    }
    </script>
</x-layout>
