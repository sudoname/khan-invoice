<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.3;
        }
        .container {
            padding: 20px;
        }
        .header {
            background-color: #7c3aed;
            color: white;
            padding: 15px 20px;
            margin: -20px -20px 15px -20px;
        }
        .header-content {
            display: table;
            width: 100%;
        }
        .header-left, .header-center, .header-right {
            display: table-cell;
            vertical-align: middle;
        }
        .header-left {
            width: 33%;
            text-align: left;
        }
        .header-center {
            width: 34%;
            text-align: center;
        }
        .header-right {
            width: 33%;
            text-align: right;
        }
        h1 {
            font-size: 20pt;
            margin-bottom: 5px;
        }
        h2 {
            font-size: 12pt;
            margin-bottom: 5px;
        }
        h3 {
            font-size: 10pt;
            margin-bottom: 8px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
        }
        h4 {
            font-size: 8pt;
            margin-bottom: 5px;
            color: #888;
            text-transform: uppercase;
            font-weight: 600;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }
        .info-box p {
            margin-bottom: 3px;
        }
        .info-label {
            font-weight: bold;
        }
        .bank-details {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table thead {
            background-color: #f3f4f6;
        }
        table th {
            padding: 8px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }
        table td {
            padding: 8px;
            border-bottom: 1px solid #f3f4f6;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-table {
            width: 300px;
            margin-left: auto;
            margin-bottom: 20px;
        }
        .totals-table td {
            padding: 5px 10px;
            border: none;
        }
        .total-row {
            background-color: #f9f5ff;
            font-weight: bold;
            font-size: 11pt;
        }
        .notes {
            background-color: #f9fafb;
            padding: 10px;
            border-radius: 4px;
            margin-top: 15px;
        }
        .notes h3 {
            margin-bottom: 5px;
        }
        .payment-section {
            background-color: #e0f2fe;
            padding: 15px;
            margin-top: 20px;
            border-left: 4px solid #0284c7;
        }
        .payment-section h3 {
            color: #0284c7;
            margin-bottom: 10px;
        }
        .payment-link {
            color: #0284c7;
            word-break: break-all;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8pt;
            color: #666;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="header-left">
                    <h1>INVOICE</h1>
                    <p style="font-size: 10pt;">{{ $invoice->invoice_number }}</p>
                </div>
                <div class="header-center">
                    @if($invoice->company_logo)
                        <img src="{{ storage_path('app/public/' . $invoice->company_logo) }}" alt="Company Logo" style="max-width: 120px; max-height: 70px; object-fit: contain;">
                    @endif
                </div>
                <div class="header-right">
                    <p style="font-size: 9pt;">
                        <strong>Issue Date:</strong> {{ $invoice->issue_date->format('M d, Y') }}<br>
                        <strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- From/To Information -->
        <div class="info-section">
            <div class="info-box">
                <h3>FROM</h3>
                <p class="info-label">{{ $invoice->from_name }}</p>
                @if($invoice->from_email)
                    <p>{{ $invoice->from_email }}</p>
                @endif
                @if($invoice->from_phone)
                    <p>{{ $invoice->from_phone }}</p>
                @endif
                @if($invoice->from_address)
                    <p>{{ $invoice->from_address }}</p>
                @endif
                @if($invoice->from_bank_name || $invoice->from_account_number)
                    <div class="bank-details">
                        <h4>BANK DETAILS</h4>
                        @if($invoice->from_account_number)
                            <p><strong>Account Number:</strong> {{ $invoice->from_account_number }}</p>
                        @endif
                        @if($invoice->from_account_name)
                            <p><strong>Name on Account:</strong> {{ $invoice->from_account_name }}</p>
                        @endif
                        @if($invoice->from_bank_name)
                            <p><strong>Bank:</strong> {{ $invoice->from_bank_name }}</p>
                        @endif
                    </div>
                @endif
            </div>
            <div class="info-box">
                <h3>BILL TO</h3>
                <p class="info-label">{{ $invoice->to_name }}</p>
                @if($invoice->to_email)
                    <p>{{ $invoice->to_email }}</p>
                @endif
                @if($invoice->to_phone)
                    <p>{{ $invoice->to_phone }}</p>
                @endif
                @if($invoice->to_address)
                    <p>{{ $invoice->to_address }}</p>
                @endif
            </div>
        </div>

        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th class="text-center" style="width: 15%;">Quantity</th>
                    <th class="text-right" style="width: 17.5%;">Unit Price</th>
                    <th class="text-right" style="width: 17.5%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item['description'] }}</td>
                    <td class="text-center">{{ number_format($item['quantity'], 2) }}</td>
                    <td class="text-right">₦{{ number_format($item['unit_price'], 2) }}</td>
                    <td class="text-right">₦{{ number_format($item['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td class="text-right"><strong>₦{{ number_format($invoice->subtotal, 2) }}</strong></td>
            </tr>
            @if($invoice->vat_percentage > 0)
            <tr>
                <td>VAT ({{ number_format($invoice->vat_percentage, 2) }}%):</td>
                <td class="text-right"><strong style="color: #dc2626;">+₦{{ number_format($invoice->vat_amount, 2) }}</strong></td>
            </tr>
            @endif
            @if($invoice->wht_percentage > 0)
            <tr>
                <td>WHT ({{ number_format($invoice->wht_percentage, 2) }}%):</td>
                <td class="text-right"><strong style="color: #dc2626;">-₦{{ number_format($invoice->wht_amount, 2) }}</strong></td>
            </tr>
            @endif
            @if($invoice->discount_percentage > 0)
            <tr>
                <td>Discount ({{ number_format($invoice->discount_percentage, 2) }}%):</td>
                <td class="text-right"><strong style="color: #16a34a;">-₦{{ number_format($invoice->discount_amount, 2) }}</strong></td>
            </tr>
            @endif
            <tr class="total-row">
                <td style="font-size: 11pt;">TOTAL:</td>
                <td class="text-right" style="font-size: 12pt; color: #7c3aed;">₦{{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </table>

        <!-- Notes -->
        @if($invoice->notes)
        <div class="notes">
            <h3>NOTES</h3>
            <p>{{ $invoice->notes }}</p>
        </div>
        @endif

        <!-- Payment Section -->
        <div class="payment-section">
            <h3>PAY THIS INVOICE</h3>
            <div style="width: 100%;">
                <p>To pay this invoice securely online, visit:</p>
                <p class="payment-link">{{ route('public-invoice.show', $invoice->public_id) }}</p>
                <p style="margin-top: 10px; font-size: 8pt;">Visit the link above to pay via card, bank transfer, or other payment methods.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Generated with Khan Invoice - https://kinvoice.ng</p>
            <p>Professional Nigerian Invoice Management System</p>
        </div>
    </div>
</body>
</html>
