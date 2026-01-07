<x-layout>
    <x-slot name="title">Free Invoice Generator - Khan Invoice</x-slot>
    <x-slot name="description">Create professional Nigerian invoices instantly with our free generator. No signup required. VAT (7.5%), WHT, Paystack payments, PDF download, and WhatsApp sharing included.</x-slot>

    <!-- Hero Section -->
    <div class="gradient-bg text-white py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Free Invoice Generator</h1>
            <p class="text-xl text-purple-100">
                Create professional Nigerian invoices in seconds - No signup required
            </p>
        </div>
    </div>

    <!-- Free Mode Awareness Banner (for logged-out users) -->
    @guest
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 border-2 border-blue-300 rounded-xl p-4 sm:p-6 shadow-md">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-bold">
                            FREE MODE
                        </div>
                        <span class="text-gray-700 font-semibold">Create unlimited invoices instantly</span>
                    </div>
                    <p class="text-sm text-gray-600 mb-3">
                        Want to save customers, track payments, and create invoices faster? <strong>Create a free account in 20 seconds</strong>.
                    </p>
                    <div class="flex flex-wrap gap-3 items-center">
                        <a href="{{ route('filament.app.auth.register') }}?ref=generator_banner"
                           onclick="if (window.KinvoiceAnalytics) { window.KinvoiceAnalytics.track('generator_banner_signup_clicked'); }"
                           class="inline-flex items-center bg-gradient-to-r from-purple-600 to-blue-600 text-white px-5 py-2.5 rounded-lg font-semibold hover:from-purple-700 hover:to-blue-700 transition shadow-md text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Create Free Account
                        </a>
                        <div class="flex items-center gap-4 text-xs text-gray-600">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Save customers
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Track payments
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Invoice history
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endguest

    <!-- Invoice Form -->
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <form method="POST" action="{{ route('public-invoice.preview') }}" id="invoiceForm" enctype="multipart/form-data">
            @csrf

            <!-- From Section -->
            <div class="bg-white rounded-xl p-6 shadow-lg border-2 border-purple-100 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">From (Your Business)</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Business Name *</label>
                        <input type="text" name="from_name" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="Your Business Name" value="{{ old('from_name') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                        <input type="email" name="from_email"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="business@example.com" value="{{ old('from_email') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Phone</label>
                        <input type="text" name="from_phone"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="+234 800 000 0000" value="{{ old('from_phone') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Address</label>
                        <input type="text" name="from_address"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="Business Address" value="{{ old('from_address') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Company Logo (Optional)</label>
                        <input type="file" name="company_logo" accept="image/*"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Accepted formats: JPG, PNG, GIF (Max: 2MB)</p>
                    </div>
                </div>

                <h3 class="text-lg font-semibold text-gray-900 mt-6 mb-3">Bank Account Details (Optional)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Bank Name</label>
                        <input type="text" name="from_bank_name" list="bankList"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="Type or select bank name" value="{{ old('from_bank_name') }}">
                        <datalist id="bankList">
                            <option value="Access Bank">
                            <option value="Citibank">
                            <option value="Ecobank">
                            <option value="Fidelity Bank">
                            <option value="First Bank of Nigeria">
                            <option value="First City Monument Bank (FCMB)">
                            <option value="Guaranty Trust Bank (GTBank)">
                            <option value="Heritage Bank">
                            <option value="Keystone Bank">
                            <option value="Polaris Bank">
                            <option value="Providus Bank">
                            <option value="Stanbic IBTC Bank">
                            <option value="Standard Chartered Bank">
                            <option value="Sterling Bank">
                            <option value="Union Bank">
                            <option value="United Bank for Africa (UBA)">
                            <option value="Unity Bank">
                            <option value="Wema Bank">
                            <option value="Zenith Bank">
                            <option value="Kuda Bank">
                            <option value="ALAT by Wema">
                            <option value="VFD Microfinance Bank">
                            <option value="Opay">
                            <option value="PalmPay">
                            <option value="Moniepoint">
                        </datalist>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Account Number</label>
                        <input type="text" name="from_account_number"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="0123456789" value="{{ old('from_account_number') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Name on Account</label>
                        <input type="text" name="from_account_name"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="Account Holder Name" value="{{ old('from_account_name') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Account Type</label>
                        <select name="from_account_type"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">Select Type</option>
                            <option value="Savings" {{ old('from_account_type') == 'Savings' ? 'selected' : '' }}>Savings</option>
                            <option value="Current" {{ old('from_account_type') == 'Current' ? 'selected' : '' }}>Current</option>
                            <option value="Domiciliary" {{ old('from_account_type') == 'Domiciliary' ? 'selected' : '' }}>Domiciliary</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- To Section -->
            <div class="bg-white rounded-xl p-6 shadow-lg border-2 border-purple-100 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">To (Your Customer)</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Customer Name * <span id="recent-customers-label" class="text-xs text-gray-500"></span></label>
                        <input type="text" name="to_name" id="to_name" required list="recentCustomers"
                            onchange="saveRecentCustomer(this.value)"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="Customer Name" value="{{ old('to_name') }}">
                        <datalist id="recentCustomers"></datalist>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                        <input type="email" name="to_email"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="customer@example.com" value="{{ old('to_email') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Phone</label>
                        <input type="text" name="to_phone"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="+234 800 000 0000" value="{{ old('to_phone') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Address</label>
                        <input type="text" name="to_address"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="Customer Address" value="{{ old('to_address') }}">
                    </div>
                </div>
            </div>

            <!-- Invoice Details -->
            <div class="bg-white rounded-xl p-6 shadow-lg border-2 border-purple-100 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Invoice Details</h2>

                <!-- Invoice Number - Full width on mobile -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center justify-between">
                        <span>Invoice Number</span>
                        <button type="button" onclick="autoSuggestInvoiceNumber()" class="text-xs text-purple-600 hover:text-purple-800 font-medium">
                            💡 Auto-suggest
                        </button>
                    </label>
                    <input type="text" name="invoice_number" id="invoice_number"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Click 'Auto-suggest' or type your own" value="{{ old('invoice_number') }}">
                    <p class="text-xs text-gray-500 mt-1" id="invoice-preview"></p>
                </div>

                <!-- Issue Date & Due Date - Side by side on all screens -->
                <div class="grid grid-cols-2 gap-3 sm:gap-4 mb-3">
                    <!-- Issue Date -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Issue Date *</label>
                        <input type="date" name="issue_date" required
                            class="w-full px-3 sm:px-4 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            value="{{ old('issue_date', now()->format('Y-m-d')) }}">
                    </div>

                    <!-- Due Date -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Due Date *</label>
                        <input type="date" name="due_date" id="due_date" required
                            class="w-full px-3 sm:px-4 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}">
                    </div>
                </div>

                <!-- Quick select buttons below both date fields -->
                <div class="flex gap-1.5 sm:gap-2 flex-wrap items-center">
                    <span class="text-xs text-gray-600 font-medium">Quick select:</span>
                    <button type="button" onclick="setDueDate(0)" class="text-xs px-2.5 sm:px-3 py-1.5 bg-gray-100 hover:bg-purple-100 rounded-full transition">Today</button>
                    <button type="button" onclick="setDueDate(7)" class="text-xs px-2.5 sm:px-3 py-1.5 bg-gray-100 hover:bg-purple-100 rounded-full transition">7 days</button>
                    <button type="button" onclick="setDueDate(14)" class="text-xs px-2.5 sm:px-3 py-1.5 bg-gray-100 hover:bg-purple-100 rounded-full transition">14 days</button>
                    <button type="button" onclick="setDueDate(30)" class="text-xs px-2.5 sm:px-3 py-1.5 bg-purple-100 hover:bg-purple-200 rounded-full font-medium transition">30 days</button>
                </div>
            </div>

            <!-- Items Section -->
            <div class="bg-white rounded-xl p-6 shadow-lg border-2 border-purple-100 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-900">Invoice Items</h2>
                    <button type="button" id="addItem"
                        class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition">
                        + Add Item
                    </button>
                </div>

                <div id="itemsContainer">
                    <!-- Initial Item -->
                    <div class="item-row grid grid-cols-12 gap-2 mb-3">
                        <div class="col-span-12 md:col-span-5">
                            <input type="text" name="items[0][description]" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                placeholder="Item description">
                        </div>
                        <div class="col-span-4 md:col-span-2">
                            <input type="number" name="items[0][quantity]" step="0.01" min="0.01" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                placeholder="Qty">
                        </div>
                        <div class="col-span-4 md:col-span-3">
                            <input type="number" name="items[0][unit_price]" step="0.01" min="0" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                placeholder="Unit Price (₦)">
                        </div>
                        <div class="col-span-4 md:col-span-2 flex items-center">
                            <button type="button" class="remove-item text-red-600 hover:text-red-800 px-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Simple Invoice Mode Toggle -->
            <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6 shadow-lg border-2 border-blue-200 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <label for="simpleModeToggle" class="flex items-center cursor-pointer">
                            <div class="relative">
                                <input type="checkbox" id="simpleModeToggle" class="sr-only" onchange="toggleSimpleMode()">
                                <div class="block bg-gray-300 w-14 h-8 rounded-full"></div>
                                <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition"></div>
                            </div>
                            <div class="ml-4">
                                <span class="text-lg font-bold text-gray-900">Simple Invoice (No Tax)</span>
                                <p class="text-sm text-gray-600 mt-1">
                                    Use this if you're not charging VAT or WHT. Perfect for freelancers and small vendors.
                                </p>
                            </div>
                        </label>
                    </div>
                </div>
                <input type="hidden" name="simple_mode" id="simpleModeInput" value="0">
            </div>

            <!-- Tax and Discount Section -->
            <div class="bg-white rounded-xl p-6 shadow-lg border-2 border-purple-100 mb-6" id="taxSection">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Tax & Discount (Optional)</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            VAT (%)
                            <span class="ml-2 text-gray-500 cursor-help" onclick="if(window.KinvoiceAnalytics){window.KinvoiceAnalytics.track('tax_tooltip_opened',{type:'vat'})}" title="Value Added Tax - Standard rate in Nigeria is 7.5%. This will be ADDED to your subtotal. Don't need tax? Toggle 'Simple Invoice' above.">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </span>
                        </label>
                        <input type="number" name="vat_percentage" step="0.01" min="0" max="100"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="7.5" value="{{ old('vat_percentage', '7.5') }}">
                        <p class="text-xs text-gray-500 mt-1">Standard rate: 7.5% (added to total)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            WHT (%)
                            <span class="ml-2 text-gray-500 cursor-help" onclick="if(window.KinvoiceAnalytics){window.KinvoiceAnalytics.track('tax_tooltip_opened',{type:'wht'})}" title="Withholding Tax - Your customer may deduct this before paying (usually 5% or 10%). This will be SUBTRACTED from your total. Don't need tax? Toggle 'Simple Invoice' above.">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </span>
                        </label>
                        <input type="number" name="wht_percentage" step="0.01" min="0" max="100"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="0" value="{{ old('wht_percentage', '0') }}">
                        <p class="text-xs text-gray-500 mt-1">Common: 5% or 10% (deducted from total)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            Discount (%)
                            <span class="ml-2 text-gray-500 cursor-help" title="Percentage discount to apply to your customer. This will be SUBTRACTED from your total.">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </span>
                        </label>
                        <input type="number" name="discount_percentage" step="0.01" min="0" max="100"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="0" value="{{ old('discount_percentage', '0') }}">
                        <p class="text-xs text-gray-500 mt-1">Optional discount for your customer</p>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="bg-white rounded-xl p-6 shadow-lg border-2 border-purple-100 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Notes (Optional)</h2>
                <textarea name="notes" rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="Payment terms, thank you message, etc.">{{ old('notes') }}</textarea>
            </div>

            <!-- Payment Fee Notice -->
            <div class="mb-6">
                <p class="text-center text-sm text-gray-500">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Online payments via "Pay Now" button include processing fees.
                    <a href="{{ route('fees') }}" target="_blank" class="text-blue-600 hover:text-blue-800 underline">View fee schedule</a>
                </p>
            </div>

            <!-- Submit Button -->
            <div class="text-center">
                <!-- Loss Awareness Microcopy -->
                @guest
                <div class="mb-4 inline-block">
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg max-w-lg mx-auto">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-yellow-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm text-yellow-800">
                                <strong class="font-semibold">This invoice will not be saved</strong> unless you create an account.
                                <span class="text-yellow-700">You'll be able to save it after generation.</span>
                            </p>
                        </div>
                    </div>
                </div>
                @endguest

                <button type="submit"
                    class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-12 py-4 rounded-lg text-lg font-semibold hover:from-purple-700 hover:to-blue-700 transition transform hover:scale-105 shadow-lg">
                    Generate Invoice Preview
                </button>

                <!-- Quick Signup Link -->
                @guest
                <p class="mt-3 text-sm text-gray-600">
                    Already have an account?
                    <a href="{{ route('filament.app.auth.login') }}" class="text-purple-600 hover:text-purple-800 font-semibold">
                        Log in to save automatically
                    </a>
                </p>
                @endguest
            </div>
        </form>
    </div>

    <!-- JavaScript for Dynamic Items -->
    <script>
        // Track GA4 event: invoice_started
        if (typeof gtag !== 'undefined') {
            gtag('event', 'invoice_started', {
                event_category: 'invoice',
                event_label: 'invoice_creation_started'
            });
        }

        let itemCount = 1;

        document.getElementById('addItem').addEventListener('click', function() {
            const container = document.getElementById('itemsContainer');
            const newItem = document.createElement('div');
            newItem.className = 'item-row grid grid-cols-12 gap-2 mb-3';
            newItem.innerHTML = `
                <div class="col-span-12 md:col-span-5">
                    <input type="text" name="items[${itemCount}][description]" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                        placeholder="Item description">
                </div>
                <div class="col-span-4 md:col-span-2">
                    <input type="number" name="items[${itemCount}][quantity]" step="0.01" min="0.01" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                        placeholder="Qty">
                </div>
                <div class="col-span-4 md:col-span-3">
                    <input type="number" name="items[${itemCount}][unit_price]" step="0.01" min="0" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                        placeholder="Unit Price (₦)">
                </div>
                <div class="col-span-4 md:col-span-2 flex items-center">
                    <button type="button" class="remove-item text-red-600 hover:text-red-800 px-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            `;
            container.appendChild(newItem);
            itemCount++;
        });

        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-item')) {
                const rows = document.querySelectorAll('.item-row');
                if (rows.length > 1) {
                    e.target.closest('.item-row').remove();
                } else {
                    alert('You must have at least one item!');
                }
            }
        });

        // Simple Invoice Mode Toggle Logic
        function toggleSimpleMode() {
            const checkbox = document.getElementById('simpleModeToggle');
            const hiddenInput = document.getElementById('simpleModeInput');
            const taxSection = document.getElementById('taxSection');
            const toggle = checkbox.parentElement;

            if (checkbox.checked) {
                // Enable simple mode
                hiddenInput.value = '1';
                toggle.querySelector('.block').classList.remove('bg-gray-300');
                toggle.querySelector('.block').classList.add('bg-green-500');
                toggle.querySelector('.dot').style.transform = 'translateX(1.5rem)';

                // Hide tax section
                taxSection.style.display = 'none';

                // Clear VAT/WHT values
                document.querySelector('[name="vat_percentage"]').value = '0';
                document.querySelector('[name="wht_percentage"]').value = '0';

                // Save to localStorage
                localStorage.setItem('simpleInvoiceMode', 'true');

                // Track analytics event
                if (window.KinvoiceAnalytics) {
                    window.KinvoiceAnalytics.track('invoice_simple_mode_toggled', { enabled: true });
                }
            } else {
                // Disable simple mode
                hiddenInput.value = '0';
                toggle.querySelector('.block').classList.add('bg-gray-300');
                toggle.querySelector('.block').classList.remove('bg-green-500');
                toggle.querySelector('.dot').style.transform = 'translateX(0)';

                // Show tax section
                taxSection.style.display = 'block';

                // Restore default VAT
                document.querySelector('[name="vat_percentage"]').value = '7.5';

                // Save to localStorage
                localStorage.setItem('simpleInvoiceMode', 'false');

                // Track analytics event
                if (window.KinvoiceAnalytics) {
                    window.KinvoiceAnalytics.track('invoice_simple_mode_toggled', { enabled: false });
                }
            }
        }

        // Restore simple mode from localStorage on page load
        window.addEventListener('DOMContentLoaded', function() {
            const savedMode = localStorage.getItem('simpleInvoiceMode');
            if (savedMode === 'true') {
                document.getElementById('simpleModeToggle').checked = true;
                toggleSimpleMode();
            }

            // Track invoice generator page view
            if (window.KinvoiceAnalytics) {
                window.KinvoiceAnalytics.track('invoice_generator_viewed');
            }
        });

        // Invoice Number Auto-Suggestion (A3)
        function autoSuggestInvoiceNumber() {
            const input = document.getElementById('invoice_number');
            const preview = document.getElementById('invoice-preview');

            // Check if user is logged in
            const isLoggedIn = {{ auth()->check() ? 'true' : 'false' }};
            const mode = isLoggedIn ? 'logged_in' : 'anonymous';

            // Generate invoice number
            const year = new Date().getFullYear();
            const sequence = getNextInvoiceSequence();
            const invoiceNumber = `INV-${year}-${String(sequence).padStart(4, '0')}`;

            // Set the input value
            input.value = invoiceNumber;

            // Show preview
            preview.textContent = `✓ Invoice number set to: ${invoiceNumber}`;
            preview.classList.add('text-green-600');

            // Track analytics
            if (window.KinvoiceAnalytics) {
                window.KinvoiceAnalytics.track('invoice_number_autofilled', { mode: mode });
            }

            // Clear preview after 3 seconds
            setTimeout(function() {
                preview.textContent = '';
            }, 3000);
        }

        function getNextInvoiceSequence() {
            const storageKey = 'kinvoice_sequence';

            try {
                // Get current sequence from localStorage
                const currentSequence = parseInt(localStorage.getItem(storageKey) || '0');

                // Increment for next invoice
                const nextSequence = currentSequence + 1;

                // Save updated sequence
                localStorage.setItem(storageKey, nextSequence.toString());

                return nextSequence;
            } catch (e) {
                // If localStorage is not available, use a timestamp-based fallback
                return Math.floor(Date.now() / 1000) % 10000;
            }
        }

        // Auto-suggest on page load if field is empty
        window.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('invoice_number');
            if (!input.value || input.value === '') {
                autoSuggestInvoiceNumber();
            }
            loadRecentCustomers();
        });

        // Recent Customers & Items (B1)
        function loadRecentCustomers() {
            try {
                const recent = JSON.parse(localStorage.getItem('kinvoice_recent_customers') || '[]');
                const datalist = document.getElementById('recentCustomers');
                const label = document.getElementById('recent-customers-label');

                if (recent.length > 0) {
                    label.textContent = `(${recent.length} recent)`;
                    datalist.innerHTML = recent.map(c => `<option value="${c}">`).join('');
                }
            } catch (e) {}
        }

        function saveRecentCustomer(name) {
            if (!name || name.length < 2) return;
            try {
                let recent = JSON.parse(localStorage.getItem('kinvoice_recent_customers') || '[]');
                recent = recent.filter(c => c !== name);
                recent.unshift(name);
                recent = recent.slice(0, 5);
                localStorage.setItem('kinvoice_recent_customers', JSON.stringify(recent));
                if (window.KinvoiceAnalytics) {
                    window.KinvoiceAnalytics.track('recent_customer_selected');
                }
            } catch (e) {}
        }

        // Due Date Chips (B2)
        function setDueDate(days) {
            const input = document.getElementById('due_date');
            const date = new Date();
            date.setDate(date.getDate() + days);
            input.value = date.toISOString().split('T')[0];
            if (window.KinvoiceAnalytics) {
                window.KinvoiceAnalytics.track('invoice_due_date_chip_selected', { days });
            }
        }
    </script>

    <style>
        /* Toggle switch styling */
        #simpleModeToggle:checked ~ .block {
            background-color: #10b981;
        }
        #simpleModeToggle:checked ~ .dot {
            transform: translateX(100%);
        }
        .dot {
            transition: transform 0.3s ease;
        }
    </style>
</x-layout>
