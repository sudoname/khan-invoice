<x-layout>
    <!-- Hero Section -->
    <section class="gradient-bg text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div>
                    <h1 class="text-4xl md:text-5xl font-bold mb-6">
                        Professional Invoice Management for Nigerian Businesses
                    </h1>
                    <p class="text-xl mb-8 text-purple-100">
                        Create, manage, and track invoices with built-in VAT, Withholding Tax, and Nigerian banking support.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        @auth
                            <a href="/app" class="bg-white text-purple-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition text-center">
                                Go to Dashboard
                            </a>
                        @else
                            <a href="/login" class="bg-white text-purple-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition text-center">
                                Get Started
                            </a>
                        @endauth
                        <a href="{{ route('public-invoice.create') }}" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-purple-600 transition text-center">
                            Generate Free Invoice
                        </a>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="bg-white rounded-lg shadow-2xl p-6">
                        <div class="text-gray-800">
                            <div class="flex justify-between items-center mb-4 pb-4 border-b">
                                <h3 class="text-xl font-bold">Sample Invoice</h3>
                                <span class="text-sm text-gray-500">INV-2025-001</span>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal</span>
                                    <span class="font-semibold">₦500,000.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">VAT (7.5%)</span>
                                    <span class="font-semibold">₦37,500.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Discount (5%)</span>
                                    <span class="font-semibold text-green-600">-₦25,000.00</span>
                                </div>
                                <div class="flex justify-between pt-2 border-t-2 border-purple-600">
                                    <span class="font-bold text-lg">Total</span>
                                    <span class="font-bold text-lg text-purple-600">₦512,500.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Built for Nigerian Businesses
                </h2>
                <p class="text-xl text-gray-600">
                    Everything you need to manage invoices professionally
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-8 hover:shadow-lg transition">
                    <div class="bg-purple-600 w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Automatic Calculations</h3>
                    <p class="text-gray-700">
                        Built-in VAT (7.5%) and Withholding Tax calculations with automatic totals.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-8 hover:shadow-lg transition">
                    <div class="bg-blue-600 w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Nigerian Bank Support</h3>
                    <p class="text-gray-700">
                        Pre-loaded with all major Nigerian banks for easy payment processing.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-8 hover:shadow-lg transition">
                    <div class="bg-green-600 w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">PDF Generation</h3>
                    <p class="text-gray-700">
                        Professional PDF invoices formatted for Nigerian businesses with Naira symbol.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-8 hover:shadow-lg transition">
                    <div class="bg-orange-600 w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Secure Sharing</h3>
                    <p class="text-gray-700">
                        Share invoices securely with clients using unique, password-protected links.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-8 hover:shadow-lg transition">
                    <div class="bg-red-600 w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">CAC & TIN Support</h3>
                    <p class="text-gray-700">
                        Include CAC number and TIN on invoices for full Nigerian compliance.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl p-8 hover:shadow-lg transition">
                    <div class="bg-indigo-600 w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Track Payments</h3>
                    <p class="text-gray-700">
                        Monitor payment status with draft, sent, paid, and overdue tracking.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Free Invoice Generator Section -->
    <section class="py-20 bg-gradient-to-br from-purple-600 to-blue-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-2xl p-8 md:p-12">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                    <div>
                        <div class="inline-block bg-purple-100 text-purple-600 px-4 py-2 rounded-lg font-semibold text-sm mb-4">
                            Try it Free - No Signup Required
                        </div>
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                            Free Invoice Generator
                        </h2>
                        <p class="text-xl text-gray-600 mb-6">
                            Create professional Nigerian invoices instantly without creating an account. Try our invoice generator and see how easy it is!
                        </p>
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-start">
                                <svg class="w-6 h-6 text-green-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">No signup or registration required</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-6 h-6 text-green-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Automatic VAT and WHT calculations</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-6 h-6 text-green-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Professional PDF download</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-6 h-6 text-green-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Nigerian Naira (₦) formatting</span>
                            </li>
                        </ul>
                        <a href="{{ route('public-invoice.create') }}" class="inline-block bg-gradient-to-r from-purple-600 to-blue-600 text-white px-8 py-4 rounded-lg font-semibold text-lg hover:from-purple-700 hover:to-blue-700 transition transform hover:scale-105 shadow-lg">
                            Generate Free Invoice
                        </a>
                    </div>
                    <div class="hidden md:block">
                        <div class="relative">
                            <div class="absolute -top-4 -right-4 bg-yellow-400 text-yellow-900 px-4 py-2 rounded-lg font-bold text-sm transform rotate-12 shadow-lg">
                                100% Free!
                            </div>
                            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-6 shadow-2xl border-2 border-purple-200">
                                <div class="flex justify-between items-center mb-4 pb-3 border-b-2 border-purple-600">
                                    <div class="text-purple-600 font-bold text-lg">Sample Invoice</div>
                                    <div class="text-sm text-gray-500">INV-20250130</div>
                                </div>
                                <div class="space-y-3 text-sm mb-4">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Web Design Services</span>
                                        <span class="font-semibold">₦250,000</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Hosting (12 months)</span>
                                        <span class="font-semibold">₦50,000</span>
                                    </div>
                                    <div class="flex justify-between pt-2 border-t">
                                        <span class="text-gray-600">Subtotal</span>
                                        <span class="font-semibold">₦300,000</span>
                                    </div>
                                    <div class="flex justify-between text-green-600">
                                        <span>VAT (7.5%)</span>
                                        <span class="font-semibold">+₦22,500</span>
                                    </div>
                                    <div class="flex justify-between pt-2 border-t-2 border-purple-600">
                                        <span class="font-bold">Total</span>
                                        <span class="font-bold text-purple-600">₦322,500</span>
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-t">
                                    <div class="text-xs text-gray-500 text-center">
                                        Download as PDF • Email to Client • Professional Format
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                Ready to Streamline Your Invoicing?
            </h2>
            <p class="text-xl text-gray-600 mb-8">
                Start creating professional Nigerian invoices in minutes.
            </p>
            @auth
                <a href="/app" class="inline-block gradient-bg text-white px-8 py-4 rounded-lg font-semibold text-lg hover:shadow-lg transition">
                    Go to Dashboard
                </a>
            @else
                <a href="/login" class="inline-block gradient-bg text-white px-8 py-4 rounded-lg font-semibold text-lg hover:shadow-lg transition">
                    Get Started
                </a>
            @endauth
        </div>
    </section>
</x-layout>
