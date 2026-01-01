<x-layout>
    <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Payment Processing Fees</h1>
                <p class="text-lg text-gray-600">Transparent pricing for invoice payments</p>
            </div>

            <!-- Fee Structure Card -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
                    <h2 class="text-2xl font-bold text-white">When Your Customer Pays Online</h2>
                </div>

                <div class="p-8">
                    <p class="text-gray-700 mb-6">
                        When customers use the <strong>"Pay Now"</strong> button on your invoice, the following fees apply:
                    </p>

                    <!-- Paystack Fee -->
                    <div class="mb-6 p-5 bg-blue-50 border-l-4 border-blue-500 rounded-r-lg">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-lg font-semibold text-blue-900 mb-2">Paystack Processing Fee</h3>
                                <p class="text-blue-800 mb-2">
                                    <span class="font-bold">1.5% + ₦100</span> per transaction
                                </p>
                                <p class="text-sm text-blue-700">Maximum: ₦2,000 per transaction</p>
                            </div>
                        </div>
                    </div>

                    <!-- Platform Service Fee -->
                    <div class="mb-6 p-5 bg-purple-50 border-l-4 border-purple-500 rounded-r-lg">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-lg font-semibold text-purple-900 mb-2">Kinvoice Service Charge</h3>
                                <p class="text-purple-800 mb-2">
                                    <span class="font-bold">2%</span> of invoice amount
                                </p>
                                <p class="text-sm text-purple-700">Minimum: ₦150 | Maximum: ₦3,000 per transaction</p>
                            </div>
                        </div>
                    </div>

                    <!-- Example Calculation -->
                    <div class="mt-8 p-6 bg-gray-50 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">💡 Example Calculation</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700">Invoice Amount:</span>
                                <span class="font-semibold text-gray-900">₦50,000.00</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">Paystack Fee (1.5% + ₦100):</span>
                                <span class="text-red-600">- ₦850.00</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">Service Charge (2%):</span>
                                <span class="text-red-600">- ₦1,000.00</span>
                            </div>
                            <div class="border-t border-gray-300 pt-3 flex justify-between items-center">
                                <span class="font-bold text-gray-900">You Receive:</span>
                                <span class="font-bold text-green-600 text-xl">₦48,150.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No-Fee Alternatives -->
            <div class="bg-green-50 rounded-xl border-2 border-green-200 p-6 mb-8">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xl font-bold text-green-900 mb-3">💰 Avoid Fees with Bank Transfer</h3>
                        <p class="text-green-800 mb-3">
                            Your customers can pay via <strong>direct bank transfer</strong> with <strong>ZERO fees</strong>.
                            Simply include your bank details on the invoice.
                        </p>
                        <ul class="list-disc list-inside text-green-700 space-y-1 text-sm">
                            <li>No processing fees</li>
                            <li>No service charges</li>
                            <li>You receive the full invoice amount</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Frequently Asked Questions</h2>

                <div class="space-y-6">
                    <!-- Question 1 -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Who pays the fees?</h3>
                        <p class="text-gray-700">
                            The fees are deducted from the payment amount you receive. Your customer pays the invoice amount,
                            and you receive that amount minus the processing fees.
                        </p>
                    </div>

                    <!-- Question 2 -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Can I pass the fees to my customer?</h3>
                        <p class="text-gray-700">
                            Yes! You can add the fees to your invoice amount if you'd like your customer to cover the processing costs.
                            Many businesses choose to absorb the fees as part of their operating costs.
                        </p>
                    </div>

                    <!-- Question 3 -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">When are fees charged?</h3>
                        <p class="text-gray-700">
                            Fees are only charged when a customer pays using the online "Pay Now" button.
                            Bank transfers and other payment methods have no fees.
                        </p>
                    </div>

                    <!-- Question 4 -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Why are there two fees?</h3>
                        <p class="text-gray-700">
                            <strong>Paystack fee</strong> is charged by the payment processor for handling card transactions.
                            <strong>Service charge</strong> is charged by Kinvoice for providing the invoicing platform,
                            payment tracking, and business management tools.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer CTA -->
            <div class="text-center">
                <p class="text-gray-600 mb-4">Have more questions about fees?</p>
                <a href="/contact" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</x-layout>
