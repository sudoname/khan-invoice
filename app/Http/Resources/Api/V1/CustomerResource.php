<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'tax_id' => $this->tax_id,
            'notes' => $this->notes,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),

            // Invoice stats (when loaded)
            'invoices_count' => $this->whenCounted('invoices'),
            'total_invoiced' => $this->when(isset($this->total_invoiced), function () {
                return (float) $this->total_invoiced;
            }),
            'total_paid' => $this->when(isset($this->total_paid), function () {
                return (float) $this->total_paid;
            }),
            'total_outstanding' => $this->when(isset($this->total_outstanding), function () {
                return (float) $this->total_outstanding;
            }),
        ];
    }
}
