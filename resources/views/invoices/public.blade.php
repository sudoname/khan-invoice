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
        <!-- Flash Messages -->
        @if(session('success'))
            <div class="no-print mb-4 bg-green-50 border border-green-400 text-green-800 px-4 py-3 rounded-lg relative" role="alert">
                <strong class="font-semibold">Success!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="no-print mb-4 bg-red-50 border border-red-400 text-red-800 px-4 py-3 rounded-lg relative" role="alert">
                <strong class="font-semibold">Error!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="no-print mb-4 flex justify-end gap-2 flex-wrap">
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
            <a href="https://wa.me/?text={{ urlencode('Invoice ' . $invoice->invoice_number . ' - Amount: ₦' . number_format($invoice->total_amount, 2) . '. View invoice: ' . route('invoice.public', $invoice->public_id)) }}"
               target="_blank"
               class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                Share on WhatsApp
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

                <!-- Payment Button -->
                @php
                    $balanceDue = $invoice->total_amount - $invoice->amount_paid;
                @endphp
                @if($balanceDue > 0 && !in_array($invoice->status, ['paid', 'cancelled']))
                    <div class="mt-6 flex justify-end no-print">
                        <form action="{{ route('payment.initiate', $invoice->public_id) }}" method="POST" class="w-full md:w-auto">
                            @csrf
                            <button type="submit" class="w-full md:w-auto bg-gradient-to-r from-purple-600 to-blue-600 text-white px-8 py-3 rounded-lg hover:from-purple-700 hover:to-blue-700 transition flex items-center justify-center gap-2 shadow-lg font-semibold text-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                                Pay Now - ₦{{ number_format($balanceDue, 2) }}
                            </button>
                        </form>
                    </div>
                @endif

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
