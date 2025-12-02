<x-layout>
    <x-slot name="title">Free Invoice Generator - Khan Invoice</x-slot>

    <!-- Hero Section -->
    <div class="gradient-bg text-white py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Free Invoice Generator</h1>
            <p class="text-xl text-purple-100">
                Create professional Nigerian invoices in seconds - No signup required
            </p>
        </div>
    </div>

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
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Customer Name *</label>
                        <input type="text" name="to_name" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="Customer Name" value="{{ old('to_name') }}">
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
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Invoice Number</label>
                        <input type="text" name="invoice_number"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="Auto-generated" value="{{ old('invoice_number') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Issue Date *</label>
                        <input type="date" name="issue_date" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            value="{{ old('issue_date', now()->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Due Date *</label>
                        <input type="date" name="due_date" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}">
                    </div>
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

            <!-- Tax and Discount Section -->
            <div class="bg-white rounded-xl p-6 shadow-lg border-2 border-purple-100 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Tax & Discount (Optional)</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">VAT (%)</label>
                        <input type="number" name="vat_percentage" step="0.01" min="0" max="100"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="7.5" value="{{ old('vat_percentage', '7.5') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">WHT (%)</label>
                        <input type="number" name="wht_percentage" step="0.01" min="0" max="100"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="0" value="{{ old('wht_percentage', '0') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Discount (%)</label>
                        <input type="number" name="discount_percentage" step="0.01" min="0" max="100"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="0" value="{{ old('discount_percentage', '0') }}">
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

            <!-- Submit Button -->
            <div class="text-center">
                <button type="submit"
                    class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-12 py-4 rounded-lg text-lg font-semibold hover:from-purple-700 hover:to-blue-700 transition transform hover:scale-105">
                    Generate Invoice Preview
                </button>
            </div>
        </form>
    </div>

    <!-- JavaScript for Dynamic Items -->
    <script>
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
    </script>
</x-layout>
