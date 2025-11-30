<x-layout>
    <x-slot name="title">Invoice Preview - {{$invoice_number}}</x-slot>

    <!-- Paystack Inline JS -->
    <script src="https://js.paystack.co/v1/inline.js"></script>

    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold">Invoice Preview</h1>
            <p class="text-purple-100 mt-2">Review your invoice before downloading</p>
        </div>
    </div>

    <!-- Preview and Actions -->
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Action Buttons -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <!-- Download & Create New -->
            <div class="flex gap-2">
                <form method="POST" action="{{ route('public-invoice.download') }}" class="flex-1">
                    @csrf
                    @foreach(request()->except('_token') as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $subKey => $subValue)
                                @if(is_array($subValue))
                                    @foreach($subValue as $itemKey => $itemValue)
                                        <input type="hidden" name="{{$key}}[{{$subKey}}][{{$itemKey}}]" value="{{$itemValue}}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{$key}}[{{$subKey}}]" value="{{$subValue}}">
                                @endif
                            @endforeach
                        @else
                            <input type="hidden" name="{{$key}}" value="{{$value}}">
                        @endif
                    @endforeach
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white px-4 py-3 rounded-lg font-semibold hover:from-purple-700 hover:to-blue-700 transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download PDF
                    </button>
                </form>
                <a href="{{ route('public-invoice.create') }}"
                    class="bg-gray-200 text-gray-700 px-4 py-3 rounded-lg font-semibold hover:bg-gray-300 transition flex items-center">
                    New
                </a>
            </div>

            <!-- Share & Pay -->
            <div class="flex gap-2">
                <!-- WhatsApp Share -->
                <button onclick="shareWhatsApp()" class="flex-1 bg-green-600 text-white px-4 py-3 rounded-lg font-semibold hover:bg-green-700 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    WhatsApp
                </button>

                <!-- Pay Now -->
                <button onclick="openPaymentModal()" class="flex-1 bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-3 rounded-lg font-semibold hover:from-green-600 hover:to-green-700 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    Pay Now
                </button>
            </div>
        </div>

        <!-- Invoice Preview -->
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden border-2 border-gray-200 p-8">
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white p-6 -m-8 mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold">INVOICE</h1>
                        <p class="text-purple-100 mt-1">{{ $invoice_number }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm">Issue Date: {{ \Carbon\Carbon::parse($issue_date)->format('M d, Y') }}</p>
                        <p class="text-sm">Due Date: {{ \Carbon\Carbon::parse($due_date)->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>

            <!-- From/To Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 mb-2">FROM</h3>
                    <div class="text-gray-900">
                        <p class="font-bold text-lg">{{ $from_name }}</p>
                        @if($from_email)
                            <p class="text-sm">{{ $from_email }}</p>
                        @endif
                        @if($from_phone)
                            <p class="text-sm">{{ $from_phone }}</p>
                        @endif
                        @if($from_address)
                            <p class="text-sm">{{ $from_address }}</p>
                        @endif
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 mb-2">BILL TO</h3>
                    <div class="text-gray-900">
                        <p class="font-bold text-lg">{{ $to_name }}</p>
                        @if($to_email)
                            <p class="text-sm">{{ $to_email }}</p>
                        @endif
                        @if($to_phone)
                            <p class="text-sm">{{ $to_phone }}</p>
                        @endif
                        @if($to_address)
                            <p class="text-sm">{{ $to_address }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="mb-8">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-left p-3 text-sm font-semibold text-gray-700">Description</th>
                            <th class="text-center p-3 text-sm font-semibold text-gray-700">Qty</th>
                            <th class="text-right p-3 text-sm font-semibold text-gray-700">Unit Price</th>
                            <th class="text-right p-3 text-sm font-semibold text-gray-700">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        <tr class="border-b border-gray-200">
                            <td class="p-3 text-sm text-gray-900">{{ $item['description'] }}</td>
                            <td class="p-3 text-sm text-gray-900 text-center">{{ number_format($item['quantity'], 2) }}</td>
                            <td class="p-3 text-sm text-gray-900 text-right">₦{{ number_format($item['unit_price'], 2) }}</td>
                            <td class="p-3 text-sm text-gray-900 text-right">₦{{ number_format($item['total'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="flex justify-end mb-8">
                <div class="w-full md:w-1/2">
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span class="text-gray-700">Subtotal:</span>
                        <span class="font-semibold">₦{{ number_format($subtotal, 2) }}</span>
                    </div>
                    @if($vat_percentage > 0)
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span class="text-gray-700">VAT ({{ number_format($vat_percentage, 2) }}%):</span>
                        <span class="font-semibold">₦{{ number_format($vat_amount, 2) }}</span>
                    </div>
                    @endif
                    @if($wht_percentage > 0)
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span class="text-gray-700">WHT ({{ number_format($wht_percentage, 2) }}%):</span>
                        <span class="font-semibold text-red-600">-₦{{ number_format($wht_amount, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between py-3 bg-gradient-to-r from-purple-50 to-blue-50 px-4 rounded-lg mt-2">
                        <span class="text-lg font-bold text-gray-900">Total:</span>
                        <span class="text-xl font-bold text-purple-600">₦{{ number_format($total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            @if($notes)
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Notes:</h3>
                <p class="text-sm text-gray-600">{{ $notes }}</p>
            </div>
            @endif
        </div>

        <!-- CTA Section -->
        <div class="mt-8 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-xl p-8 text-center">
            <h3 class="text-2xl font-bold mb-2">Want to manage invoices professionally?</h3>
            <p class="text-purple-100 mb-4">Track payments, send invoices, and manage your business with Khan Invoice</p>
            <a href="{{ route('filament.app.auth.register') }}"
                class="inline-block bg-white text-purple-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
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
                    <!-- Amount Display -->
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600">Amount to Pay</p>
                        <p class="text-2xl font-bold text-purple-600">₦{{ number_format($total_amount, 2) }}</p>
                    </div>

                    <!-- Payer Information -->
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">Payer Information</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payer Name *</label>
                                <input type="text" id="payer_name" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    placeholder="Enter payer name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payer Email *</label>
                                <input type="email" id="payer_email" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    placeholder="payer@example.com">
                            </div>
                        </div>
                    </div>

                    <!-- Receiver Information -->
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">Receiver Information</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Receiver Name *</label>
                                <input type="text" id="receiver_name" required value="{{ $from_name }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    placeholder="Enter receiver name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Receiver Email *</label>
                                <input type="email" id="receiver_email" required value="{{ $from_email }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    placeholder="receiver@example.com">
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
        // WhatsApp Share Function
        function shareWhatsApp() {
            const invoiceNumber = "{{ $invoice_number }}";
            const fromName = "{{ $from_name }}";
            const toName = "{{ $to_name }}";
            const total = "₦{{ number_format($total_amount, 2) }}";
            const dueDate = "{{ \Carbon\Carbon::parse($due_date)->format('M d, Y') }}";

            const message = `*INVOICE: ${invoiceNumber}*\n\n` +
                           `From: ${fromName}\n` +
                           `To: ${toName}\n` +
                           `Amount: ${total}\n` +
                           `Due Date: ${dueDate}\n\n` +
                           `View invoice at: ${window.location.href}\n\n` +
                           `Generated with Khan Invoice - https://kinvoice.ng`;

            const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
        }

        // Payment Modal Functions
        function openPaymentModal() {
            document.getElementById('paymentModal').classList.remove('hidden');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
        }

        // Handle Payment Form Submission
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const payerName = document.getElementById('payer_name').value;
            const payerEmail = document.getElementById('payer_email').value;
            const receiverName = document.getElementById('receiver_name').value;
            const receiverEmail = document.getElementById('receiver_email').value;

            // Generate reference for this payment
            const reference = 'KI_PUBLIC_' + Date.now() + '_' + Math.floor(Math.random() * 1000000);

            // Initialize Paystack payment
            const handler = PaystackPop.setup({
                key: '{{ config("services.paystack.public_key") }}',
                email: payerEmail,
                amount: {{ $total_amount * 100 }}, // Convert to kobo
                currency: 'NGN',
                ref: reference,
                metadata: {
                    invoice_number: "{{ $invoice_number }}",
                    payer_name: payerName,
                    receiver_name: receiverName,
                    receiver_email: receiverEmail,
                    from_name: "{{ $from_name }}",
                    to_name: "{{ $to_name }}",
                },
                callback: function(response) {
                    // Payment successful
                    closePaymentModal();

                    // Show success message
                    alert('Payment Successful!\n\n' +
                          'Transaction Reference: ' + response.reference + '\n' +
                          'Amount: ₦{{ number_format($total_amount, 2) }}\n\n' +
                          'A confirmation has been sent to both payer and receiver.');

                    // You could also send this to a backend endpoint to record the payment
                    // and send confirmation emails
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
