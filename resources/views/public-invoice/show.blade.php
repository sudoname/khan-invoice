<x-layout>
    <x-slot name="title">Invoice {{ $invoice->invoice_number }} - Khan Invoice</x-slot>

    <style>
        /* Mobile optimizations */
        @media (max-width: 768px) {
            body {
                font-size: 14px;
            }
            .invoice-container {
                padding: 8px !important;
            }
            table {
                font-size: 12px;
            }
        }

        /* Print styles - hide UI elements */
        @media print {
            nav, .action-buttons, button, a.button {
                display: none !important;
            }
            .invoice-container {
                box-shadow: none !important;
                border: none !important;
            }
        }

        /* Ensure text is readable on mobile */
        body {
            -webkit-text-size-adjust: 100%;
        }
    </style>

    <!-- Paystack Inline JS -->
    <script src="https://js.paystack.co/v1/inline.js"></script>

    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Invoice {{ $invoice->invoice_number }}</h1>
                    <p class="text-purple-100 mt-2">View and pay invoice</p>
                </div>
                @if($invoice->payment_status === 'paid')
                    <div class="bg-green-500 text-white px-4 py-2 rounded-lg font-bold text-sm">
                        ✓ PAID
                    </div>
                @else
                    <div class="bg-yellow-500 text-white px-4 py-2 rounded-lg font-bold text-sm">
                        UNPAID
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Preview and Actions -->
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Action Buttons -->
        <div class="action-buttons grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 mb-6">
            <!-- Print -->
            <button onclick="window.print()" class="bg-blue-600 text-white px-3 py-3 rounded-lg font-semibold hover:bg-blue-700 transition flex items-center justify-center gap-2 text-xs sm:text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                <span class="hidden sm:inline">Print</span>
            </button>

            <!-- Download -->
            <a href="{{ route('public-invoice.download', $invoice->public_id) }}"
                download="invoice-{{ $invoice->invoice_number }}.pdf"
                target="_blank"
                onclick="handleDownload(event, this.href)"
                class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-3 py-3 rounded-lg font-semibold hover:from-purple-700 hover:to-blue-700 transition flex items-center justify-center gap-2 text-xs sm:text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <span class="hidden sm:inline">Download</span>
            </a>

            <!-- Share -->
            <button onclick="shareWhatsApp()" class="bg-green-600 text-white px-3 py-3 rounded-lg font-semibold hover:bg-green-700 transition flex items-center justify-center gap-2 text-xs sm:text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                </svg>
                <span class="hidden sm:inline">Share</span>
            </button>

            <!-- Pay Now -->
            <button onclick="openPaymentModal()" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-3 py-3 rounded-lg font-semibold hover:from-green-600 hover:to-green-700 transition flex items-center justify-center gap-2 text-xs sm:text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
                <span class="hidden sm:inline">Pay</span>
            </button>
        </div>

        <!-- Invoice Preview -->
        <div class="invoice-container bg-white rounded-xl shadow-2xl overflow-hidden border-2 border-gray-200">
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-center sm:text-left">
                        <h1 class="text-2xl sm:text-3xl font-bold">INVOICE</h1>
                        <p class="text-purple-100 mt-1 text-sm sm:text-base">{{ $invoice->invoice_number }}</p>
                    </div>

                    <!-- Company Logo - Centered -->
                    @if($invoice->company_logo)
                    <div class="flex-shrink-0">
                        <img src="{{ asset('storage/' . $invoice->company_logo) }}" alt="Company Logo" class="mx-auto" style="max-width: 150px; max-height: 80px; object-fit: contain;">
                    </div>
                    @endif

                    <div class="text-center sm:text-right">
                        <p class="text-xs sm:text-sm">Issue Date: {{ $invoice->issue_date->format('M d, Y') }}</p>
                        <p class="text-xs sm:text-sm">Due Date: {{ $invoice->due_date->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>

            <div class="p-4 sm:p-6 md:p-8">
            <!-- From/To Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8 mb-6 sm:mb-8">
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 mb-2">FROM</h3>
                    <div class="text-gray-900">
                        <p class="font-bold text-lg">{{ $invoice->from_name }}</p>
                        @if($invoice->from_email)
                            <p class="text-sm">{{ $invoice->from_email }}</p>
                        @endif
                        @if($invoice->from_phone)
                            <p class="text-sm">{{ $invoice->from_phone }}</p>
                        @endif
                        @if($invoice->from_address)
                            <p class="text-sm">{{ $invoice->from_address }}</p>
                        @endif
                        @if($invoice->from_bank_name || $invoice->from_account_number)
                            <div class="mt-3 pt-3 border-t border-gray-200">
                                <p class="text-xs font-semibold text-gray-500 mb-1">BANK DETAILS</p>
                                @if($invoice->from_account_number)
                                    <p class="text-sm"><span class="font-semibold">Account Number:</span> {{ $invoice->from_account_number }}</p>
                                @endif
                                @if($invoice->from_account_name)
                                    <p class="text-sm"><span class="font-semibold">Name on Account:</span> {{ $invoice->from_account_name }}</p>
                                @endif
                                @if($invoice->from_bank_name)
                                    <p class="text-sm"><span class="font-semibold">Bank:</span> {{ $invoice->from_bank_name }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 mb-2">BILL TO</h3>
                    <div class="text-gray-900">
                        <p class="font-bold text-lg">{{ $invoice->to_name }}</p>
                        @if($invoice->to_email)
                            <p class="text-sm">{{ $invoice->to_email }}</p>
                        @endif
                        @if($invoice->to_phone)
                            <p class="text-sm">{{ $invoice->to_phone }}</p>
                        @endif
                        @if($invoice->to_address)
                            <p class="text-sm">{{ $invoice->to_address }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="mb-8 overflow-x-auto -mx-4 sm:mx-0">
                <div class="inline-block min-w-full align-middle">
                    <table class="w-full">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="text-left p-2 sm:p-3 text-xs sm:text-sm font-semibold text-gray-700 whitespace-nowrap">Description</th>
                                <th class="text-center p-2 sm:p-3 text-xs sm:text-sm font-semibold text-gray-700 whitespace-nowrap">Qty</th>
                                <th class="text-right p-2 sm:p-3 text-xs sm:text-sm font-semibold text-gray-700 whitespace-nowrap">Unit Price</th>
                                <th class="text-right p-2 sm:p-3 text-xs sm:text-sm font-semibold text-gray-700 whitespace-nowrap">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $item)
                            <tr class="border-b border-gray-200">
                                <td class="p-2 sm:p-3 text-xs sm:text-sm text-gray-900">{{ $item['description'] }}</td>
                                <td class="p-2 sm:p-3 text-xs sm:text-sm text-gray-900 text-center whitespace-nowrap">{{ number_format($item['quantity'], 2) }}</td>
                                <td class="p-2 sm:p-3 text-xs sm:text-sm text-gray-900 text-right whitespace-nowrap">₦{{ number_format($item['unit_price'], 2) }}</td>
                                <td class="p-2 sm:p-3 text-xs sm:text-sm text-gray-900 text-right whitespace-nowrap">₦{{ number_format($item['total'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Totals -->
            <div class="flex justify-end mb-8">
                <div class="w-full md:w-1/2">
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span class="text-gray-700">Subtotal:</span>
                        <span class="font-semibold">₦{{ number_format($invoice->subtotal, 2) }}</span>
                    </div>
                    @if($invoice->vat_percentage > 0)
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span class="text-gray-700">VAT ({{ number_format($invoice->vat_percentage, 2) }}%):</span>
                        <span class="font-semibold text-red-600">+₦{{ number_format($invoice->vat_amount, 2) }}</span>
                    </div>
                    @endif
                    @if($invoice->wht_percentage > 0)
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span class="text-gray-700">WHT ({{ number_format($invoice->wht_percentage, 2) }}%):</span>
                        <span class="font-semibold text-red-600">-₦{{ number_format($invoice->wht_amount, 2) }}</span>
                    </div>
                    @endif
                    @if($invoice->discount_percentage > 0)
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span class="text-gray-700">Discount ({{ number_format($invoice->discount_percentage, 2) }}%):</span>
                        <span class="font-semibold text-green-600">-₦{{ number_format($invoice->discount_amount, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between py-3 bg-gradient-to-r from-purple-50 to-blue-50 px-4 rounded-lg mt-2">
                        <span class="text-lg font-bold text-gray-900">Total:</span>
                        <span class="text-xl font-bold text-purple-600">₦{{ number_format($invoice->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            @if($invoice->notes)
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Notes:</h3>
                <p class="text-sm text-gray-600">{{ $invoice->notes }}</p>
            </div>
            @endif
            </div>
        </div>

        <!-- CTA Section -->
        <div class="mt-8 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-xl p-6 sm:p-8 text-center">
            <h3 class="text-xl sm:text-2xl font-bold mb-2">Want to manage invoices professionally?</h3>
            <p class="text-sm sm:text-base text-purple-100 mb-4">Track payments, send invoices, and manage your business with Khan Invoice</p>
            <a href="{{ route('filament.app.auth.register') }}"
                class="inline-block bg-white text-purple-600 px-6 sm:px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition text-sm sm:text-base">
                Create Free Account
            </a>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold text-gray-900">Payment Information</h3>
                <button onclick="closePaymentModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="paymentForm">
                <div class="space-y-4">
                    <!-- Amount Display (Customer pays only invoice amount) -->
                    @php
                        $invoiceAmount = $invoice->total_amount;
                        $netCalculation = \App\Models\PaymentSetting::calculateNetAmountReceived($invoiceAmount);
                    @endphp
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm text-gray-700">
                                <span>Invoice Amount</span>
                                <span class="font-semibold">₦{{ number_format($invoiceAmount, 2) }}</span>
                            </div>
                            <div class="border-t border-purple-200 pt-2 flex justify-between">
                                <span class="text-sm font-medium text-gray-700">Total to Pay</span>
                                <span class="text-2xl font-bold text-purple-600">₦{{ number_format($invoiceAmount, 2) }}</span>
                            </div>
                            <div class="text-xs text-gray-500 text-center mt-2">
                                Processing fees are absorbed by the merchant
                            </div>
                        </div>
                    </div>

                    <!-- Payer Information -->
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">Payer Information</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payer Name *</label>
                                <input type="text" id="payer_name" required value="{{ $invoice->to_name }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payer Email *</label>
                                <input type="email" id="payer_email" required value="{{ $invoice->to_email }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-green-600 hover:to-green-700 transition">
                        Proceed to Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Detect if running in mobile app WebView
        function isInAppBrowser() {
            const ua = navigator.userAgent || navigator.vendor || window.opera;
            return (ua.indexOf('wv') > -1) || (ua.indexOf('WebView') > -1);
        }

        // Handle PDF download
        function handleDownload(event, url) {
            // For mobile devices, just let the default behavior work
            // The download attribute and target="_blank" should handle it
            console.log('Downloading PDF from:', url);

            // Show a brief message
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            if (isMobile) {
                // Give feedback that download is starting
                setTimeout(() => {
                    console.log('Download should have started');
                }, 500);
            }

            // Let the default link behavior proceed
            return true;
        }

        // WhatsApp Share Function - Mobile friendly
        function shareWhatsApp() {
            const url = "{{ route('public-invoice.show', $invoice->public_id) }}";
            const invoiceText = `Invoice {{ $invoice->invoice_number }} - Amount: ₦{{ number_format($invoice->total_amount, 2) }}`;
            const message = `*INVOICE: {{ $invoice->invoice_number }}*\n\n` +
                           `From: {{ $invoice->from_name }}\n` +
                           `To: {{ $invoice->to_name }}\n` +
                           `Amount: ₦{{ number_format($invoice->total_amount, 2) }}\n` +
                           `Due Date: {{ $invoice->due_date->format('M d, Y') }}\n\n` +
                           `View and pay invoice: ${url}`;

            // Try Web Share API first (works best in mobile apps/browsers)
            if (navigator.share) {
                navigator.share({
                    title: invoiceText,
                    text: message
                }).then(() => {
                    console.log('Share successful');
                }).catch((error) => {
                    console.log('Share failed or cancelled:', error);
                    // Only fallback if not cancelled
                    if (error.name !== 'AbortError') {
                        fallbackToWhatsApp(message);
                    }
                });
            } else {
                // Fallback for browsers without Web Share API
                fallbackToWhatsApp(message);
            }
        }

        function fallbackToWhatsApp(message) {
            // Use WhatsApp Web URL
            const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;

            // Try to open in new window/tab
            const newWindow = window.open(whatsappUrl, '_blank');

            // If popup blocked, provide alternative
            if (!newWindow || newWindow.closed || typeof newWindow.closed === 'undefined') {
                alert('Please allow popups to share via WhatsApp, or copy the link manually.');
            }
        }

        // Payment Modal Functions
        function openPaymentModal() {
            const paymentStatus = '{{ $invoice->payment_status }}';

            if (paymentStatus === 'paid') {
                if (confirm('⚠️ This invoice appears to have been paid already.\n\nPaid on: {{ $invoice->paid_at ? $invoice->paid_at->format("M d, Y h:i A") : "N/A" }}\nAmount Paid: ₦{{ number_format($invoice->amount_paid ?? 0, 2) }}\n\nDo you still want to make a payment?')) {
                    document.getElementById('paymentModal').classList.remove('hidden');
                }
            } else {
                document.getElementById('paymentModal').classList.remove('hidden');
            }
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
        }

        // Handle Payment Form Submission
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const payerName = document.getElementById('payer_name').value;
            const payerEmail = document.getElementById('payer_email').value;

            // Generate reference for this payment
            const reference = 'KI_PUBLIC_{{ $invoice->public_id }}_' + Date.now();

            // Customer pays only invoice amount (business absorbs fees)
            const invoiceAmount = {{ $invoice->total_amount }};
            const netCalculation = {!! json_encode($netCalculation) !!};

            // Initialize Paystack payment
            const handler = PaystackPop.setup({
                key: '{{ config("services.paystack.public_key") }}',
                email: payerEmail,
                amount: Math.round(invoiceAmount * 100), // Customer pays ONLY invoice amount (in kobo)
                currency: 'NGN',
                ref: reference,
                @if($invoice->paystack_subaccount_code)
                subaccount: '{{ $invoice->paystack_subaccount_code }}',
                transaction_charge: Math.round(netCalculation.service_charge * 100), // Platform keeps service charge (in kobo)
                bearer: 'account', // Subaccount (business) bears the charge - deducted from their portion
                @endif
                metadata: {
                    invoice_id: '{{ $invoice->public_id }}',
                    invoice_number: "{{ $invoice->invoice_number }}",
                    invoice_amount: invoiceAmount.toFixed(2),
                    customer_pays: invoiceAmount.toFixed(2),
                    paystack_fee: netCalculation.paystack_fee.toFixed(2),
                    service_charge: netCalculation.service_charge.toFixed(2),
                    total_fees: netCalculation.total_fees.toFixed(2),
                    net_amount_received: netCalculation.net_amount_received.toFixed(2),
                    fee_model: 'business_absorbs_fees',
                    payer_name: payerName,
                    receiver_name: "{{ $invoice->from_name }}",
                    receiver_email: "{{ $invoice->from_email ?? '' }}",
                    @if($invoice->from_bank_name && $invoice->from_account_number)
                    receiver_bank_name: "{{ $invoice->from_bank_name }}",
                    receiver_account_number: "{{ $invoice->from_account_number }}",
                    receiver_account_name: "{{ $invoice->from_account_name ?? '' }}",
                    receiver_account_type: "{{ $invoice->from_account_type ?? '' }}",
                    @endif
                },
                callback: function(response) {
                    // Payment successful
                    closePaymentModal();

                    // Show success message (customer paid only invoice amount)
                    alert('Payment Successful!\n\n' +
                          'Transaction Reference: ' + response.reference + '\n' +
                          'Amount Paid: ₦' + invoiceAmount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '\n\n' +
                          'Thank you for your payment!');

                    // Reload page to show updated payment status
                    window.location.reload();
                },
                onClose: function() {
                    // User closed the payment modal
                    alert('Payment was not completed. You can try again.');
                }
            });

            handler.openIframe();
        });

        // Close modal when clicking outside
        document.getElementById('paymentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePaymentModal();
            }
        });
    </script>
</x-layout>
