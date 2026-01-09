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
        <!-- Post-Invoice Conversion Banner (session-based) -->
        @if(session('invoice_just_created'))
        <div id="conversionBanner" class="bg-gradient-to-r from-green-50 to-blue-50 border-2 border-green-400 rounded-xl p-4 sm:p-6 mb-6 shadow-lg" style="display: none;">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center mb-2">
                        <svg class="w-6 h-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-lg sm:text-xl font-bold text-gray-900">Invoice created successfully!</h3>
                    </div>
                    <p class="text-sm sm:text-base text-gray-700 mb-4">
                        Create a free account to <strong>save invoices, reuse customers, and track payments</strong>.
                    </p>
                    <div class="flex flex-col gap-3">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <a href="{{ route('filament.app.auth.register') }}?from=public_invoice&invoice_id={{ $invoice->public_id }}"
                                onclick="if (window.KinvoiceAnalytics) { window.KinvoiceAnalytics.track('post_invoice_signup_prompt_clicked'); }"
                                class="inline-flex items-center justify-center bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-purple-700 hover:to-blue-700 transition shadow-md text-sm sm:text-base">
                                Create Free Account
                            </a>
                            <button onclick="dismissConversionBanner()"
                                class="inline-flex items-center justify-center bg-white text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition border-2 border-gray-300 text-sm sm:text-base">
                                Not now
                            </button>
                        </div>
                        <p class="text-center text-sm text-gray-600">
                            Already have an account?
                            <a href="{{ route('filament.app.auth.login') }}?from=public_invoice&invoice_id={{ $invoice->public_id }}"
                               class="text-purple-600 font-semibold hover:text-purple-800">
                                Login here
                            </a>
                        </p>
                    </div>
                </div>
                <button onclick="dismissConversionBanner()" class="ml-4 text-gray-400 hover:text-gray-600 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <script>
            // Track GA4 event: invoice_generated
            if (typeof gtag !== 'undefined') {
                gtag('event', 'invoice_generated', {
                    event_category: 'invoice',
                    event_label: 'invoice_created'
                });
            }

            // Show conversion banner if not dismissed in this session
            if (!sessionStorage.getItem('conversionBannerDismissed')) {
                document.getElementById('conversionBanner').style.display = 'block';

                // Track analytics event
                if (window.KinvoiceAnalytics) {
                    window.KinvoiceAnalytics.track('post_invoice_signup_prompt_shown');
                }
            }

            function dismissConversionBanner() {
                sessionStorage.setItem('conversionBannerDismissed', 'true');
                document.getElementById('conversionBanner').style.display = 'none';
            }
        </script>
        @endif

        <!-- Status Badge and Actions -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <!-- Status Badge -->
            <div class="flex items-center gap-3">
                <span class="px-4 py-2 rounded-full text-sm font-bold
                    @if($invoice->status_color === 'gray') bg-gray-100 text-gray-800
                    @elseif($invoice->status_color === 'blue') bg-blue-100 text-blue-800
                    @elseif($invoice->status_color === 'yellow') bg-yellow-100 text-yellow-800
                    @elseif($invoice->status_color === 'red') bg-red-100 text-red-800
                    @elseif($invoice->status_color === 'green') bg-green-100 text-green-800
                    @endif
                    ">
                    {{ $invoice->status_label }}
                </span>

                @if($invoice->simple_mode)
                <span class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-semibold border border-blue-200">
                    Simple Invoice
                </span>
                @endif
            </div>

            <!-- Mark as Sent Button -->
            @if($invoice->canMarkAsSent())
            <form action="{{ route('public-invoice.mark-sent', $invoice->public_id) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                    Mark as Sent
                </button>
            </form>
            @endif
        </div>

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

            <!-- Share via WhatsApp -->
            <button type="button" onclick="openWhatsAppTemplateModal()" class="bg-green-600 text-white px-3 py-3 rounded-lg font-semibold hover:bg-green-700 transition flex items-center justify-center gap-2 text-xs sm:text-sm">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                <span class="hidden sm:inline">WhatsApp</span>
            </button>

            <!-- Pay Now -->
            @php
                $canPay = $invoice->payment_status !== 'paid'
                    && $invoice->payment_enabled
                    && (!$invoice->payment_expires_at || $invoice->payment_expires_at > now());
            @endphp

            @if($canPay)
                <button type="button" onclick="openPaymentModal()" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-3 py-3 rounded-lg font-semibold hover:from-green-600 hover:to-green-700 transition flex items-center justify-center gap-2 text-xs sm:text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    <span class="hidden sm:inline">Pay</span>
                </button>
            @else
                <div class="bg-gray-100 text-gray-600 px-3 py-3 rounded-lg font-semibold flex items-center justify-center gap-2 text-xs sm:text-sm cursor-not-allowed" title="@if($invoice->payment_status === 'paid') Already paid @elseif(!$invoice->payment_enabled) Online payment disabled @elseif($invoice->payment_expires_at && $invoice->payment_expires_at <= now()) Payment link expired @endif">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <span class="hidden sm:inline">Pay</span>
                </div>
            @endif
        </div>

        <!-- Payment Disabled/Expired Notice -->
        @if(!$canPay && $invoice->payment_status !== 'paid')
            <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 rounded-r-lg p-4">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <h4 class="text-sm font-bold text-yellow-900 mb-1">Online Payment Not Available</h4>
                        <p class="text-sm text-yellow-800">
                            @if(!$invoice->payment_enabled)
                                The merchant has disabled online payments for this invoice. Please contact them for alternative payment methods.
                            @elseif($invoice->payment_expires_at && $invoice->payment_expires_at <= now())
                                The payment link for this invoice expired on {{ $invoice->payment_expires_at->format('M d, Y \a\t g:i A') }}. Please contact the merchant to reactivate online payments.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Next Step Strip (for logged-out users after generation) -->
        @guest
        <div class="mb-6 bg-gradient-to-r from-green-50 to-blue-50 border-2 border-green-400 rounded-xl p-6 shadow-lg">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <!-- Left Content -->
                <div class="flex-1">
                    <div class="flex items-center mb-3">
                        <div class="bg-green-600 text-white px-3 py-1 rounded-full text-xs font-bold mr-3">
                            NEXT STEP
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Save & track this invoice</h3>
                    </div>
                    <p class="text-sm text-gray-700 mb-4">
                        This invoice is temporary and will be lost. Create a <strong>free account</strong> to unlock:
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <p class="font-semibold text-sm text-gray-900">Save invoice history</p>
                                <p class="text-xs text-gray-600">Never lose an invoice again</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <p class="font-semibold text-sm text-gray-900">Track payment status</p>
                                <p class="text-xs text-gray-600">Know who owes you money</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <p class="font-semibold text-sm text-gray-900">Reuse this customer</p>
                                <p class="text-xs text-gray-600">Save time on future invoices</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right CTA -->
                <div class="flex flex-col gap-3 min-w-fit">
                    <a href="{{ route('filament.app.auth.register') }}?from=public_invoice&invoice_id={{ $invoice->public_id }}"
                       onclick="if (window.KinvoiceAnalytics) { window.KinvoiceAnalytics.track('public_invoice_save_cta_clicked', { invoice_id: '{{ $invoice->public_id }}', context: 'next_step_strip' }); }"
                       class="inline-flex items-center justify-center bg-gradient-to-r from-purple-600 to-blue-600 text-white px-8 py-4 rounded-lg font-bold text-lg hover:from-purple-700 hover:to-blue-700 transition shadow-lg transform hover:scale-105 whitespace-nowrap">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Create Free Account
                    </a>
                    <p class="text-xs text-center text-gray-600">
                        ⚡ Takes 20 seconds • 🆓 Free forever
                    </p>
                    <a href="{{ route('filament.app.auth.login') }}?from=public_invoice&invoice_id={{ $invoice->public_id }}"
                       class="text-sm text-center text-gray-700 hover:text-purple-600 font-semibold">
                        Already have an account? Login
                    </a>
                </div>
            </div>
        </div>

        <script>
            // Track next step strip shown
            document.addEventListener('DOMContentLoaded', function() {
                if (window.KinvoiceAnalytics) {
                    window.KinvoiceAnalytics.track('public_invoice_save_cta_shown', {
                        invoice_id: '{{ $invoice->public_id }}',
                        context: 'next_step_strip'
                    });
                }
            });
        </script>
        @endguest

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
                    @if(!$invoice->simple_mode)
                        @if($invoice->vat_percentage > 0)
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <span class="text-gray-700">VAT ({{ number_format($invoice->vat_percentage, 2) }}%):</span>
                            <span class="font-semibold text-green-600">+₦{{ number_format($invoice->vat_amount, 2) }}</span>
                        </div>
                        @endif
                        @if($invoice->wht_percentage > 0)
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <span class="text-gray-700">WHT ({{ number_format($invoice->wht_percentage, 2) }}%):</span>
                            <span class="font-semibold text-red-600">-₦{{ number_format($invoice->wht_amount, 2) }}</span>
                        </div>
                        @endif
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

            <!-- Document Verification -->
            <x-invoice-verification
                :hash="$invoice->document_hash"
                :updatedAt="$invoice->document_hash_updated_at"
            />
            </div>
        </div>

        <!-- Prominent Payment Section (A1) -->
        <div class="mt-8 bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-400 rounded-xl p-6 sm:p-8 shadow-lg">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center bg-green-600 text-white px-4 py-2 rounded-full text-sm font-bold mb-3">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    PAYMENT INFORMATION
                </div>
                <div class="text-4xl sm:text-5xl font-bold text-gray-900 mb-2">₦{{ number_format($invoice->total_amount, 2) }}</div>
                <p class="text-sm text-gray-600">
                    Due Date: <span class="font-semibold">{{ $invoice->due_date->format('M d, Y') }}</span>
                    @if($invoice->status === 'overdue')
                        <span class="text-red-600 font-bold ml-2">⚠️ OVERDUE</span>
                    @elseif($invoice->status === 'paid')
                        <span class="text-green-600 font-bold ml-2">✓ PAID</span>
                    @endif
                </p>
            </div>

            <!-- Payment Options -->
            <div class="space-y-4">
                <!-- Paystack Option -->
                @if($invoice->paystack_subaccount_code)
                <div class="bg-white rounded-lg p-4 border-2 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Pay Online with Card</h4>
                            <p class="text-sm text-gray-600">Fast & secure payment via Paystack</p>
                        </div>
                        <button type="button" onclick="openPaymentModal(); trackAnalytics('invoice_pay_now_clicked', {has_bank_details: {{ $invoice->from_bank_name ? 'true' : 'false' }}});"
                            class="bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition whitespace-nowrap">
                            Pay Now
                        </button>
                    </div>
                </div>
                @endif

                <!-- Bank Transfer Option -->
                @if($invoice->from_bank_name && $invoice->from_account_number)
                <div class="bg-white rounded-lg p-4 sm:p-6 border border-gray-300">
                    <h4 class="font-bold text-gray-900 mb-4 text-center sm:text-left">Bank Transfer Details</h4>
                    <div class="space-y-3">
                        <!-- Account Number -->
                        <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                            <div class="flex-1">
                                <p class="text-xs text-gray-600 mb-1">Account Number</p>
                                <p class="font-bold text-gray-900" id="account-number">{{ $invoice->from_account_number }}</p>
                            </div>
                            <button onclick="copyToClipboard('{{ $invoice->from_account_number }}', 'account_number'); trackAnalytics('invoice_bank_details_copied', {field: 'account_number'});"
                                class="ml-3 bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-purple-700 transition flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                <span class="hidden sm:inline">Copy</span>
                            </button>
                        </div>

                        <!-- Bank Name -->
                        <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                            <div class="flex-1">
                                <p class="text-xs text-gray-600 mb-1">Bank Name</p>
                                <p class="font-bold text-gray-900">{{ $invoice->from_bank_name }}</p>
                            </div>
                        </div>

                        <!-- Account Name -->
                        @if($invoice->from_account_name)
                        <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                            <div class="flex-1">
                                <p class="text-xs text-gray-600 mb-1">Account Name</p>
                                <p class="font-bold text-gray-900">{{ $invoice->from_account_name }}</p>
                            </div>
                        </div>
                        @endif

                        <!-- Amount to Pay -->
                        <div class="flex items-center justify-between bg-purple-50 p-3 rounded-lg border-2 border-purple-300">
                            <div class="flex-1">
                                <p class="text-xs text-gray-600 mb-1">Amount to Transfer</p>
                                <p class="text-2xl font-bold text-purple-600">₦{{ number_format($invoice->total_amount, 2) }}</p>
                            </div>
                            <button onclick="copyToClipboard('{{ $invoice->total_amount }}', 'amount'); trackAnalytics('invoice_bank_details_copied', {field: 'amount'});"
                                class="ml-3 bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-purple-700 transition flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                <span class="hidden sm:inline">Copy</span>
                            </button>
                        </div>

                        <!-- Reference/Narration -->
                        <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                            <div class="flex-1">
                                <p class="text-xs text-gray-600 mb-1">Payment Reference</p>
                                <p class="font-bold text-gray-900">{{ $invoice->invoice_number }}</p>
                            </div>
                            <button onclick="copyToClipboard('{{ $invoice->invoice_number }}', 'reference'); trackAnalytics('invoice_bank_details_copied', {field: 'narration'});"
                                class="ml-3 bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-purple-700 transition flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                <span class="hidden sm:inline">Copy</span>
                            </button>
                        </div>
                    </div>

                    <p class="text-xs text-gray-500 text-center mt-4">💡 Use the reference number when making payment</p>
                </div>
                @endif

                <!-- If no payment methods -->
                @if(!$invoice->paystack_subaccount_code && (!$invoice->from_bank_name || !$invoice->from_account_number))
                <div class="bg-white rounded-lg p-6 border border-gray-300 text-center">
                    <p class="text-gray-600">Payment details will be shared separately by the merchant.</p>
                </div>
                @endif
            </div>

            <!-- Copy Payment Message Button (A2) -->
            <div class="mt-4">
                <button onclick="copyPaymentMessage()"
                    class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-4 rounded-lg font-semibold hover:from-blue-700 hover:to-purple-700 transition flex items-center justify-center gap-2 shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                    </svg>
                    Copy Payment Message for WhatsApp
                </button>
                <p class="text-xs text-gray-600 text-center mt-2">💬 Ready-to-send payment reminder with all details</p>
            </div>
        </div>

        <!-- Enhanced CTA Section -->
        <div class="mt-8 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-xl p-8 sm:p-10 shadow-2xl border-2 border-purple-400">
            <div class="max-w-3xl mx-auto text-center">
                <div class="bg-yellow-400 text-yellow-900 px-4 py-2 rounded-full inline-block font-bold text-sm mb-4">
                    ✨ FREE ACCOUNT
                </div>
                <h3 class="text-2xl sm:text-3xl font-bold mb-3">Save time on your next invoice</h3>
                <p class="text-lg sm:text-xl text-purple-100 mb-6">
                    Create a free account to save customers & items, track payments, and generate invoices faster
                </p>

                <!-- Benefits Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8 text-left">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-green-300 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <p class="font-semibold text-sm">Save customers</p>
                                <p class="text-xs text-purple-200">Reuse customer details</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-green-300 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <p class="font-semibold text-sm">Save items</p>
                                <p class="text-xs text-purple-200">Create invoices faster</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-green-300 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <p class="font-semibold text-sm">Track payments</p>
                                <p class="text-xs text-purple-200">See paid/unpaid status</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <a href="{{ route('filament.app.auth.register') }}"
                        class="bg-white text-purple-600 px-8 py-4 rounded-lg font-bold text-lg hover:bg-gray-100 transition shadow-xl transform hover:scale-105">
                        Create Free Account →
                    </a>
                    <span class="text-sm text-purple-200">No credit card required</span>
                </div>
            </div>
        </div>
    </div>

    <!-- WhatsApp Template Modal -->
    <div id="whatsappModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-lg w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold text-gray-900 flex items-center">
                    <svg class="w-6 h-6 text-green-600 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    Share via WhatsApp
                </h3>
                <button type="button" onclick="closeWhatsAppModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <p class="text-sm text-gray-600 mb-4">Choose a message template or customize your own:</p>

            <!-- Message Templates -->
            <div class="space-y-3 mb-4">
                <div class="border-2 border-gray-200 rounded-lg p-3 hover:border-purple-500 cursor-pointer transition" onclick="selectTemplate(1)">
                    <label class="flex items-start cursor-pointer">
                        <input type="radio" name="template" value="1" class="mt-1 mr-3">
                        <div>
                            <p class="font-semibold text-gray-900 text-sm">Professional</p>
                            <p class="text-sm text-gray-600" id="template1">Hi, please find my invoice attached. Thank you.</p>
                        </div>
                    </label>
                </div>
                <div class="border-2 border-gray-200 rounded-lg p-3 hover:border-purple-500 cursor-pointer transition" onclick="selectTemplate(2)">
                    <label class="flex items-start cursor-pointer">
                        <input type="radio" name="template" value="2" class="mt-1 mr-3">
                        <div>
                            <p class="font-semibold text-gray-900 text-sm">Formal</p>
                            <p class="text-sm text-gray-600" id="template2">Hello, kindly see invoice for your records.</p>
                        </div>
                    </label>
                </div>
                <div class="border-2 border-gray-200 rounded-lg p-3 hover:border-purple-500 cursor-pointer transition" onclick="selectTemplate(3)">
                    <label class="flex items-start cursor-pointer">
                        <input type="radio" name="template" value="3" class="mt-1 mr-3">
                        <div>
                            <p class="font-semibold text-gray-900 text-sm">Friendly</p>
                            <p class="text-sm text-gray-600" id="template3">Good day, this is the invoice for the service provided.</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Editable Message -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Edit your message:</label>
                <textarea id="whatsappMessage" rows="4"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="Type your message here..."></textarea>
                <p class="text-xs text-gray-500 mt-1">The invoice link will be automatically added at the end</p>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3">
                <button type="button" onclick="sendWhatsApp()"
                    class="flex-1 bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    Send via WhatsApp
                </button>
                <button type="button" onclick="closeWhatsAppModal()"
                    class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Conversion Signup Modal (for logged-out users) -->
    @guest
        <x-conversion-modal trigger="pdf" :dismissible="true" />
    @endguest

    <!-- Payment Modal -->
    <div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold text-gray-900">Payment Information</h3>
                <button type="button" onclick="closePaymentModal()" class="text-gray-500 hover:text-gray-700">
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
                                <span>Amount to Pay</span>
                                <span class="font-semibold">₦{{ number_format($invoiceAmount, 2) }}</span>
                            </div>
                            <div class="border-t border-purple-200 pt-2 flex justify-between">
                                <span class="text-lg font-bold text-gray-900">Total</span>
                                <span class="text-2xl font-bold text-purple-600">₦{{ number_format($invoiceAmount, 2) }}</span>
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
            // Track GA4 event
            if (typeof gtag !== 'undefined') {
                gtag('event', 'invoice_pdf_downloaded', {
                    event_category: 'invoice',
                    event_label: 'pdf'
                });
            }

            // Track analytics event
            if (window.KinvoiceAnalytics) {
                window.KinvoiceAnalytics.track('invoice_pdf_downloaded', {
                    invoice_number: '{{ $invoice->invoice_number }}'
                });
            }

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

            // Show conversion modal after download starts (for logged-out users only)
            @guest
            setTimeout(() => {
                if (window.ConversionModal) {
                    window.ConversionModal.show('pdf');
                }
            }, 1500); // Wait 1.5 seconds for download to initiate
            @endguest

            // Let the default link behavior proceed
            return true;
        }

        // WhatsApp Template Selection - Nigerian market optimized
        const whatsappTemplates = {
            1: "Hi, please find my invoice attached. Thank you.",
            2: "Hello, kindly see invoice for your records.",
            3: "Good day, this is the invoice for the service provided."
        };

        function openWhatsAppTemplateModal() {
            console.log('WhatsApp button clicked');

            // Conversion loop: Require signup for WhatsApp sharing (logged-out users only)
            @guest
            if (window.ConversionModal) {
                window.ConversionModal.show('whatsapp');
                return;
            }
            @endguest

            // If logged in, proceed with WhatsApp modal
            try {
                const modal = document.getElementById('whatsappModal');
                console.log('WhatsApp modal element:', modal);
                if (!modal) {
                    alert('WhatsApp modal not found. Please contact support.');
                    return;
                }
                modal.classList.remove('hidden');
                // Pre-select first template
                const templateInput = document.querySelector('input[name="template"][value="1"]');
                if (templateInput) {
                    templateInput.checked = true;
                    selectTemplate(1);
                }
            } catch (error) {
                console.error('Error opening WhatsApp modal:', error);
                alert('Unable to open WhatsApp modal. Please refresh the page and try again.');
            }
        }

        function closeWhatsAppModal() {
            document.getElementById('whatsappModal').classList.add('hidden');
        }

        function selectTemplate(templateNum) {
            const message = whatsappTemplates[templateNum];
            document.getElementById('whatsappMessage').value = message;
            document.querySelector(`input[name="template"][value="${templateNum}"]`).checked = true;
        }

        function sendWhatsApp() {
            const url = "{{ route('public-invoice.show', $invoice->public_id) }}";
            const customMessage = document.getElementById('whatsappMessage').value.trim();

            if (!customMessage) {
                alert('Please enter a message to send');
                return;
            }

            // Build invoice details
            const invoiceDetails = `*INVOICE: {{ $invoice->invoice_number }}*\n` +
                                  `Amount: ₦{{ number_format($invoice->total_amount, 2) }}\n` +
                                  `Due: {{ $invoice->due_date->format('M d, Y') }}\n\n`;

            // Combine custom message with invoice details and link
            const fullMessage = `${customMessage}\n\n${invoiceDetails}View invoice: ${url}`;

            // Generate WhatsApp link (wa.me format - works on mobile and desktop)
            const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(fullMessage)}`;

            console.log('[WhatsApp] Generated link:', whatsappUrl);

            // Track GA4 event
            if (typeof gtag !== 'undefined') {
                gtag('event', 'invoice_shared_whatsapp', {
                    event_category: 'invoice',
                    event_label: 'whatsapp'
                });
            }

            // Track analytics event
            if (window.KinvoiceAnalytics) {
                window.KinvoiceAnalytics.track('invoice_shared', {
                    invoice_number: '{{ $invoice->invoice_number }}',
                    platform: 'whatsapp'
                });
            }

            // Try to open WhatsApp
            const newWindow = window.open(whatsappUrl, '_blank');

            // Handle popup blocking gracefully
            if (!newWindow || newWindow.closed || typeof newWindow.closed === 'undefined') {
                // Fallback: Try direct navigation
                if (confirm('Unable to open WhatsApp in a new window. Open in this tab?')) {
                    window.location.href = whatsappUrl;
                } else {
                    alert('Please allow popups to share via WhatsApp, or copy the invoice link manually.');
                }
            } else {
                // Success - close modal
                closeWhatsAppModal();
            }
        }

        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('whatsappModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeWhatsAppModal();
                }
            });
        });

        // Payment Modal Functions
        function openPaymentModal() {
            console.log('Payment button clicked');
            try {
                const paymentStatus = '{{ $invoice->payment_status }}';
                const modal = document.getElementById('paymentModal');
                console.log('Payment modal element:', modal);

                if (!modal) {
                    alert('Payment modal not found. Please contact support.');
                    return;
                }

                if (paymentStatus === 'paid') {
                    if (confirm('⚠️ This invoice appears to have been paid already.\n\nPaid on: {{ $invoice->paid_at ? $invoice->paid_at->format("M d, Y h:i A") : "N/A" }}\nAmount Paid: ₦{{ number_format($invoice->amount_paid ?? 0, 2) }}\n\nDo you still want to make a payment?')) {
                        modal.classList.remove('hidden');
                    }
                } else {
                    modal.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error opening payment modal:', error);
                alert('Unable to open payment modal. Please refresh the page and try again.');
            }
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
        }

        // Handle Payment Form Submission
        document.addEventListener('DOMContentLoaded', function() {
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
                label: "{{ $invoice->from_name }}", // Show the business name (FROM) as the merchant
                @if($invoice->paystack_subaccount_code)
                subaccount: '{{ $invoice->paystack_subaccount_code }}',
                transaction_charge: Math.round(netCalculation.service_charge * 100), // Platform keeps service charge (in kobo)
                bearer: 'account', // Subaccount (business) bears the charge - deducted from their portion
                @endif
                callback_url: '{{ route("public-invoice.show", $invoice->public_id) }}',
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
                    receiver_email: "{{ $invoice->from_email ?? '' }}"{{ ($invoice->from_bank_name && $invoice->from_account_number) ? ',' : '' }}
                    @if($invoice->from_bank_name && $invoice->from_account_number)
                    receiver_bank_name: "{{ $invoice->from_bank_name }}",
                    receiver_account_number: "{{ $invoice->from_account_number }}",
                    receiver_account_name: "{{ $invoice->from_account_name ?? '' }}",
                    receiver_account_type: "{{ $invoice->from_account_type ?? '' }}"
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
        });

        // Copy to Clipboard Helper (A1)
        function copyToClipboard(text, label) {
            if (navigator.clipboard && window.isSecureContext) {
                // Use Clipboard API if available
                navigator.clipboard.writeText(text).then(function() {
                    showCopyFeedback(label);
                }).catch(function(err) {
                    fallbackCopyToClipboard(text, label);
                });
            } else {
                // Fallback for older browsers or non-HTTPS
                fallbackCopyToClipboard(text, label);
            }
        }

        function fallbackCopyToClipboard(text, label) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.top = '0';
            textArea.style.left = '0';
            textArea.style.width = '2em';
            textArea.style.height = '2em';
            textArea.style.padding = '0';
            textArea.style.border = 'none';
            textArea.style.outline = 'none';
            textArea.style.boxShadow = 'none';
            textArea.style.background = 'transparent';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                document.execCommand('copy');
                showCopyFeedback(label);
            } catch (err) {
                alert('Failed to copy: ' + text);
            }

            document.body.removeChild(textArea);
        }

        function showCopyFeedback(label) {
            // Show temporary feedback
            const feedback = document.createElement('div');
            feedback.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
            feedback.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>${label.replace('_', ' ').toUpperCase()} copied!</span>
            `;
            document.body.appendChild(feedback);

            setTimeout(function() {
                feedback.remove();
            }, 2000);
        }

        // Analytics Tracking Helper
        function trackAnalytics(eventName, properties) {
            if (window.KinvoiceAnalytics) {
                window.KinvoiceAnalytics.track(eventName, properties);
            }
        }

        // Copy Payment Message for WhatsApp (A2)
        function copyPaymentMessage() {
            const hasBankDetails = {{ ($invoice->from_bank_name && $invoice->from_account_number) ? 'true' : 'false' }};
            const context = {!! session('invoice_just_created') ? "'post_generate'" : "'shared_view'" !!};

            // Build the message
            let message = "Hello,\n\n";
            message += "Please find payment details for Invoice {{ $invoice->invoice_number }}:\n\n";
            message += "*Amount Due:* ₦{{ number_format($invoice->total_amount, 2) }}\n";
            message += "*Due Date:* {{ $invoice->due_date->format('M d, Y') }}\n";
            message += "*Invoice Link:* {{ route('public-invoice.show', $invoice->public_id) }}\n\n";

            // Add bank details if available
            @if($invoice->from_bank_name && $invoice->from_account_number)
            message += "*Bank Transfer Details:*\n";
            message += "Bank: {{ $invoice->from_bank_name }}\n";
            message += "Account Number: {{ $invoice->from_account_number }}\n";
            @if($invoice->from_account_name)
            message += "Account Name: {{ $invoice->from_account_name }}\n";
            @endif
            message += "Amount: ₦{{ number_format($invoice->total_amount, 2) }}\n";
            message += "Reference: {{ $invoice->invoice_number }}\n\n";
            @endif

            message += "Thank you!";

            // Copy to clipboard
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(message).then(function() {
                    showCopySuccessMessage();
                    trackAnalytics('invoice_message_copied', {
                        context: context,
                        has_bank_details: hasBankDetails
                    });
                }).catch(function(err) {
                    fallbackCopyMessage(message, context, hasBankDetails);
                });
            } else {
                fallbackCopyMessage(message, context, hasBankDetails);
            }
        }

        function fallbackCopyMessage(message, context, hasBankDetails) {
            const textArea = document.createElement('textarea');
            textArea.value = message;
            textArea.style.position = 'fixed';
            textArea.style.top = '0';
            textArea.style.left = '0';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                document.execCommand('copy');
                showCopySuccessMessage();
                trackAnalytics('invoice_message_copied', {
                    context: context,
                    has_bank_details: hasBankDetails
                });
            } catch (err) {
                alert('Failed to copy message. Please try again.');
            }

            document.body.removeChild(textArea);
        }

        function showCopySuccessMessage() {
            const feedback = document.createElement('div');
            feedback.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 bg-green-600 text-white px-6 py-4 rounded-lg shadow-2xl z-50 flex items-center gap-3 max-w-sm';
            feedback.innerHTML = `
                <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <div>
                    <p class="font-bold">Message Copied!</p>
                    <p class="text-sm text-green-100">Ready to paste in WhatsApp</p>
                </div>
            `;
            document.body.appendChild(feedback);

            setTimeout(function() {
                feedback.remove();
            }, 3000);
        }
    </script>
</x-layout>
