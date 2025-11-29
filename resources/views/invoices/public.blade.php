<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body class="bg-gray-50 p-4 md:p-8">
    <div class="max-w-4xl mx-auto">
        <!-- Action Buttons -->
        <div class="no-print mb-4 flex justify-end gap-2">
            <button onclick="window.print()" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Print
            </button>
            <a href="{{ route('invoice.download', $invoice->public_id) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Download PDF
            </a>
        </div>

        <!-- Invoice Card -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        @if($businessProfile && $businessProfile->logo_url)
                            <img src="{{ asset('storage/' . $businessProfile->logo_url) }}" alt="Business Logo" class="mb-4 max-h-16 max-w-xs">
                        @endif
                        <h1 class="text-3xl font-bold mb-2">INVOICE</h1>
                        <p class="text-purple-100">{{ $invoice->invoice_number }}</p>
                    </div>
                    <div class="md:text-right">
                        @if($businessProfile)
                            <h2 class="text-2xl font-bold mb-1">{{ $businessProfile->business_name }}</h2>
                            @if($businessProfile->address_line1)
                                <p class="text-purple-100">{{ $businessProfile->address_line1 }}</p>
                            @endif
                            @if($businessProfile->city || $businessProfile->state)
                                <p class="text-purple-100">
                                    {{ $businessProfile->city }}@if($businessProfile->city && $businessProfile->state), @endif{{ $businessProfile->state }}
                                </p>
                            @endif
                            @if($businessProfile->phone)
                                <p class="text-purple-100">{{ $businessProfile->phone }}</p>
                            @endif
                            @if($businessProfile->email)
                                <p class="text-purple-100">{{ $businessProfile->email }}</p>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            <!-- Invoice Info & Customer -->
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <!-- Bill To -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 uppercase mb-2">Bill To</h3>
                        <div class="text-gray-900">
                            <p class="font-semibold text-lg">{{ $invoice->customer->name }}</p>
                            @if($invoice->customer->company_name)
                                <p>{{ $invoice->customer->company_name }}</p>
                            @endif
                            @if($invoice->customer->email)
                                <p>{{ $invoice->customer->email }}</p>
                            @endif
                            @if($invoice->customer->phone)
                                <p>{{ $invoice->customer->phone }}</p>
                            @endif
                            @if($invoice->customer->address_line1)
                                <p class="mt-2">{{ $invoice->customer->address_line1 }}</p>
                            @endif
                            @if($invoice->customer->city || $invoice->customer->state)
                                <p>{{ $invoice->customer->city }}@if($invoice->customer->city && $invoice->customer->state), @endif{{ $invoice->customer->state }}</p>
                            @endif
                            @if($invoice->customer->tin)
                                <p class="mt-2"><span class="text-gray-600">TIN:</span> {{ $invoice->customer->tin }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Invoice Details -->
                    <div class="md:text-right">
                        <div class="inline-block text-left">
                            <div class="mb-2">
                                <span class="text-gray-600">Issue Date:</span>
                                <span class="font-semibold ml-2">{{ $invoice->issue_date->format('M d, Y') }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-gray-600">Due Date:</span>
                                <span class="font-semibold ml-2">{{ $invoice->due_date->format('M d, Y') }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-gray-600">Status:</span>
                                <span class="ml-2 px-3 py-1 rounded-full text-sm font-semibold
                                    {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $invoice->status === 'sent' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $invoice->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ $invoice->status === 'overdue' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $invoice->status === 'partially_paid' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $invoice->status === 'cancelled' ? 'bg-gray-100 text-gray-600' : '' }}">
                                    {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                                </span>
                            </div>
                            @if($businessProfile)
                                @if($businessProfile->cac_number)
                                    <div class="mt-4 text-sm">
                                        <span class="text-gray-600">CAC:</span>
                                        <span class="ml-2">{{ $businessProfile->cac_number }}</span>
                                    </div>
                                @endif
                                @if($businessProfile->tin)
                                    <div class="text-sm">
                                        <span class="text-gray-600">TIN:</span>
                                        <span class="ml-2">{{ $businessProfile->tin }}</span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Line Items Table -->
                <div class="overflow-x-auto mb-8">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b-2 border-gray-300">
                                <th class="text-left py-3 px-2 font-semibold text-gray-700">Description</th>
                                <th class="text-right py-3 px-2 font-semibold text-gray-700">Qty</th>
                                <th class="text-right py-3 px-2 font-semibold text-gray-700">Unit Price</th>
                                <th class="text-right py-3 px-2 font-semibold text-gray-700">Discount</th>
                                <th class="text-right py-3 px-2 font-semibold text-gray-700">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $item)
                                <tr class="border-b border-gray-200">
                                    <td class="py-3 px-2">{{ $item->description }}</td>
                                    <td class="text-right py-3 px-2">{{ number_format($item->quantity, 2) }}</td>
                                    <td class="text-right py-3 px-2">₦{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-right py-3 px-2 text-red-600">
                                        @if($item->discount > 0)
                                            -₦{{ number_format($item->discount, 2) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-right py-3 px-2 font-semibold">₦{{ number_format($item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="flex justify-end">
                    <div class="w-full md:w-1/2 lg:w-1/3">
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-semibold">₦{{ number_format($invoice->sub_total, 2) }}</span>
                        </div>
                        @if($invoice->discount_total > 0)
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <span class="text-gray-600">Discount:</span>
                                <span class="font-semibold text-red-600">-₦{{ number_format($invoice->discount_total, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <span class="text-gray-600">VAT ({{ number_format($invoice->vat_rate, 1) }}%):</span>
                            <span class="font-semibold">₦{{ number_format($invoice->vat_amount, 2) }}</span>
                        </div>
                        @if($invoice->wht_amount && $invoice->wht_amount > 0)
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <span class="text-gray-600">WHT ({{ number_format($invoice->wht_rate, 1) }}%):</span>
                                <span class="font-semibold text-red-600">-₦{{ number_format($invoice->wht_amount, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between py-3 border-t-2 border-gray-400 mt-2">
                            <span class="text-lg font-bold">Total:</span>
                            <span class="text-lg font-bold text-purple-600">₦{{ number_format($invoice->total_amount, 2) }}</span>
                        </div>
                        @if($invoice->amount_paid > 0)
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <span class="text-gray-600">Amount Paid:</span>
                                <span class="font-semibold text-green-600">₦{{ number_format($invoice->amount_paid, 2) }}</span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="font-bold">Balance Due:</span>
                                <span class="font-bold text-red-600">₦{{ number_format($invoice->total_amount - $invoice->amount_paid, 2) }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Banking Information -->
                @if($businessProfile && $businessProfile->bank_name)
                    <div class="mt-8 p-6 bg-blue-50 rounded-lg">
                        <h3 class="font-semibold text-lg mb-3 text-gray-900">Payment Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-600">Bank Name:</p>
                                <p class="font-semibold">{{ $businessProfile->bank_name }}</p>
                            </div>
                            @if($businessProfile->bank_account_name)
                                <div>
                                    <p class="text-gray-600">Account Name:</p>
                                    <p class="font-semibold">{{ $businessProfile->bank_account_name }}</p>
                                </div>
                            @endif
                            @if($businessProfile->bank_account_number)
                                <div>
                                    <p class="text-gray-600">Account Number:</p>
                                    <p class="font-semibold text-lg">{{ $businessProfile->bank_account_number }}</p>
                                </div>
                            @endif
                            @if($businessProfile->bank_account_type)
                                <div>
                                    <p class="text-gray-600">Account Type:</p>
                                    <p class="font-semibold">{{ ucfirst($businessProfile->bank_account_type) }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Notes -->
                @if($invoice->footer)
                    <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $invoice->footer }}</p>
                    </div>
                @endif

                <!-- Footer -->
                <div class="mt-8 pt-8 border-t border-gray-200 text-center text-sm text-gray-500">
                    <p>Thank you for your business!</p>
                    <p class="mt-2">Generated with Khan Invoice</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
