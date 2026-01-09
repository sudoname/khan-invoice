<?php

namespace App\Services\Invoice;

use App\Models\Invoice;
use App\Models\PublicInvoice;
use Illuminate\Database\Eloquent\Model;

class InvoiceHashService
{
    /**
     * Extract canonical payload from an invoice
     *
     * @param Invoice|PublicInvoice $invoice
     * @return array
     */
    public function canonicalPayload(Model $invoice): array
    {
        if ($invoice instanceof Invoice) {
            return $this->canonicalPayloadForInvoice($invoice);
        }

        if ($invoice instanceof PublicInvoice) {
            return $this->canonicalPayloadForPublicInvoice($invoice);
        }

        throw new \InvalidArgumentException('Invalid invoice type');
    }

    /**
     * Create canonical payload for private Invoice model
     *
     * @param Invoice $invoice
     * @return array
     */
    protected function canonicalPayloadForInvoice(Invoice $invoice): array
    {
        // Load relationships if not already loaded
        $invoice->loadMissing(['customer', 'businessProfile', 'items']);

        return [
            'invoice_meta' => [
                'invoice_number' => $invoice->invoice_number,
                'issue_date' => $invoice->issue_date ? $invoice->issue_date->format('Y-m-d') : null,
                'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : null,
                'currency' => strtoupper($invoice->currency ?? 'NGN'),
            ],
            'seller' => [
                'name' => $invoice->businessProfile?->business_name ?? '',
                'email' => $invoice->user?->email ?? '',
                'phone' => $invoice->businessProfile?->phone_number ?? '',
                'address' => $this->normalizeAddress([
                    $invoice->businessProfile?->address_line1,
                    $invoice->businessProfile?->address_line2,
                    $invoice->businessProfile?->city,
                    $invoice->businessProfile?->state,
                ]),
                'tax_id' => $invoice->businessProfile?->tin ?? '',
            ],
            'buyer' => [
                'name' => $invoice->customer?->name ?? '',
                'company' => $invoice->customer?->company_name ?? '',
                'email' => $invoice->customer?->email ?? '',
                'phone' => $invoice->customer?->phone ?? '',
                'address' => $this->normalizeAddress([
                    $invoice->customer?->address_line1,
                    $invoice->customer?->address_line2,
                    $invoice->customer?->city,
                    $invoice->customer?->state,
                ]),
                'tax_id' => $invoice->customer?->tin ?? '',
            ],
            'line_items' => $invoice->items->map(function ($item) {
                return [
                    'description' => $this->normalizeString($item->description),
                    'quantity' => $this->normalizeDecimal($item->quantity),
                    'unit_price' => $this->normalizeDecimal($item->unit_price),
                    'discount' => $this->normalizeDecimal($item->discount ?? 0),
                    'tax_rate' => $this->normalizeDecimal($item->tax_rate ?? 0),
                    'line_total' => $this->normalizeDecimal($item->line_total),
                ];
            })->values()->toArray(),
            'financial' => [
                'subtotal' => $this->normalizeDecimal($invoice->sub_total),
                'discount_total' => $this->normalizeDecimal($invoice->discount_total ?? 0),
                'vat_rate' => $this->normalizeDecimal($invoice->vat_rate ?? 0),
                'vat_amount' => $this->normalizeDecimal($invoice->vat_amount ?? 0),
                'wht_rate' => $this->normalizeDecimal($invoice->wht_rate ?? 0),
                'wht_amount' => $this->normalizeDecimal($invoice->wht_amount ?? 0),
                'total_amount' => $this->normalizeDecimal($invoice->total_amount),
            ],
            'terms' => [
                'notes' => $this->normalizeString($invoice->notes ?? ''),
                'footer' => $this->normalizeString($invoice->footer ?? ''),
            ],
        ];
    }

