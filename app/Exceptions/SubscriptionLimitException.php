<?php

namespace App\Exceptions;

use Exception;

class SubscriptionLimitException extends Exception
{
    protected string $limitType;
    protected int $currentUsage;
    protected int $limit;

    public function __construct(
        string $limitType,
        int $currentUsage,
        int $limit,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->limitType = $limitType;
        $this->currentUsage = $currentUsage;
        $this->limit = $limit;

        if (empty($message)) {
            $message = "Subscription limit reached for {$limitType}. Current usage: {$currentUsage}/{$limit}";
        }

        parent::__construct($message, $code, $previous);
    }

    public function getLimitType(): string
    {
        return $this->limitType;
    }

    public function getCurrentUsage(): int
    {
        return $this->currentUsage;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Get the exception's context for logging
     */
    public function context(): array
    {
        return [
            'limit_type' => $this->limitType,
            'current_usage' => $this->currentUsage,
            'limit' => $this->limit,
        ];
    }
}
