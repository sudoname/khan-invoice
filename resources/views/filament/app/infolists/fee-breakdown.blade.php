@php
    // Debug: Check what variables are available
    // In Filament custom views, data from viewData() should be available as $this->data
    $data = $this->data ?? [];
    $invoiceAmount = $data['invoice_amount'] ?? 0;
    $breakdown = $data['breakdown'] ?? [];
@endphp

<div class="rounded-lg bg-gray-50 p-4 space-y-4">
    <div class="bg-white rounded-lg p-4 border border-gray-200">
        <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            How Payment Fees Work
        </h4>
        <p class="text-sm text-gray-600 mb-4">
            When customers pay this invoice using online payment (Paystack), fees are automatically deducted.
            You receive the net amount after fees.
        </p>

        <div class="space-y-3">
            <!-- Invoice Amount -->
            <div class="flex justify-between items-center py-2 border-b border-gray-200">
                <span class="text-sm font-medium text-gray-700">Invoice Amount (Customer Pays)</span>
                <span class="text-lg font-bold text-gray-900">₦{{ number_format($breakdown['invoice_amount'] ?? 0, 2) }}</span>
            </div>

            <!-- Fees Section -->
            <div class="bg-red-50 rounded-lg p-3 space-y-2">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-700">
                        <span class="font-medium">Paystack Fee</span>
                        <span class="text-xs text-gray-500 ml-1">(1.5% + ₦100)</span>
                    </span>
                    <span class="font-semibold text-red-700">-₦{{ number_format($breakdown['paystack_fee'] ?? 0, 2) }}</span>
                </div>

                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-700">
                        <span class="font-medium">Platform Service Charge</span>
                        <span class="text-xs text-gray-500 ml-1">(2% min ₦150)</span>
                    </span>
                    <span class="font-semibold text-red-700">-₦{{ number_format($breakdown['service_charge'] ?? 0, 2) }}</span>
                </div>

                <div class="flex justify-between items-center pt-2 border-t border-red-200">
                    <span class="text-sm font-semibold text-gray-700">Total Fees Deducted</span>
                    <span class="font-bold text-red-700">-₦{{ number_format($breakdown['total_fees'] ?? 0, 2) }}</span>
                </div>
            </div>

            <!-- Net Amount -->
            <div class="flex justify-between items-center py-3 bg-green-50 rounded-lg px-4 border-2 border-green-400">
                <span class="text-sm font-bold text-gray-900">You Receive (Net Amount)</span>
                <span class="text-2xl font-bold text-green-700">₦{{ number_format($breakdown['net_amount_received'] ?? 0, 2) }}</span>
            </div>
        </div>
    </div>

    <!-- Quick Tips -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
        <h5 class="text-sm font-semibold text-blue-900 mb-2">💡 Fee Tips</h5>
        <ul class="text-sm text-blue-800 space-y-1">
            <li class="flex items-start">
                <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span><strong>Customer pays only the invoice amount</strong> - fees are absorbed by your business</span>
            </li>
            <li class="flex items-start">
                <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Bank transfers have <strong>no fees</strong> - you receive the full invoice amount</span>
            </li>
            <li class="flex items-start">
                <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Higher invoice amounts = lower fee percentage</span>
            </li>
            <li class="flex items-start">
                <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Fees are capped at ₦2,000 (Paystack) + ₦3,000 (Platform)</span>
            </li>
        </ul>
    </div>
</div>