    /**
     * Create canonical payload for PublicInvoice model
     *
     * @param PublicInvoice $invoice
     * @return array
     */
    protected function canonicalPayloadForPublicInvoice(PublicInvoice $invoice): array
    {
        return [
            'invoice_meta' => [
                'invoice_number' => $invoice->invoice_number,
                'issue_date' => $invoice->issue_date ? $invoice->issue_date->format('Y-m-d') : null,
                'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : null,
                'currency' => 'NGN', // Public invoices are always NGN
            ],
            'seller' => [
                'name' => $invoice->from_name ?? '',
                'email' => $invoice->from_email ?? '',
                'phone' => $invoice->from_phone ?? '',
                'address' => $this->normalizeString($invoice->from_address ?? ''),
                'bank' => [
                    'name' => $invoice->from_bank_name ?? '',
                    'account_number' => $invoice->from_account_number ?? '',
                    'account_name' => $invoice->from_account_name ?? '',
                    'account_type' => $invoice->from_account_type ?? '',
                ],
            ],
            'buyer' => [
                'name' => $invoice->to_name ?? '',
                'email' => $invoice->to_email ?? '',
                'phone' => $invoice->to_phone ?? '',
                'address' => $this->normalizeString($invoice->to_address ?? ''),
            ],
            'line_items' => collect($invoice->items ?? [])->map(function ($item) {
                return [
                    'description' => $this->normalizeString($item['description'] ?? ''),
                    'quantity' => $this->normalizeDecimal($item['quantity'] ?? 0),
                    'unit_price' => $this->normalizeDecimal($item['unit_price'] ?? 0),
                    'total' => $this->normalizeDecimal($item['total'] ?? 0),
                ];
            })->values()->toArray(),
            'financial' => [
                'subtotal' => $this->normalizeDecimal($invoice->subtotal),
                'vat_percentage' => $this->normalizeDecimal($invoice->vat_percentage ?? 0),
                'vat_amount' => $this->normalizeDecimal($invoice->vat_amount ?? 0),
                'wht_percentage' => $this->normalizeDecimal($invoice->wht_percentage ?? 0),
                'wht_amount' => $this->normalizeDecimal($invoice->wht_amount ?? 0),
                'discount_percentage' => $this->normalizeDecimal($invoice->discount_percentage ?? 0),
                'discount_amount' => $this->normalizeDecimal($invoice->discount_amount ?? 0),
                'total_amount' => $this->normalizeDecimal($invoice->total_amount),
            ],
            'terms' => [
                'notes' => $this->normalizeString($invoice->notes ?? ''),
            ],
        ];
    }

    /**
     * Recursively canonicalize an array (stable key ordering, normalized values)
     *
     * @param array $payload
     * @return array
     */
    public function canonicalize(array $payload): array
    {
        // Sort keys alphabetically for deterministic ordering
        ksort($payload);

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->canonicalize($value);
            } elseif (is_string($value)) {
                $payload[$key] = $this->normalizeString($value);
            }
        }

        return $payload;
    }

    /**
     * Convert canonical payload to JSON string
     *
     * @param array $payload
     * @return string
     */
    public function canonicalJson(array $payload): string
    {
        return json_encode(
            $this->canonicalize($payload),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * Compute SHA-256 hash for an invoice
     *
     * @param Invoice|PublicInvoice $invoice
     * @return string
     */
    public function computeHash(Model $invoice): string
    {
        $payload = $this->canonicalPayload($invoice);
        $json = $this->canonicalJson($payload);

        return hash('sha256', $json);
    }

    /**
     * Update hash for an invoice and save to database
     *
     * @param Invoice|PublicInvoice $invoice
     * @return void
     */
    public function updateHash(Model $invoice): void
    {
        $hash = $this->computeHash($invoice);

        $invoice->document_hash = $hash;
        $invoice->document_hash_updated_at = now();
        $invoice->document_hash_version = 1;

        // Use saveQuietly to avoid triggering observers
        $invoice->saveQuietly();
    }

    /**
     * Normalize a string: trim, normalize line endings, collapse multiple spaces
     *
     * @param string|null $value
     * @return string
     */
    protected function normalizeString(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Trim whitespace
        $value = trim($value);

        // Normalize line endings to \n
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        // Collapse multiple spaces (but preserve single newlines)
        $value = preg_replace('/[^\S\n]+/', ' ', $value);

        // Collapse multiple newlines to maximum of 2
        $value = preg_replace('/\n{3,}/', "\n\n", $value);

        return $value;
    }

    /**
     * Normalize a decimal number to fixed 2 decimal places
     *
     * @param mixed $value
     * @return string
     */
    protected function normalizeDecimal($value): string
    {
        if ($value === null) {
            return '0.00';
        }

        // Convert to float then format to 2 decimal places
        return number_format((float) $value, 2, '.', '');
    }

    /**
     * Normalize address from array of lines
     *
     * @param array $lines
     * @return string
     */
    protected function normalizeAddress(array $lines): string
    {
        // Filter out empty lines and join with commas
        $filtered = array_filter($lines, fn($line) => !empty($line));

        return $this->normalizeString(implode(', ', $filtered));
    }
}
