<?php

namespace App\Services\AI;

use App\Models\BrandKit;
use App\Models\Invoice;
use App\Models\MarketingTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeIntegrationService
{
    protected string $apiKey;
    protected string $model;
    protected int $maxTokens;
    protected float $temperature;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = config('marketing.claude.api_key');
        $this->model = config('marketing.claude.model');
        $this->maxTokens = config('marketing.claude.max_tokens');
        $this->temperature = config('marketing.claude.temperature');
        $this->timeout = config('marketing.claude.timeout');
    }

    /**
     * Generate marketing design JSON from user prompt
     *
     * @param string $prompt User's design request
     * @param MarketingTemplate $template Template to use
     * @param BrandKit|null $brandKit User's brand settings
     * @param Invoice|null $invoice Optional invoice for context
     * @return array Design JSON structure
     * @throws \Exception
     */
    public function generateDesign(
        string $prompt,
        MarketingTemplate $template,
        ?BrandKit $brandKit = null,
        ?Invoice $invoice = null
    ): array {
        $systemPrompt = $this->buildSystemPrompt($template, $brandKit);
        $userPrompt = $this->buildUserPrompt($prompt, $template, $brandKit, $invoice);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
                'system' => $systemPrompt,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $userPrompt,
                    ],
                ],
            ]);

            if (!$response->successful()) {
                Log::error('Claude API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Claude API request failed: ' . $response->body());
            }

            $data = $response->json();
            $content = $data['content'][0]['text'] ?? null;

            if (!$content) {
                throw new \Exception('No content in Claude response');
            }

            // Extract JSON from response (Claude might wrap it in markdown)
            $designJson = $this->extractJson($content);

            // Validate design structure
            $this->validateDesignJson($designJson, $template);

            return $designJson;

        } catch (\Exception $e) {
            Log::error('Claude integration error', [
                'error' => $e->getMessage(),
                'prompt' => $prompt,
                'template_id' => $template->id,
            ]);
            throw $e;
        }
    }

    /**
     * Build system prompt for Claude
     */
    protected function buildSystemPrompt(MarketingTemplate $template, ?BrandKit $brandKit): string
    {
        $baseContext = config('marketing.prompts.system_context');

        $templateContext = "
Template Constraints:
- Name: {$template->name}
- Category: {$template->category}
- Dimensions: {$template->width}x{$template->height}px
- Aspect Ratio: {$template->aspect_ratio}
- Layout Schema: " . json_encode($template->layout_schema) . "
- Default Styles: " . json_encode($template->default_styles) . "

You MUST:
1. Respect the template's layout_schema (grid structure, safe margins, text zones)
2. Output ONLY valid JSON matching the design schema below
3. Ensure text is readable on mobile (min 14px for body, 20px for headings)
4. Use brand colors if provided, otherwise generate harmonious palette
5. Keep total element count under 15 for performance
6. All text must fit within designated text zones
7. Use Nigerian currency format (₦) for amounts

Design JSON Schema:
{
  \"layout\": {
    \"width\": number,
    \"height\": number,
    \"background\": {
      \"type\": \"solid\" | \"gradient\",
      \"color\": \"#RRGGBB\" | [\"#RRGGBB\", \"#RRGGBB\"],
      \"direction\": \"to bottom\" (if gradient)
    }
  },
  \"elements\": [
    {
      \"type\": \"text\" | \"image\" | \"shape\" | \"icon\",
      \"content\": string (for text/icon),
      \"position\": { \"x\": number, \"y\": number },
      \"size\": { \"width\": number, \"height\": number },
      \"styles\": {
        \"fontSize\": number (px),
        \"fontFamily\": string,
        \"fontWeight\": number,
        \"color\": \"#RRGGBB\",
        \"textAlign\": \"left\" | \"center\" | \"right\",
        \"backgroundColor\": \"#RRGGBB\" (optional),
        \"borderRadius\": number (optional),
        \"opacity\": number (0-1, optional)
      }
    }
  ],
  \"metadata\": {
    \"title\": string,
    \"description\": string,
    \"tags\": [string]
  }
}
";

        $brandContext = '';
        if ($brandKit) {
            $brandContext = "
Brand Kit:
- Primary Color: {$brandKit->primary_color}
- Secondary Color: {$brandKit->secondary_color}
- Accent Color: {$brandKit->accent_color}
- Heading Font: {$brandKit->font_heading}
- Body Font: {$brandKit->font_body}
- Logo URL: {$brandKit->logo_url}

IMPORTANT: You MUST use these brand colors and fonts in your design.
";
        }

        return $baseContext . "\n\n" . $templateContext . "\n\n" . $brandContext;
    }

    /**
     * Build user prompt
     */
    protected function buildUserPrompt(
        string $prompt,
        MarketingTemplate $template,
        ?BrandKit $brandKit,
        ?Invoice $invoice
    ): string {
        $userMessage = "Create a {$template->category} design with the following requirements:\n\n";
        $userMessage .= "User Request: {$prompt}\n\n";

        if ($invoice) {
            $userMessage .= "Invoice Context:\n";
            $userMessage .= "- Invoice Number: {$invoice->invoice_number}\n";
            $userMessage .= "- Amount: ₦" . number_format($invoice->total_amount, 2) . "\n";
            $userMessage .= "- Customer: {$invoice->customer->name}\n";
            $userMessage .= "- Status: {$invoice->status}\n";
            $userMessage .= "- Issue Date: {$invoice->issue_date->format('M d, Y')}\n\n";
        }

        $userMessage .= "Remember to:\n";
        $userMessage .= "1. Use the template's layout schema\n";
        $userMessage .= "2. Apply brand kit colors and fonts if provided\n";
        $userMessage .= "3. Make text mobile-readable (min 14px body, 20px headings)\n";
        $userMessage .= "4. Output ONLY the JSON, no markdown formatting\n";
        $userMessage .= "5. Ensure design fits {$template->width}x{$template->height}px canvas\n";

        return $userMessage;
    }

    /**
     * Extract JSON from Claude's response (handles markdown wrapping)
     */
    protected function extractJson(string $content): array
    {
        // Remove markdown code fences if present
        $content = preg_replace('/^```json\s*/m', '', $content);
        $content = preg_replace('/^```\s*/m', '', $content);
        $content = trim($content);

        $json = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON in Claude response: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Validate design JSON structure
     */
    protected function validateDesignJson(array $json, MarketingTemplate $template): void
    {
        // Check required top-level keys
        $requiredKeys = ['layout', 'elements', 'metadata'];
        foreach ($requiredKeys as $key) {
            if (!isset($json[$key])) {
                throw new \Exception("Missing required key: {$key}");
            }
        }

        // Validate layout
        if (!isset($json['layout']['width'], $json['layout']['height'], $json['layout']['background'])) {
            throw new \Exception('Invalid layout structure');
        }

        // Validate dimensions match template
        if ($json['layout']['width'] != $template->width || $json['layout']['height'] != $template->height) {
            throw new \Exception('Design dimensions do not match template');
        }

        // Validate elements array
        if (!is_array($json['elements']) || empty($json['elements'])) {
            throw new \Exception('Elements must be a non-empty array');
        }

        // Validate each element has required fields
        foreach ($json['elements'] as $index => $element) {
            if (!isset($element['type'], $element['position'], $element['size'])) {
                throw new \Exception("Element {$index} missing required fields");
            }
        }

        // Validate metadata
        if (!isset($json['metadata']['title'])) {
            throw new \Exception('Metadata must include title');
        }
    }

    /**
     * Check if Claude API is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Test Claude API connection
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->timeout(10)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => 10,
                'messages' => [
                    ['role' => 'user', 'content' => 'Test'],
                ],
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Claude API test connection failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Generate DALL-E 3 optimized image prompt from user request
     *
     * @param string $userPrompt User's design request
     * @param MarketingTemplate $template Template for context
     * @param BrandKit|null $brandKit User's brand colors
     * @param Invoice|null $invoice Optional invoice data
     * @return array ['image_prompt' => string, 'context' => array]
     * @throws \Exception
     */
    public function generateImagePrompt(
        string $userPrompt,
        MarketingTemplate $template,
        ?BrandKit $brandKit = null,
        ?Invoice $invoice = null
    ): array {
        $systemPrompt = $this->buildDallE3SystemPrompt();
        $enhancedUserPrompt = $this->buildDallE3UserPrompt($userPrompt, $template, $brandKit, $invoice);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => 1000,
                'temperature' => 0.7,
                'system' => $systemPrompt,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $enhancedUserPrompt,
                    ],
                ],
            ]);

            if (!$response->successful()) {
                throw new \Exception('Claude API request failed: ' . $response->body());
            }

            $data = $response->json();
            $imagePrompt = $data['content'][0]['text'] ?? null;

            if (!$imagePrompt) {
                throw new \Exception('No image prompt in Claude response');
            }

            // Clean up the prompt (remove any markdown formatting)
            $imagePrompt = trim(strip_tags($imagePrompt));

            Log::info('DALL-E 3 prompt generated', [
                'prompt_length' => strlen($imagePrompt),
                'template' => $template->name,
            ]);

            return [
                'image_prompt' => $imagePrompt,
                'context' => [
                    'template_name' => $template->name,
                    'aspect_ratio' => $template->aspect_ratio,
                    'has_brand_kit' => $brandKit !== null,
                    'has_invoice' => $invoice !== null,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('DALL-E 3 prompt generation error', [
                'error' => $e->getMessage(),
                'user_prompt' => $userPrompt,
            ]);
            throw $e;
        }
    }

    /**
     * Build system prompt for DALL-E 3 image generation
     */
    protected function buildDallE3SystemPrompt(): string
    {
        return <<<PROMPT
You are an expert marketing designer specializing in creating image generation prompts for DALL-E 3.

Your role is to transform user requests into detailed, professional image generation prompts that will produce:
- Ultra-realistic, photorealistic marketing graphics
- Eye-catching designs suitable for social media (WhatsApp Status, Instagram, Facebook)
- Professional business marketing materials
- Clear, readable text integration when needed

Guidelines:
1. Be specific about visual style, colors, composition, lighting
2. Specify text placement and readability requirements
3. Include brand colors when provided
4. Ensure designs are modern, clean, and professional
5. Optimize for the target aspect ratio (9:16 for WhatsApp Status, 1:1 for Instagram, etc.)
6. Include keywords: "professional marketing graphic", "high quality", "vibrant colors"
7. Avoid any prohibited content or copyrighted elements

Output only the optimized DALL-E 3 prompt - no explanations, no markdown, just the prompt text.
PROMPT;
    }

    /**
     * Build user prompt for DALL-E 3 optimization
     */
    protected function buildDallE3UserPrompt(
        string $userPrompt,
        MarketingTemplate $template,
        ?BrandKit $brandKit = null,
        ?Invoice $invoice = null
    ): string {
        $context = [];

        // Template context
        $context[] = "Template: {$template->name}";
        $context[] = "Aspect Ratio: {$template->aspect_ratio}";
        $context[] = "Purpose: " . ucwords(str_replace('-', ' ', $template->category));

        // Brand colors
        if ($brandKit) {
            if ($brandKit->primary_color) {
                $context[] = "Primary Brand Color: {$brandKit->primary_color}";
            }
            if ($brandKit->secondary_color) {
                $context[] = "Secondary Brand Color: {$brandKit->secondary_color}";
            }
            if ($brandKit->accent_color) {
                $context[] = "Accent Color: {$brandKit->accent_color}";
            }
        }

        // Invoice context for invoice-share templates
        if ($invoice) {
            $context[] = "Invoice Number: {$invoice->invoice_number}";
            $context[] = "Amount: ₦" . number_format($invoice->total_amount, 2);
            $context[] = "Status: " . ucfirst($invoice->status);
            if ($invoice->customer) {
                $context[] = "Customer: {$invoice->customer->name}";
            }
        }

        $contextString = implode("\n", $context);

        return <<<PROMPT
Create a DALL-E 3 image generation prompt based on this request:

User Request: "{$userPrompt}"

Context:
{$contextString}

Requirements:
- Ultra-realistic, professional marketing graphic
- Modern design with vibrant colors and gradients
- Suitable for Nigerian SME businesses
- Text must be clear and readable
- Mobile-optimized for social media sharing
- Professional quality suitable for business use

Generate the optimized DALL-E 3 prompt now:
PROMPT;
    }
}
