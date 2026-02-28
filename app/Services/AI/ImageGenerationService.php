<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class ImageGenerationService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.openai.com/v1/images/generations';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');

        if (empty($this->apiKey)) {
            throw new Exception('OpenAI API key not configured. Please set OPENAI_API_KEY in .env');
        }
    }

    /**
     * Generate image using DALL-E 3
     *
     * @param string $prompt The image generation prompt
     * @param int $width Image width in pixels
     * @param int $height Image height in pixels
     * @param string $quality 'standard' or 'hd'
     * @return array ['url' => string, 'revised_prompt' => string]
     * @throws Exception
     */
    public function generateWithDallE3(
        string $prompt,
        int $width = 1024,
        int $height = 1024,
        string $quality = 'hd'
    ): array {
        // DALL-E 3 only supports specific sizes
        $size = $this->getValidDallE3Size($width, $height);

        Log::info('Generating image with DALL-E 3', [
            'prompt_length' => strlen($prompt),
            'size' => $size,
            'quality' => $quality,
        ]);

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->apiUrl, [
                    'model' => 'dall-e-3',
                    'prompt' => $prompt,
                    'size' => $size,
                    'quality' => $quality,
                    'n' => 1,
                    'response_format' => 'url',
                ]);

            if ($response->failed()) {
                $error = $response->json()['error']['message'] ?? 'Unknown error';
                throw new Exception("DALL-E 3 API error: {$error}");
            }

            $data = $response->json();

            Log::info('DALL-E 3 image generated successfully', [
                'url' => $data['data'][0]['url'] ?? null,
            ]);

            return [
                'url' => $data['data'][0]['url'],
                'revised_prompt' => $data['data'][0]['revised_prompt'] ?? $prompt,
            ];

        } catch (Exception $e) {
            Log::error('DALL-E 3 generation failed', [
                'error' => $e->getMessage(),
                'prompt' => substr($prompt, 0, 200),
            ]);

            throw new Exception("Failed to generate image: {$e->getMessage()}");
        }
    }

    /**
     * Download image from URL and save to storage
     *
     * @param string $imageUrl Remote image URL
     * @param string $filename Desired filename
     * @param string $disk Storage disk name
     * @return array ['path' => string, 'size' => int]
     * @throws Exception
     */
    public function downloadAndSave(string $imageUrl, string $filename, string $disk = 'public'): array
    {
        try {
            Log::info('Downloading image from DALL-E 3', [
                'url' => $imageUrl,
                'filename' => $filename,
            ]);

            // Download image content
            $response = Http::timeout(60)->get($imageUrl);

            if ($response->failed()) {
                throw new Exception('Failed to download image from DALL-E 3');
            }

            $imageContent = $response->body();
            $fileSize = strlen($imageContent);

            // Ensure directory exists
            $directory = 'marketing-designs';
            Storage::disk($disk)->makeDirectory($directory);

            // Save to storage
            $path = "{$directory}/{$filename}";
            Storage::disk($disk)->put($path, $imageContent);

            Log::info('Image downloaded and saved successfully', [
                'path' => $path,
                'size' => $fileSize,
            ]);

            return [
                'path' => $path,
                'size' => $fileSize,
                'url' => Storage::disk($disk)->url($path),
            ];

        } catch (Exception $e) {
            Log::error('Failed to download and save image', [
                'error' => $e->getMessage(),
                'url' => $imageUrl,
            ]);

            throw new Exception("Failed to save image: {$e->getMessage()}");
        }
    }

    /**
     * Get valid DALL-E 3 size based on requested dimensions
     *
     * DALL-E 3 only supports: 1024x1024, 1792x1024, 1024x1792
     *
     * @param int $width
     * @param int $height
     * @return string
     */
    private function getValidDallE3Size(int $width, int $height): string
    {
        $aspectRatio = $width / $height;

        // Square (1:1) - Instagram Post
        if ($aspectRatio >= 0.9 && $aspectRatio <= 1.1) {
            return '1024x1024';
        }

        // Portrait (9:16) - WhatsApp Status, Instagram Story
        if ($aspectRatio < 0.9) {
            return '1024x1792';
        }

        // Landscape (16:9) - Facebook Post
        if ($aspectRatio > 1.1) {
            return '1792x1024';
        }

        // Default to square
        return '1024x1024';
    }

    /**
     * Get DALL-E 3 size string from aspect ratio
     *
     * @param string $aspectRatio e.g., "9:16", "1:1", "16:9"
     * @return string
     */
    public function getSizeFromAspectRatio(string $aspectRatio): string
    {
        return match ($aspectRatio) {
            '9:16' => '1024x1792',  // Portrait (WhatsApp Status, Instagram Story)
            '16:9' => '1792x1024',  // Landscape (Facebook, YouTube)
            '1:1' => '1024x1024',   // Square (Instagram Post)
            '4:5' => '1024x1024',   // Instagram Portrait (closest match)
            default => '1024x1024', // Default to square
        };
    }

    /**
     * Enhance prompt for DALL-E 3 with marketing-specific guidelines
     *
     * @param string $userPrompt User's original prompt
     * @param array $brandColors Optional brand colors
     * @return string
     */
    public function enhancePromptForMarketing(string $userPrompt, array $brandColors = []): string
    {
        $enhancements = [];

        // Add style guidelines
        $enhancements[] = "Create a professional marketing graphic.";
        $enhancements[] = "Modern, clean design with high contrast.";
        $enhancements[] = "Eye-catching and suitable for social media.";

        // Add brand colors if provided
        if (!empty($brandColors['primary_color'])) {
            $enhancements[] = "Use {$brandColors['primary_color']} as the primary color.";
        }
        if (!empty($brandColors['secondary_color'])) {
            $enhancements[] = "Incorporate {$brandColors['secondary_color']} as accent color.";
        }

        // Combine with user prompt
        $enhanced = implode(' ', $enhancements) . " " . $userPrompt;

        // Add quality markers
        $enhanced .= " Professional quality, vibrant colors, suitable for business marketing.";

        return $enhanced;
    }

    /**
     * Validate API key configuration
     *
     * @return bool
     */
    public function validateApiKey(): bool
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->get('https://api.openai.com/v1/models');

            return $response->successful();
        } catch (Exception $e) {
            Log::error('OpenAI API key validation failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get pricing estimate for image generation
     *
     * @param string $size DALL-E 3 size
     * @param string $quality 'standard' or 'hd'
     * @return float Cost in USD
     */
    public function getEstimatedCost(string $size, string $quality = 'hd'): float
    {
        // DALL-E 3 HD pricing (as of 2024)
        $hdPricing = [
            '1024x1024' => 0.040,
            '1024x1792' => 0.080,
            '1792x1024' => 0.080,
        ];

        // Standard quality is 50% of HD pricing
        $standardPricing = [
            '1024x1024' => 0.020,
            '1024x1792' => 0.040,
            '1792x1024' => 0.040,
        ];

        $pricing = $quality === 'hd' ? $hdPricing : $standardPricing;

        return $pricing[$size] ?? 0.040;
    }
}
