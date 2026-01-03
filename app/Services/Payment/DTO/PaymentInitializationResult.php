<?php

namespace App\Services\Payment\DTO;

class PaymentInitializationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $reference,
        public readonly ?string $authorizationUrl,
        public readonly ?string $accessCode,
        public readonly array $metadata = [],
        public readonly ?string $errorMessage = null
    ) {}

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function hasFailed(): bool
    {
        return !$this->success;
    }

    public static function successful(
        string $reference,
        string $authorizationUrl,
        ?string $accessCode = null,
        array $metadata = []
    ): self {
        return new self(
            success: true,
            reference: $reference,
            authorizationUrl: $authorizationUrl,
            accessCode: $accessCode,
            metadata: $metadata
        );
    }

    public static function failed(string $errorMessage): self
    {
        return new self(
            success: false,
            reference: null,
            authorizationUrl: null,
            accessCode: null,
            metadata: [],
            errorMessage: $errorMessage
        );
    }
}
