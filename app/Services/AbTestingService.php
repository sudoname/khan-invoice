<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

class AbTestingService
{
    /**
     * Get or assign variant for a test
     *
     * @param string $testName Test identifier (e.g., 'dashboard_cta')
     * @param array $variants Array of variant names (e.g., ['control', 'variant_a'])
     * @param array $weights Optional weights for each variant (default: equal distribution)
     * @return string The assigned variant
     */
    public function getVariant(string $testName, array $variants, array $weights = []): string
    {
        $sessionKey = "ab_test_{$testName}";

        // Check if user already has an assigned variant
        if (Session::has($sessionKey)) {
            $existingVariant = Session::get($sessionKey);

            // Verify the variant is still valid
            if (in_array($existingVariant, $variants)) {
                return $existingVariant;
            }
        }

        // Assign new variant using weighted random selection
        $variant = $this->assignVariant($variants, $weights);

        // Store variant in session
        Session::put($sessionKey, $variant);

        // Also store in user settings if authenticated
        if (auth()->check()) {
            $this->storeUserVariant(auth()->id(), $testName, $variant);
        }

        // Track variant assignment
        $analytics = app(AnalyticsService::class);
        $analytics->trackAbTestVariant($testName, $variant, auth()->id());

        return $variant;
    }

    /**
     * Assign variant using weighted random selection
     *
     * @param array $variants
     * @param array $weights
     * @return string
     */
    protected function assignVariant(array $variants, array $weights = []): string
    {
        // If no weights provided, use equal distribution
        if (empty($weights)) {
            $weights = array_fill(0, count($variants), 1);
        }

        // Normalize weights
        $totalWeight = array_sum($weights);
        $normalizedWeights = array_map(fn($w) => $w / $totalWeight, $weights);

        // Random selection based on weights
        $random = mt_rand() / mt_getrandmax();
        $cumulativeWeight = 0;

        foreach ($variants as $index => $variant) {
            $cumulativeWeight += $normalizedWeights[$index];
            if ($random <= $cumulativeWeight) {
                return $variant;
            }
        }

        // Fallback (should never reach here)
        return $variants[0];
    }

    /**
     * Store variant assignment in user settings (for persistent tracking)
     *
     * @param int $userId
     * @param string $testName
     * @param string $variant
     */
    protected function storeUserVariant(int $userId, string $testName, string $variant): void
    {
        try {
            $user = \App\Models\User::find($userId);
            if ($user) {
                $settings = $user->settings ?? [];
                $settings["ab_test_{$testName}"] = [
                    'variant' => $variant,
                    'assigned_at' => now()->toIso8601String(),
                ];
                $user->settings = $settings;
                $user->save();
            }
        } catch (\Exception $e) {
            // Fail silently - don't break user experience
            \Illuminate\Support\Facades\Log::warning("Failed to store A/B test variant: {$e->getMessage()}");
        }
    }

    /**
     * Track conversion for a test
     *
     * @param string $testName
     * @param string $conversionType (e.g., 'clicked_quick', 'clicked_advanced', 'invoice_created')
     */
    public function trackConversion(string $testName, string $conversionType): void
    {
        $sessionKey = "ab_test_{$testName}";
        $variant = Session::get($sessionKey);

        if (!$variant) {
            return;
        }

        $analytics = app(AnalyticsService::class);
        $analytics->trackAbTestConversion($testName, $variant, $conversionType, auth()->id());
    }

    /**
     * Get test results for admin dashboard
     *
     * @param string $testName
     * @return array
     */
    public function getTestResults(string $testName): array
    {
        // This would typically query a database or analytics API
        // For now, return a placeholder structure
        return [
            'test_name' => $testName,
            'variants' => [],
            'note' => 'View detailed results in Google Analytics 4',
        ];
    }

    /**
     * Check if a test is active
     *
     * @param string $testName
     * @return bool
     */
    public function isTestActive(string $testName): bool
    {
        // You can implement logic to enable/disable tests
        // For now, check if it's in the config or always return true
        $activeTests = config('ab_testing.active_tests', []);

        if (empty($activeTests)) {
            // If no config, all tests are active
            return true;
        }

        return in_array($testName, $activeTests);
    }

    /**
     * Force a specific variant for testing (useful for QA)
     *
     * @param string $testName
     * @param string $variant
     */
    public function forceVariant(string $testName, string $variant): void
    {
        $sessionKey = "ab_test_{$testName}";
        Session::put($sessionKey, $variant);
    }
}
