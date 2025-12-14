<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'type' => $this->type,
            'formatted_type' => $this->formatted_type,
            'status' => $this->status,
            'amount' => (float) $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'currency' => $this->currency,
            'payment_gateway' => $this->payment_gateway,
            'transaction_reference' => $this->transaction_reference,
            'paystack_reference' => $this->paystack_reference,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),

            // Related subscription (when loaded)
            'subscription' => $this->whenLoaded('subscription', function () {
                return [
                    'id' => $this->subscription->id,
                    'plan_name' => $this->subscription->plan->name,
                    'billing_cycle' => $this->subscription->billing_cycle,
                ];
            }),
        ];
    }
}
