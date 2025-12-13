<div class="space-y-4 text-sm">
    <div>
        <h4 class="font-semibold mb-2">Authentication</h4>
        <ul class="list-disc list-inside space-y-1 text-gray-600 dark:text-gray-400">
            <li><code class="text-xs">POST /api/v1/auth/token</code> - Create API token</li>
            <li><code class="text-xs">POST /api/v1/auth/revoke</code> - Revoke current token</li>
        </ul>
    </div>

    <div>
        <h4 class="font-semibold mb-2">Invoices</h4>
        <ul class="list-disc list-inside space-y-1 text-gray-600 dark:text-gray-400">
            <li><code class="text-xs">GET /api/v1/invoices</code> - List all invoices</li>
            <li><code class="text-xs">POST /api/v1/invoices</code> - Create new invoice</li>
            <li><code class="text-xs">GET /api/v1/invoices/{id}</code> - Get invoice details</li>
            <li><code class="text-xs">PUT /api/v1/invoices/{id}</code> - Update invoice</li>
            <li><code class="text-xs">DELETE /api/v1/invoices/{id}</code> - Delete invoice</li>
        </ul>
    </div>

    <div>
        <h4 class="font-semibold mb-2">Customers</h4>
        <ul class="list-disc list-inside space-y-1 text-gray-600 dark:text-gray-400">
            <li><code class="text-xs">GET /api/v1/customers</code> - List all customers</li>
            <li><code class="text-xs">POST /api/v1/customers</code> - Create new customer</li>
            <li><code class="text-xs">GET /api/v1/customers/{id}</code> - Get customer details</li>
            <li><code class="text-xs">PUT /api/v1/customers/{id}</code> - Update customer</li>
            <li><code class="text-xs">DELETE /api/v1/customers/{id}</code> - Delete customer</li>
        </ul>
    </div>

    <div>
        <h4 class="font-semibold mb-2">Payments</h4>
        <ul class="list-disc list-inside space-y-1 text-gray-600 dark:text-gray-400">
            <li><code class="text-xs">GET /api/v1/payments</code> - List all payments</li>
            <li><code class="text-xs">POST /api/v1/payments</code> - Record new payment</li>
        </ul>
    </div>

    <div>
        <h4 class="font-semibold mb-2">Reports</h4>
        <ul class="list-disc list-inside space-y-1 text-gray-600 dark:text-gray-400">
            <li><code class="text-xs">GET /api/v1/reports/sales</code> - Sales report (requires start_date, end_date)</li>
            <li><code class="text-xs">GET /api/v1/reports/aging</code> - Accounts receivable aging</li>
            <li><code class="text-xs">GET /api/v1/reports/profit-loss</code> - P&L statement (requires start_date, end_date)</li>
        </ul>
    </div>
</div>
