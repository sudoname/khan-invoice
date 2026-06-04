<?php

namespace App\Http\Resources;

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
            'issue_date' => $this->issue_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->status,
            'currency' => $this->currency,
            'sub_total' => (float) $this->sub_total,
            'discount_total' => (float) $this->discount_total,
            'vat_rate' => (float) $this->vat_rate,
            'vat_amount' => (float) $this->vat_amount,
            'wht_rate' => (float) $this->wht_rate,
            'wht_amount' => (float) $this->wht_amount,
            'total_amount' => (float) $this->total_amount,
            'amount_paid' => (float) $this->amount_paid,
            'balance_due' => (float) ($this->total_amount - $this->amount_paid),
            'notes' => $this->notes,
            'footer' => $this->footer,
            'public_id' => $this->public_id,
            'payment_status' => $this->payment_status,
            'paid_at' => $this->paid_at?->toIso8601String(),
            
            // Public URLs using public_id (use /inv/ for regular user invoices)
            'public_url' => url('/inv/' . $this->public_id),
            'pdf_url' => url('/inv/' . $this->public_id . '/download'),
            
            // Customer data
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer?->name,
            'customer_email' => $this->customer?->email,
            'customer_phone' => $this->customer?->phone,
            
            // Formatted values for display
            'formatted_total' => '₦' . number_format($this->total_amount, 2),
            'formatted_amount_due' => '₦' . number_format($this->total_amount - $this->amount_paid, 2),
            
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'company_name' => $this->customer->company_name,
                    'email' => $this->customer->email,
                    'phone' => $this->customer->phone,
                ];
            }),
            'items' => $this->whenLoaded('items'),
            'payments' => $this->whenLoaded('payments'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
