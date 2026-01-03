<?php

namespace App\Services\Payment\DTO;

class PaymentVerificationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?string $reference,
        public readonly ?float $amount,
        public readonly ?float $fees,
        public readonly ?string $currency,
        public readonly ?string $channel,
        public readonly ?string $paidAt,
        public readonly array $metadata = [],
        public readonly ?string $errorMessage = null
    ) {}

    public function isSuccessful(): bool
    {
        return $this->success && $this->status === 'success';
    }

    public function hasFailed(): bool
    {
        return !$this->success || $this->status !== 'success';
    }

    public function getNetAmount(): float
    {
        if ($this->amount === null || $this->fees === null) {
            return 0.0;
        }

        return $this->amount - $this->fees;
    }

    public static function successful(
        string $reference,
        float $amount,
        float $fees,
        string $currency,
        string $channel,
        string $paidAt,
        array $metadata = []
    ): self {
        return new self(
            success: true,
            status: 'success',
            reference: $reference,
            amount: $amount,
            fees: $fees,
            currency: $currency,
            channel: $channel,
            paidAt: $paidAt,
            metadata: $metadata
        );
    }

    public static function failed(string $errorMessage, string $reference = null): self
    {
        return new self(
            success: false,
            status: 'failed',
            reference: $reference,
            amount: null,
            fees: null,
            currency: null,
            channel: null,
            paidAt: null,
            metadata: [],
            errorMessage: $errorMessage
        );
    }
}
