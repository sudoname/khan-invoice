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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            margin: -20px -20px 15px -20px;
        }
        .header-content {
            display: table;
            width: 100%;
        }
        .header-left, .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .header-right {
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
            text-transform: uppercase;
            color: #666;
            font-weight: bold;
        }
        .info-section {
            margin-bottom: 25px;
        }
        .two-column {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }
        .column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .right-align {
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background-color: #f5f5f5;
            padding: 6px 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #ddd;
            font-size: 9pt;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #eee;
            font-size: 9pt;
        }
        .text-right {
            text-align: right;
        }
        .totals-table {
            width: 40%;
            margin-left: auto;
            margin-top: 20px;
        }
        .totals-table td {
            padding: 5px 8px;
            border: none;
            font-size: 9pt;
        }
        .total-row {
            font-weight: bold;
            font-size: 10pt;
            border-top: 2px solid #333 !important;
        }
        .banking-info {
            background-color: #e3f2fd;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #2196f3;
        }
        .footer-notes {
            background-color: #f5f5f5;
            padding: 10px;
            margin: 20px 0;
            font-size: 10pt;
        }
        .footer-text {
            text-align: center;
            margin-top: 30px;
            font-size: 9pt;
            color: #999;
        }
        .label {
            color: #666;
            font-weight: normal;
        }
        .value {
            font-weight: bold;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 9pt;
            font-weight: bold;
        }
        .status-paid { background-color: #4caf50; color: white; }
        .status-sent { background-color: #ff9800; color: white; }
        .status-draft { background-color: #9e9e9e; color: white; }
        .status-overdue { background-color: #f44336; color: white; }
        .status-partially_paid { background-color: #2196f3; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="header-left">
                    @if($businessProfile && $businessProfile->logo_url)
                        @php
                            $logoPath = storage_path('app/public/' . $businessProfile->logo_url);
                            if (file_exists($logoPath)) {
                                $logoData = base64_encode(file_get_contents($logoPath));
                                $logoMime = mime_content_type($logoPath);
                                $logoSrc = "data:{$logoMime};base64,{$logoData}";
                            }
                        @endphp
                        @if(isset($logoSrc))
                            <img src="{{ $logoSrc }}" alt="Logo" style="max-height: 40px; max-width: 150px; margin-bottom: 8px;">
                        @endif
                    @endif
                    <h1>INVOICE</h1>
                    <p style="font-size: 9pt;">{{ $invoice->invoice_number }}</p>
                </div>
                <div class="header-right">
                    @if($businessProfile)
                        <h2>{{ $businessProfile->business_name }}</h2>
                        @if($businessProfile->address_line1)
                            <p style="font-size: 8pt;">{{ $businessProfile->address_line1 }}</p>
                        @endif
                        @if($businessProfile->city || $businessProfile->state)
                            <p style="font-size: 8pt;">{{ $businessProfile->city }}@if($businessProfile->city && $businessProfile->state), @endif{{ $businessProfile->state }}</p>
                        @endif
                        @if($businessProfile->phone)
                            <p style="font-size: 8pt;">{{ $businessProfile->phone }}</p>
                        @endif
                        @if($businessProfile->email)
                            <p style="font-size: 8pt;">{{ $businessProfile->email }}</p>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <!-- Invoice Info & Customer -->
        <div class="two-column">
            <!-- Bill To -->
            <div class="column">
                <h3>Bill To</h3>
                <p style="font-size: 10pt; font-weight: bold; margin-bottom: 3px;">{{ $invoice->customer->name }}</p>
                @if($invoice->customer->company_name)
                    <p style="font-size: 9pt;">{{ $invoice->customer->company_name }}</p>
                @endif
                @if($invoice->customer->email)
                    <p style="font-size: 9pt;">{{ $invoice->customer->email }}</p>
                @endif
                @if($invoice->customer->phone)
                    <p style="font-size: 9pt;">{{ $invoice->customer->phone }}</p>
                @endif
                @if($invoice->customer->address_line1)
                    <p style="margin-top: 5px; font-size: 9pt;">{{ $invoice->customer->address_line1 }}</p>
                @endif
                @if($invoice->customer->city || $invoice->customer->state)
                    <p style="font-size: 9pt;">{{ $invoice->customer->city }}@if($invoice->customer->city && $invoice->customer->state), @endif{{ $invoice->customer->state }}</p>
                @endif
                @if($invoice->customer->tin)
                    <p style="margin-top: 5px; font-size: 9pt;"><span class="label">TIN:</span> {{ $invoice->customer->tin }}</p>
                @endif
            </div>

            <!-- Invoice Details -->
            <div class="column right-align">
                <p><span class="label">Issue Date:</span> <span class="value">{{ $invoice->issue_date->format('M d, Y') }}</span></p>
                <p><span class="label">Due Date:</span> <span class="value">{{ $invoice->due_date->format('M d, Y') }}</span></p>
                <p style="margin: 8px 0;">
                    <span class="label">Status:</span>
                    <span class="status-badge status-{{ $invoice->status }}">
                        {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                    </span>
                </p>
                @if($businessProfile)
                    @if($businessProfile->cac_number)
                        <p style="margin-top: 15px;"><span class="label">CAC:</span> {{ $businessProfile->cac_number }}</p>
                    @endif
                    @if($businessProfile->tin)
                        <p><span class="label">TIN:</span> {{ $businessProfile->tin }}</p>
                    @endif
                @endif
            </div>
        </div>

        <!-- Line Items -->
        <table>
            <thead>
                <tr>
                    <th style="width: 40%;">Description</th>
                    <th class="text-right" style="width: 12%;">Qty</th>
                    <th class="text-right" style="width: 18%;">Unit Price</th>
                    <th class="text-right" style="width: 15%;">Discount</th>
                    <th class="text-right" style="width: 15%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-right">₦{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right" style="color: #f44336;">
                            @if($item->discount > 0)
                                -₦{{ number_format($item->discount, 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right"><strong>₦{{ number_format($item->line_total, 2) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="text-right value">₦{{ number_format($invoice->sub_total, 2) }}</td>
            </tr>
            @if($invoice->discount_total > 0)
                <tr>
                    <td class="label">Discount:</td>
                    <td class="text-right value" style="color: #f44336;">-₦{{ number_format($invoice->discount_total, 2) }}</td>
                </tr>
            @endif
            <tr>
                <td class="label">VAT ({{ number_format($invoice->vat_rate, 1) }}%):</td>
                <td class="text-right value">₦{{ number_format($invoice->vat_amount, 2) }}</td>
            </tr>
            @if($invoice->wht_amount && $invoice->wht_amount > 0)
                <tr>
                    <td class="label">WHT ({{ number_format($invoice->wht_rate, 1) }}%):</td>
                    <td class="text-right value" style="color: #f44336;">-₦{{ number_format($invoice->wht_amount, 2) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td>Total:</td>
                <td class="text-right" style="color: #764ba2;">₦{{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
            @if($invoice->amount_paid > 0)
                <tr>
                    <td class="label">Amount Paid:</td>
                    <td class="text-right value" style="color: #4caf50;">₦{{ number_format($invoice->amount_paid, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Balance Due:</td>
                    <td class="text-right" style="color: #f44336;">₦{{ number_format($invoice->total_amount - $invoice->amount_paid, 2) }}</td>
                </tr>
            @endif
        </table>

        <!-- Banking Information -->
        @if($businessProfile && $businessProfile->bank_name)
            <div class="banking-info">
                <h3 style="color: #1976d2; margin-bottom: 10px;">Payment Information</h3>
                <table style="width: 100%; margin: 0;">
                    <tr>
                        <td style="width: 25%; padding: 5px; border: none;"><span class="label">Bank Name:</span></td>
                        <td style="padding: 5px; border: none;"><strong>{{ $businessProfile->bank_name }}</strong></td>
                    </tr>
                    @if($businessProfile->bank_account_name)
                        <tr>
                            <td style="padding: 5px; border: none;"><span class="label">Account Name:</span></td>
                            <td style="padding: 5px; border: none;"><strong>{{ $businessProfile->bank_account_name }}</strong></td>
                        </tr>
                    @endif
                    @if($businessProfile->bank_account_number)
                        <tr>
                            <td style="padding: 5px; border: none;"><span class="label">Account Number:</span></td>
                            <td style="padding: 5px; border: none;"><strong style="font-size: 12pt;">{{ $businessProfile->bank_account_number }}</strong></td>
                        </tr>
                    @endif
                    @if($businessProfile->bank_account_type)
                        <tr>
                            <td style="padding: 5px; border: none;"><span class="label">Account Type:</span></td>
                            <td style="padding: 5px; border: none;"><strong>{{ ucfirst($businessProfile->bank_account_type) }}</strong></td>
                        </tr>
                    @endif
                </table>
            </div>
        @endif

        <!-- Notes -->
        @if($invoice->footer)
            <div class="footer-notes">
                <p style="white-space: pre-line;">{{ $invoice->footer }}</p>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer-text">
            <p>Thank you for your business!</p>
            <p style="margin-top: 5px;">Generated with Khan Invoice</p>
        </div>
    </div>
</body>
</html>
