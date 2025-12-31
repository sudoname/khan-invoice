{{-- Conversion Signup Modal Component --}}
@props(['trigger' => 'default', 'dismissible' => true])

<div id="conversionSignupModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 shadow-2xl transform transition-all">
        <!-- Close Button (if dismissible) -->
        @if($dismissible)
        <button type="button" onclick="dismissConversionModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        @endif

        <!-- Icon -->
        <div class="flex justify-center mb-4">
            <div class="w-16 h-16 bg-gradient-to-br from-purple-100 to-blue-100 rounded-full flex items-center justify-center">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>

        <!-- Heading -->
        <h3 class="text-2xl font-bold text-center text-gray-900 mb-2">
            Save this invoice forever
        </h3>

        <!-- Benefits -->
        <div class="space-y-3 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <p class="text-gray-700">Email delivery & automatic reminders</p>
            </div>
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <p class="text-gray-700">Save customers & reuse them</p>
            </div>
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <p class="text-gray-700">Invoice history & payment tracking</p>
            </div>
        </div>

        <!-- CTA -->
        <p class="text-center text-sm text-gray-600 mb-6">
            <strong>Free forever</strong> • Takes less than 20 seconds
        </p>

        <!-- Buttons -->
        <div class="space-y-3">
            <a href="{{ route('filament.app.auth.register') }}?ref={{ $trigger }}"
               onclick="trackConversionClick('{{ $trigger }}')"
               class="block w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white text-center px-6 py-3 rounded-lg font-semibold hover:from-purple-700 hover:to-blue-700 transition shadow-lg">
                Create Free Account
            </a>

            @if($dismissible)
            <button type="button" onclick="dismissConversionModal()"
                    class="block w-full bg-gray-100 text-gray-700 text-center px-6 py-3 rounded-lg font-semibold hover:bg-gray-200 transition">
                Continue without signup
            </button>
            @endif
        </div>

        <!-- Trust Badge -->
        <p class="text-xs text-center text-gray-500 mt-4">
            🔒 Trusted by Nigerian businesses • No credit card required
        </p>
    </div>
</div>

<script>
// Conversion Modal Management
window.ConversionModal = {
    shown: false,
    dismissed: false,

    init() {
        // Check if already dismissed in this session
        if (sessionStorage.getItem('conversion_modal_dismissed')) {
            this.dismissed = true;
        }
    },

    show(source = 'unknown') {
        // Don't show if already dismissed or currently shown
        if (this.dismissed || this.shown) return;

        const modal = document.getElementById('conversionSignupModal');
        if (!modal) return;

        modal.classList.remove('hidden');
        this.shown = true;

        // Track analytics
        if (window.KinvoiceAnalytics) {
            window.KinvoiceAnalytics.track('signup_prompt_shown', { source });
        }

        console.log('[Conversion] Modal shown - source:', source);
    },

    hide() {
        const modal = document.getElementById('conversionSignupModal');
        if (modal) {
            modal.classList.add('hidden');
        }
        this.shown = false;
    },

    dismiss() {
        this.hide();
        this.dismissed = true;
        sessionStorage.setItem('conversion_modal_dismissed', 'true');

        // Track analytics
        if (window.KinvoiceAnalytics) {
            window.KinvoiceAnalytics.track('signup_prompt_dismissed');
        }

        console.log('[Conversion] Modal dismissed');
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    window.ConversionModal.init();
});

// Global helper functions
function showConversionModal(source) {
    window.ConversionModal.show(source);
}

function dismissConversionModal() {
    window.ConversionModal.dismiss();
}

function trackConversionClick(source) {
    if (window.KinvoiceAnalytics) {
        window.KinvoiceAnalytics.track('signup_cta_clicked', { source });
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('conversionSignupModal');
    if (modal && e.target === modal) {
        dismissConversionModal();
    }
});
</script>
