<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'public_id' => $this->public_id,
            'status' => $this->status,
            'payment_status' => $this->status, // Use status field for display

            // Customer info
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer?->name,
            'customer_email' => $this->customer?->email,
            'customer_phone' => $this->customer?->phone,

            // Amounts
            'sub_total' => (float) $this->sub_total,
            'discount_total' => (float) $this->discount_total,
            'vat_rate' => (float) $this->vat_rate,
            'vat_amount' => (float) $this->vat_amount,
            'wht_rate' => (float) $this->wht_rate,
            'wht_amount' => (float) $this->wht_amount,
            'total_amount' => (float) $this->total_amount,
            'amount_paid' => (float) $this->amount_paid,
            'amount_due' => (float) ($this->total_amount - $this->amount_paid),

            // Formatted amounts for display
            'formatted_total' => $this->currency . ' ' . number_format($this->total_amount, 2),
            'formatted_amount_due' => $this->currency . ' ' . number_format($this->total_amount - $this->amount_paid, 2),

            // Dates
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),

            // Additional info
            'currency' => $this->currency,
            'notes' => $this->notes,
            'footer' => $this->footer,

            // Business/Bank account info (when business_profile is loaded)
            'business_profile' => $this->whenLoaded('businessProfile', function () {
                return [
                    'business_name' => $this->businessProfile->business_name,
                    'bank_name' => $this->businessProfile->bank_name,
                    'bank_account_name' => $this->businessProfile->bank_account_name,
                    'bank_account_number' => $this->businessProfile->bank_account_number,
                    'bank_account_type' => $this->businessProfile->bank_account_type,
                ];
            }),

            // Line items (when loaded)
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'description' => $item->description,
                        'quantity' => (float) $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'total' => (float) $item->total,
                    ];
                });
            }),

            // URLs
            'public_url' => url('/inv/' . $this->public_id),
            'pdf_url' => url('/invoices/' . $this->id . '/pdf'),
        ];
    }
}
