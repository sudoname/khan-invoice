<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Receipt</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
        }
        .container {
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }
        .logo {
            font-size: 24pt;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        h1 {
            font-size: 18pt;
            color: #333;
            margin-bottom: 5px;
        }
        .receipt-number {
            font-size: 10pt;
            color: #666;
            margin-top: 10px;
        }
        .change-type {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 11pt;
            font-weight: bold;
            margin: 15px 0;
        }
        .change-type.upgrade {
            background-color: #4caf50;
            color: white;
        }
        .change-type.downgrade {
            background-color: #ff9800;
            color: white;
        }
        .info-section {
            margin: 25px 0;
        }
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .info-label {
            display: table-cell;
            width: 35%;
            color: #666;
            font-weight: normal;
        }
        .info-value {
            display: table-cell;
            font-weight: bold;
            color: #333;
        }
        .plan-comparison {
            margin: 30px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .plan-comparison h3 {
            font-size: 12pt;
            margin-bottom: 15px;
            color: #667eea;
        }
        .plan-row {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }
        .plan-label {
            display: table-cell;
            width: 30%;
            color: #666;
        }
        .plan-old {
            display: table-cell;
            width: 35%;
            padding: 0 10px;
        }
        .plan-new {
            display: table-cell;
            width: 35%;
            padding: 0 10px;
            font-weight: bold;
            color: #667eea;
        }
        .plan-header {
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #ddd;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .amount-section {
            margin: 30px 0;
            padding: 20px;
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            border-radius: 4px;
        }
        .amount-section.credit {
            background-color: #fff3e0;
            border-left-color: #ff9800;
        }
        .amount-label {
            font-size: 11pt;
            color: #666;
            margin-bottom: 8px;
        }
        .amount-value {
            font-size: 20pt;
            font-weight: bold;
            color: #2196f3;
        }
        .amount-value.credit {
            color: #ff9800;
        }
        .features-section {
            margin: 25px 0;
        }
        .features-section h3 {
            font-size: 12pt;
            margin-bottom: 12px;
            color: #333;
        }
        .feature-item {
            padding: 5px 0;
            font-size: 10pt;
        }
        .feature-item::before {
            content: "✓ ";
            color: #4caf50;
            font-weight: bold;
            margin-right: 5px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            text-align: center;
            color: #999;
            font-size: 9pt;
        }
        .footer p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">{{ config('app.name') }}</div>
            <h1>Subscription Receipt</h1>
            <div class="receipt-number">Receipt #{{ $subscription->id }}-{{ now()->format('YmdHis') }}</div>
            <div class="change-type {{ $changeType }}">
                {{ ucfirst($changeType) }}
            </div>
        </div>

        <!-- Customer Information -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-label">Customer Name:</div>
                <div class="info-value">{{ $user->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $user->email }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Transaction Date:</div>
                <div class="info-value">{{ now()->format('F j, Y \a\t g:i A') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Billing Cycle:</div>
                <div class="info-value">{{ ucfirst($subscription->billing_cycle) }}</div>
            </div>
            @if($subscription->current_period_end)
            <div class="info-row">
                <div class="info-label">Next Billing Date:</div>
                <div class="info-value">{{ $subscription->current_period_end->format('F j, Y') }}</div>
            </div>
            @endif
        </div>

        <!-- Plan Comparison -->
        <div class="plan-comparison">
            <h3>Plan Change Summary</h3>

            <div class="plan-row plan-header">
                <div class="plan-label"></div>
                <div class="plan-old">Previous Plan</div>
                <div class="plan-new">New Plan</div>
            </div>

            <div class="plan-row">
                <div class="plan-label">Plan Name:</div>
                <div class="plan-old">{{ $oldPlan->name }}</div>
                <div class="plan-new">{{ $newPlan->name }}</div>
            </div>

            <div class="plan-row">
                <div class="plan-label">Price:</div>
                <div class="plan-old">
                    @if($subscription->billing_cycle === 'yearly')
                        ₦{{ number_format($oldPlan->price_yearly, 2) }}/year
                    @else
                        ₦{{ number_format($oldPlan->price_monthly, 2) }}/month
                    @endif
                </div>
                <div class="plan-new">
                    @if($subscription->billing_cycle === 'yearly')
                        ₦{{ number_format($newPlan->price_yearly, 2) }}/year
                    @else
                        ₦{{ number_format($newPlan->price_monthly, 2) }}/month
                    @endif
                </div>
            </div>

            <div class="plan-row">
                <div class="plan-label">Invoices:</div>
                <div class="plan-old">
                    {{ $oldPlan->max_invoices == -1 ? 'Unlimited' : $oldPlan->max_invoices . '/month' }}
                </div>
                <div class="plan-new">
                    {{ $newPlan->max_invoices == -1 ? 'Unlimited' : $newPlan->max_invoices . '/month' }}
                </div>
            </div>

            <div class="plan-row">
                <div class="plan-label">Customers:</div>
                <div class="plan-old">
                    {{ $oldPlan->max_customers == -1 ? 'Unlimited' : $oldPlan->max_customers }}
                </div>
                <div class="plan-new">
                    {{ $newPlan->max_customers == -1 ? 'Unlimited' : $newPlan->max_customers }}
                </div>
            </div>

            <div class="plan-row">
                <div class="plan-label">Team Members:</div>
                <div class="plan-old">
                    {{ $oldPlan->max_team_members == -1 ? 'Unlimited' : $oldPlan->max_team_members }}
                </div>
                <div class="plan-new">
                    {{ $newPlan->max_team_members == -1 ? 'Unlimited' : $newPlan->max_team_members }}
                </div>
            </div>
        </div>

        <!-- Amount Charged/Credit Issued -->
        @if($changeType === 'upgrade' && $amountCharged)
            <div class="amount-section">
                <div class="amount-label">Amount Charged:</div>
                <div class="amount-value">₦{{ number_format($amountCharged, 2) }}</div>
                <p style="margin-top: 10px; font-size: 9pt; color: #666;">
                    This is the prorated amount charged for upgrading to {{ $newPlan->name }} for the remainder of your billing period.
                </p>
            </div>
        @endif

        @if($changeType === 'downgrade' && $creditIssued)
            <div class="amount-section credit">
                <div class="amount-label">Credit Issued:</div>
                <div class="amount-value credit">₦{{ number_format($creditIssued, 2) }}</div>
                <p style="margin-top: 10px; font-size: 9pt; color: #666;">
                    This prorated credit has been added to your account and will be automatically applied to your next payment.
                </p>
            </div>
        @endif

        <!-- New Plan Features -->
        <div class="features-section">
            <h3>Your New Plan Features:</h3>

            @if($newPlan->max_invoices == -1)
                <div class="feature-item">Unlimited invoices per month</div>
            @else
                <div class="feature-item">{{ $newPlan->max_invoices }} invoices per month</div>
            @endif

            @if($newPlan->max_customers == -1)
                <div class="feature-item">Unlimited customers</div>
            @else
                <div class="feature-item">{{ $newPlan->max_customers }} customers</div>
            @endif

            @if($newPlan->max_team_members == -1)
                <div class="feature-item">Unlimited team members</div>
            @else
                <div class="feature-item">{{ $newPlan->max_team_members }} team members</div>
            @endif

            @if($newPlan->sms_credits_monthly > 0)
                <div class="feature-item">{{ $newPlan->sms_credits_monthly }} SMS credits per month</div>
            @endif

            @if($newPlan->whatsapp_credits_monthly > 0)
                <div class="feature-item">{{ $newPlan->whatsapp_credits_monthly }} WhatsApp credits per month</div>
            @endif

            @if($newPlan->api_access)
                <div class="feature-item">API access</div>
            @endif

            @if($newPlan->multi_currency)
                <div class="feature-item">Multi-currency support</div>
            @endif

            @if($newPlan->recurring_invoices)
                <div class="feature-item">Recurring invoices</div>
            @endif

            @if($newPlan->advanced_reports)
                <div class="feature-item">Advanced reports & analytics</div>
            @endif

            @if($newPlan->priority_support)
                <div class="feature-item">Priority customer support</div>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Thank you for choosing {{ config('app.name') }}!</strong></p>
            <p>If you have any questions about your subscription, please contact our support team.</p>
            <p style="margin-top: 15px;">This is an automatically generated receipt.</p>
        </div>
    </div>
</body>
</html>
