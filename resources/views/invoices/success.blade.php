<x-filament-panels::page>
    <div class="max-w-4xl mx-auto text-center">
        <!-- Success Icon -->
        <div class="mb-6">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-green-100 rounded-full">
                <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>

        <!-- Success Message -->
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Invoice Created Successfully!</h1>
        <p class="text-lg text-gray-600 mb-8">
            Invoice <strong>{{ $invoice->invoice_number }}</strong> has been saved to your account.
        </p>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <a href="{{ route('filament.app.resources.invoices.view', $invoice) }}"
               class="bg-white border-2 border-purple-300 hover:border-purple-500 rounded-lg p-6 transition group">
                <svg class="w-8 h-8 text-purple-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <span class="font-semibold text-gray-900 group-hover:text-purple-600">View Invoice</span>
            </a>

            <a href="{{ route('invoice.download', $invoice->public_id) }}" target="_blank"
               onclick="trackDownload()"
               class="bg-white border-2 border-blue-300 hover:border-blue-500 rounded-lg p-6 transition group">
                <svg class="w-8 h-8 text-blue-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                </svg>
                <span class="font-semibold text-gray-900 group-hover:text-blue-600">Download PDF</span>
            </a>

            <button onclick="shareInvoice()"
               class="bg-white border-2 border-green-300 hover:border-green-500 rounded-lg p-6 transition group">
                <svg class="w-8 h-8 text-green-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                </svg>
                <span class="font-semibold text-gray-900 group-hover:text-green-600">Share Link</span>
            </button>

            <a href="{{ route('filament.app.resources.invoices.edit', $invoice) }}"
               class="bg-white border-2 border-yellow-300 hover:border-yellow-500 rounded-lg p-6 transition group">
                <svg class="w-8 h-8 text-yellow-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                <span class="font-semibold text-gray-900 group-hover:text-yellow-600">Edit Invoice</span>
            </a>
        </div>

        <!-- Next Steps (if no business profile) -->
        @if(!$invoice->business_profile_id)
        <div class="bg-blue-50 border-2 border-blue-300 rounded-xl p-6 text-left mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center">
                <svg class="w-6 h-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Recommended: Add Your Business Details
            </h3>
            <p class="text-gray-700 mb-4">
                Brand your invoices with your logo, business info, and bank details. Makes you look more professional!
            </p>
            <a href="{{ route('filament.app.resources.business-profiles.create') }}?from=invoice_success"
               onclick="trackBusinessProfileStart()"
               class="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                Add Business Details
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="{{ route('app.invoices.quick.create') }}"
               class="inline-flex items-center justify-center bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:from-purple-700 hover:to-blue-700 transition shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create Another Invoice
            </a>
            <a href="{{ route('filament.app.pages.dashboard') }}"
               class="inline-flex items-center justify-center bg-white border-2 border-gray-300 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-50 transition">
                Back to Dashboard
            </a>
        </div>
    </div>

    @push('scripts')
    <script>
        // Track success page view and invoice creation
        if (window.KinvoiceAnalytics) {
            const timeSinceSignup = {{ auth()->user()->created_at ? now()->diffInSeconds(auth()->user()->created_at) : 0 }};
            const isFirstInvoice = {{ auth()->user()->invoices()->count() === 1 ? 'true' : 'false' }};

            window.KinvoiceAnalytics.track('invoice_created', {
                context: '{{ session("context", "unknown") }}',
                invoice_id_hash: '{{ hash("sha256", $invoice->id) }}',
                customer_type: '{{ session("customer_type", "unknown") }}',
                time_since_signup_sec: timeSinceSignup,
                is_first_invoice: isFirstInvoice,
                has_business_profile: {{ $invoice->business_profile_id ? 'true' : 'false' }}
            });
        }

        if (typeof gtag !== 'undefined') {
            gtag('event', 'invoice_created', {
                invoice_type: '{{ session("context", "quick") }}',
                customer_type: '{{ session("customer_type") }}'
            });
        }

        function trackDownload() {
            if (window.KinvoiceAnalytics) {
                window.KinvoiceAnalytics.track('invoice_pdf_downloaded', {
                    context: 'success_screen',
                    invoice_id_hash: '{{ hash("sha256", $invoice->id) }}'
                });
            }
            if (typeof gtag !== 'undefined') {
                gtag('event', 'invoice_pdf_downloaded', {
                    context: 'success_screen'
                });
            }
        }

        function shareInvoice() {
            const url = '{{ route("invoice.public", $invoice->public_id) }}';
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(() => {
                    alert('✅ Invoice link copied to clipboard!\n\nShare it: ' + url);
                });
            } else {
                // Fallback
                const textArea = document.createElement('textarea');
                textArea.value = url;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    alert('✅ Invoice link copied to clipboard!\n\nShare it: ' + url);
                } catch (err) {
                    alert('Copy the link manually:\n\n' + url);
                }
                document.body.removeChild(textArea);
            }

            if (window.KinvoiceAnalytics) {
                window.KinvoiceAnalytics.track('invoice_shared', {
                    context: 'success_screen',
                    invoice_id_hash: '{{ hash("sha256", $invoice->id) }}',
                    channel: 'copy_link'
                });
            }
            if (typeof gtag !== 'undefined') {
                gtag('event', 'invoice_shared', {
                    context: 'success_screen',
                    channel: 'copy_link'
                });
            }
        }

        function trackBusinessProfileStart() {
            if (window.KinvoiceAnalytics) {
                window.KinvoiceAnalytics.track('business_profile_setup_started', {
                    source: 'post_invoice_nudge'
                });
            }
        }
    </script>
    @endpush
</x-filament-panels::page>
