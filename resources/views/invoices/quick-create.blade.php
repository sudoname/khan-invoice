<x-filament-panels::page>
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Quick Invoice</h1>
            <p class="text-gray-600 mt-2">Create a professional invoice in under 2 minutes. Saves automatically to your account.</p>
        </div>

        <!-- Error Messages -->
        @if($errors->any())
        <div class="bg-red-50 border-2 border-red-300 rounded-xl p-4 mb-6">
            <h3 class="font-bold text-red-900 mb-2">Please fix the following errors:</h3>
            <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Form -->
        <form method="POST" action="{{ route('app.invoices.quick.store') }}" id="quickInvoiceForm">
            @csrf

            <!-- Simple Mode Toggle -->
            <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6 shadow-sm border-2 border-blue-200 mb-6">
                <label for="simpleModeToggle" class="flex items-center cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" id="simpleModeToggle" class="sr-only" onchange="toggleSimpleMode()">
                        <div class="block bg-gray-300 w-14 h-8 rounded-full"></div>
                        <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition"></div>
                    </div>
                    <div class="ml-4">
                        <span class="text-lg font-bold text-gray-900">Simple Mode (No Tax)</span>
                        <p class="text-sm text-gray-600 mt-1">
                            Hide tax, discounts, and advanced options for quick invoicing
                        </p>
                    </div>
                </label>
                <input type="hidden" name="simple_mode" id="simpleModeInput" value="0">
            </div>

            <!-- Customer Section -->
            <div class="bg-white rounded-xl p-6 shadow-lg border-2 border-blue-100 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Bill To (Customer)</h2>

                @if($recentCustomers->count() > 0)
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Select Recent Customer</label>
                    <select name="customer_id" id="customerSelect" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="">-- Or enter new customer below --</option>
                        @foreach($recentCustomers as $customer)
                        <option value="{{ $customer->id }}"
                                data-name="{{ $customer->name }}"
                                data-email="{{ $customer->email }}"
                                data-phone="{{ $customer->phone }}"
                                data-address="{{ $customer->address }}">
                            {{ $customer->name }} @if($customer->email) ({{ $customer->email }}) @endif
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Customer Name *</label>
                        <input type="text" name="customer_name" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                            placeholder="Customer Name" value="{{ old('customer_name') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                        <input type="email" name="customer_email"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                            placeholder="customer@example.com" value="{{ old('customer_email') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Phone</label>
                        <input type="text" name="customer_phone"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                            placeholder="+234..." value="{{ old('customer_phone') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Address</label>
                        <input type="text" name="customer_address"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                            placeholder="Customer Address" value="{{ old('customer_address') }}">
                    </div>
                </div>
            </div>

            <!-- Invoice Details -->
            <div class="bg-white rounded-xl p-6 shadow-lg border-2 border-purple-100 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Invoice Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Invoice Number *</label>
                        <input type="text" name="invoice_number" required readonly
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50"
                            value="{{ $invoiceNumber }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Issue Date *</label>
                        <input type="date" name="issue_date" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                            value="{{ old('issue_date', now()->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Due Date *</label>
                        <input type="date" name="due_date" id="due_date" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                            value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}">
                    </div>
                </div>
                <!-- Quick select buttons -->
                <div class="flex gap-2 flex-wrap items-center">
                    <span class="text-xs text-gray-600 font-medium">Quick select:</span>
                    <button type="button" onclick="setDueDate(0)" class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-purple-100 rounded-full transition">Today</button>
                    <button type="button" onclick="setDueDate(7)" class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-purple-100 rounded-full transition">7 days</button>
                    <button type="button" onclick="setDueDate(14)" class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-purple-100 rounded-full transition">14 days</button>
                    <button type="button" onclick="setDueDate(30)" class="text-xs px-3 py-1.5 bg-purple-100 hover:bg-purple-200 rounded-full font-medium transition">30 days</button>
                </div>
            </div>

            <!-- Line Items -->
            <div class="bg-white rounded-xl p-6 shadow-lg border-2 border-green-100 mb-6">
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

            <!-- Tax Section (hidden in simple mode) -->
            <div id="taxSection" class="bg-white rounded-xl p-6 shadow-lg border-2 border-yellow-100 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Tax & Discounts (Optional)</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">VAT Rate (%)</label>
                        <input type="number" name="vat_rate" step="0.01" min="0" max="100" value="7.5"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <p class="text-xs text-gray-500 mt-1">Standard: 7.5% (added to total)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">WHT Rate (%)</label>
                        <input type="number" name="wht_rate" step="0.01" min="0" max="100" value="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <p class="text-xs text-gray-500 mt-1">Common: 5% or 10% (deducted)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Discount (₦)</label>
                        <input type="number" name="discount_total" step="0.01" min="0" value="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <p class="text-xs text-gray-500 mt-1">Fixed amount discount</p>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="bg-white rounded-xl p-6 shadow-lg border-2 border-gray-100 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Notes (Optional)</h2>
                <textarea name="notes" rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                    placeholder="Any additional notes or payment terms...">{{ old('notes') }}</textarea>
            </div>

            <!-- Business Profile (Hidden) -->
            @if($lastBusinessProfile)
            <input type="hidden" name="business_profile_id" value="{{ $lastBusinessProfile->id }}">
            @endif

            <!-- Submit -->
            <div class="flex justify-between items-center">
                <a href="{{ route('filament.app.pages.dashboard') }}" class="text-gray-600 hover:text-gray-800">
                    ← Back to Dashboard
                </a>
                <button type="submit"
                    class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-8 py-4 rounded-lg font-bold text-lg hover:from-purple-700 hover:to-blue-700 transition shadow-lg">
                    Create Invoice
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        let itemCount = 1;

        // Simple mode toggle
        function toggleSimpleMode() {
            const toggle = document.getElementById('simpleModeToggle');
            const input = document.getElementById('simpleModeInput');
            const taxSection = document.getElementById('taxSection');
            const toggleSwitch = toggle.parentElement.querySelector('.dot');
            const toggleBg = toggle.parentElement.querySelector('.block');

            if (toggle.checked) {
                input.value = '1';
                taxSection.style.display = 'none';
                toggleSwitch.style.transform = 'translateX(1.5rem)';
                toggleBg.classList.remove('bg-gray-300');
                toggleBg.classList.add('bg-purple-600');

                // Track analytics
                if (window.KinvoiceAnalytics) {
                    window.KinvoiceAnalytics.track('invoice_simple_mode_toggled', { mode: 'simple', context: 'quick' });
                }
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'simple_mode_enabled', { invoice_type: 'quick' });
                }
            } else {
                input.value = '0';
                taxSection.style.display = 'block';
                toggleSwitch.style.transform = 'translateX(0)';
                toggleBg.classList.remove('bg-purple-600');
                toggleBg.classList.add('bg-gray-300');

                // Track analytics
                if (window.KinvoiceAnalytics) {
                    window.KinvoiceAnalytics.track('invoice_simple_mode_toggled', { mode: 'advanced', context: 'quick' });
                }
            }
        }

        // Add line item
        document.getElementById('addItem').addEventListener('click', function() {
            const container = document.getElementById('itemsContainer');
            const newRow = document.createElement('div');
            newRow.className = 'item-row grid grid-cols-12 gap-2 mb-3';
            newRow.innerHTML = `
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
            container.appendChild(newRow);
            itemCount++;

            // Track analytics
            if (window.KinvoiceAnalytics) {
                window.KinvoiceAnalytics.track('quick_invoice_item_added', { items_count: itemCount });
            }
        });

        // Remove item
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-item')) {
                const rows = document.querySelectorAll('.item-row');
                if (rows.length > 1) {
                    e.target.closest('.item-row').remove();
                } else {
                    alert('You must have at least one item.');
                }
            }
        });

        // Customer select autofill
        const customerSelect = document.getElementById('customerSelect');
        if (customerSelect) {
            customerSelect.addEventListener('change', function(e) {
                const option = e.target.selectedOptions[0];
                if (option.value) {
                    document.querySelector('[name="customer_name"]').value = option.dataset.name || '';
                    document.querySelector('[name="customer_email"]').value = option.dataset.email || '';
                    document.querySelector('[name="customer_phone"]').value = option.dataset.phone || '';
                    document.querySelector('[name="customer_address"]').value = option.dataset.address || '';

                    // Track analytics
                    if (window.KinvoiceAnalytics) {
                        window.KinvoiceAnalytics.track('quick_invoice_customer_selected', {
                            selection_type: 'existing'
                        });
                    }
                }
            });
        }

        // Due date quick select
        function setDueDate(days) {
            const today = new Date();
            today.setDate(today.getDate() + days);
            const dateStr = today.toISOString().split('T')[0];
            document.getElementById('due_date').value = dateStr;

            // Track analytics
            if (window.KinvoiceAnalytics) {
                window.KinvoiceAnalytics.track('invoice_due_date_chip_selected', { days });
            }
        }

        // Track page view
        if (window.KinvoiceAnalytics) {
            const urlParams = new URLSearchParams(window.location.search);
            window.KinvoiceAnalytics.track('quick_invoice_started', {
                entry_point: urlParams.get('from') || 'direct'
            });
        }
        if (typeof gtag !== 'undefined') {
            gtag('event', 'quick_invoice_started');
        }

        // Form submission tracking
        document.getElementById('quickInvoiceForm').addEventListener('submit', function() {
            const itemsCount = document.querySelectorAll('.item-row').length;
            const simpleMode = document.getElementById('simpleModeInput').value === '1';
            const hasVat = !simpleMode && parseFloat(document.querySelector('[name="vat_rate"]').value) > 0;
            const hasDiscount = parseFloat(document.querySelector('[name="discount_total"]').value) > 0;

            if (window.KinvoiceAnalytics) {
                window.KinvoiceAnalytics.track('quick_invoice_submitted', {
                    items_count: itemsCount,
                    has_tax: hasVat,
                    has_discount: hasDiscount,
                    simple_mode: simpleMode
                });
            }
            if (typeof gtag !== 'undefined') {
                gtag('event', 'invoice_submitted', {
                    invoice_type: 'quick',
                    items_count: itemsCount
                });
            }
        });
    </script>
    @endpush
</x-filament-panels::page>
