<x-filament-widgets::widget>
    <div class="bg-gradient-to-br from-purple-50 to-blue-50 border-2 border-purple-300 rounded-xl p-6 sm:p-8 shadow-lg">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center">
                <div class="bg-gradient-to-br from-purple-600 to-blue-600 text-white p-3 rounded-full mr-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Welcome to Kinvoice!</h2>
                    <p class="text-sm text-gray-600 mt-1">Let's get your invoicing started in 3 easy steps</p>
                </div>
            </div>
            <button
                wire:click="dismiss"
                class="text-gray-400 hover:text-gray-600 transition flex-shrink-0 ml-4"
                title="Dismiss">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="mb-6">
            <p class="text-base font-semibold text-gray-800 mb-4">🚀 Get started in seconds:</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Step 1: First Invoice (PRIMARY ACTION) -->
                <div class="bg-white rounded-lg p-5 border-2 border-purple-200 hover:border-purple-400 transition shadow-md">
                    <div class="flex items-start mb-3">
                        <div class="bg-purple-100 text-purple-700 rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm mr-3 flex-shrink-0">
                            1
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 text-lg mb-1">Create your first invoice</h3>
                            <p class="text-sm text-gray-600 mb-3">Start invoicing immediately - no setup required</p>

                            <!-- Dual Buttons -->
                            <div class="space-y-2">
                                <a href="{{ route('app.invoices.quick.create') }}?from=dashboard_cta"
                                   onclick="if (window.KinvoiceAnalytics) { window.KinvoiceAnalytics.track('dashboard_quick_invoice_clicked'); }"
                                   class="inline-flex items-center justify-center w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white px-4 py-3 rounded-lg font-bold hover:from-purple-700 hover:to-blue-700 transition shadow-lg transform hover:scale-105 text-sm">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    Quick Invoice (Recommended)
                                </a>
                                <a href="{{ route('filament.app.resources.invoices.create') }}"
                                   onclick="if (window.KinvoiceAnalytics) { window.KinvoiceAnalytics.track('dashboard_advanced_invoice_clicked'); }"
                                   class="inline-flex items-center justify-center w-full bg-white border-2 border-purple-300 text-purple-700 px-4 py-2.5 rounded-lg font-semibold hover:bg-purple-50 transition text-sm">
                                    Advanced Invoice
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Business Profile (OPTIONAL) -->
                <div class="bg-white rounded-lg p-5 border-2 border-blue-200 hover:border-blue-400 transition">
                    <div class="flex items-start mb-3">
                        <div class="bg-blue-100 text-blue-700 rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm mr-3 flex-shrink-0">
                            2
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 text-lg mb-1">Add business profile <span class="text-xs font-normal text-gray-500">(Optional)</span></h3>
                            <p class="text-sm text-gray-600">Add logo, bank details for professional invoices</p>
                        </div>
                    </div>
                    <a href="{{ route('filament.app.resources.business-profiles.create') }}"
                       onclick="if (window.KinvoiceAnalytics) { window.KinvoiceAnalytics.track('registered_welcome_action', { action: 'setup_business' }); }"
                       class="inline-flex items-center justify-center w-full bg-blue-600 text-white px-4 py-2.5 rounded-lg font-semibold hover:bg-blue-700 transition text-sm shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        Set up business
                    </a>
                </div>

                <!-- Step 3: Record Payment -->
                <div class="bg-white rounded-lg p-5 border-2 border-green-200 hover:border-green-400 transition">
                    <div class="flex items-start mb-3">
                        <div class="bg-green-100 text-green-700 rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm mr-3 flex-shrink-0">
                            3
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 text-lg mb-1">Track payments</h3>
                            <p class="text-sm text-gray-600">Mark invoices paid and monitor cash flow</p>
                        </div>
                    </div>
                    <div class="inline-flex items-center justify-center w-full bg-gray-100 text-gray-600 px-4 py-2.5 rounded-lg font-semibold text-sm border-2 border-gray-300">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        After creating invoice
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg p-4 border border-purple-200">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-purple-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="flex-1">
                    <p class="text-sm text-gray-700">
                        <strong class="font-semibold">Need help?</strong> Check out our
                        <a href="#" class="text-purple-600 hover:text-purple-800 font-semibold">quick start guide</a>
                        or explore the dashboard to see all features.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Track welcome panel shown
        document.addEventListener('DOMContentLoaded', function() {
            if (window.KinvoiceAnalytics) {
                window.KinvoiceAnalytics.track('registered_welcome_shown');
            }
        });
    </script>
</x-filament-widgets::widget>
