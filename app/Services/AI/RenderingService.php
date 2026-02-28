<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RenderingService
{
    protected string $engine;
    protected int $timeout;
    protected int $quality;
    protected string $storageDisk;
    protected string $storagePath;

    public function __construct()
    {
        $this->engine = config('marketing.rendering.engine');
        $this->timeout = config('marketing.rendering.timeout');
        $this->quality = config('marketing.rendering.quality');
        $this->storageDisk = config('marketing.storage.disk');
        $this->storagePath = config('marketing.storage.path');
    }

    /**
     * Render design JSON to PNG file
     *
     * @param array $designJson Claude-generated design structure
     * @param int $width Canvas width
     * @param int $height Canvas height
     * @param string|null $imagePrompt Optional direct image generation prompt for DALL-E 3
     * @param array $brandColors Optional brand colors for DALL-E 3
     * @return array ['path' => string, 'url' => string, 'size' => int]
     * @throws \Exception
     */
    public function renderToPng(
        array $designJson,
        int $width,
        int $height,
        ?string $imagePrompt = null,
        array $brandColors = []
    ): array
    {
        // Route to appropriate rendering method based on engine
        return match ($this->engine) {
            'dalle3' => $this->renderWithDallE3($imagePrompt ?? $designJson['image_prompt'] ?? '', $width, $height, $brandColors),
            'playwright' => $this->renderWithPlaywrightEngine($designJson, $width, $height),
            'wkhtmltoimage' => $this->renderWithWkhtmltoimageEngine($designJson, $width, $height),
            default => throw new \Exception("Unsupported render engine: {$this->engine}"),
        };
    }

    /**
     * Render using Playwright (HTML-based)
     */
    protected function renderWithPlaywrightEngine(array $designJson, int $width, int $height): array
    {
        $html = $this->generateHtml($designJson, $width, $height);
        $pngData = $this->renderWithPlaywright($html, $width, $height);
        $storagePath = $this->storePng($pngData);

        return [
            'path' => $storagePath,
            'url' => Storage::disk($this->storageDisk)->url($storagePath),
            'size' => strlen($pngData),
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * Render using wkhtmltoimage (HTML-based)
     */
    protected function renderWithWkhtmltoimageEngine(array $designJson, int $width, int $height): array
    {
        $html = $this->generateHtml($designJson, $width, $height);
        $pngData = $this->renderWithWkhtmltoimage($html, $width, $height);
        $storagePath = $this->storePng($pngData);

        return [
            'path' => $storagePath,
            'url' => Storage::disk($this->storageDisk)->url($storagePath),
            'size' => strlen($pngData),
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * Render using DALL-E 3 (AI image generation)
     */
    protected function renderWithDallE3(string $prompt, int $width, int $height, array $brandColors = []): array
    {
        $imageGenService = app(ImageGenerationService::class);

        Log::info('Rendering with DALL-E 3', [
            'prompt_length' => strlen($prompt),
            'dimensions' => "{$width}x{$height}",
        ]);

        // Generate image with DALL-E 3
        $result = $imageGenService->generateWithDallE3(
            prompt: $prompt,
            width: $width,
            height: $height,
            quality: config('marketing.rendering.dalle3_quality', 'hd')
        );

        // Download and save image
        $filename = Str::uuid() . '.png';
        $saved = $imageGenService->downloadAndSave(
            imageUrl: $result['url'],
            filename: $filename,
            disk: $this->storageDisk
        );

        Log::info('DALL-E 3 rendering completed', [
            'path' => $saved['path'],
            'size' => $saved['size'],
        ]);

        return [
            'path' => $saved['path'],
            'url' => $saved['url'],
            'size' => $saved['size'],
            'width' => $width,
            'height' => $height,
            'revised_prompt' => $result['revised_prompt'],
        ];
    }

    /**
     * Generate HTML from design JSON
     */
    protected function generateHtml(array $designJson, int $width, int $height): string
    {
        $layout = $designJson['layout'];
        $elements = $designJson['elements'];

        // Generate background CSS
        $backgroundCss = $this->generateBackgroundCss($layout['background']);

        // Generate elements HTML
        $elementsHtml = '';
        foreach ($elements as $element) {
            $elementsHtml .= $this->generateElementHtml($element);
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .canvas {
            position: relative;
            width: {$width}px;
            height: {$height}px;
            overflow: hidden;
            {$backgroundCss}
        }

        .element {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .element-text {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .element-shape {
            border-radius: inherit;
        }

        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
    </style>
</head>
<body>
    <div class="canvas">
        {$elementsHtml}
    </div>
</body>
</html>
HTML;
    }

    /**
     * Generate background CSS
     */
    protected function generateBackgroundCss(array $background): string
    {
        if ($background['type'] === 'gradient') {
            $colors = $background['color'];
            $direction = $background['direction'] ?? 'to bottom';
            return "background: linear-gradient({$direction}, {$colors[0]}, {$colors[1]});";
        }

        return "background-color: {$background['color']};";
    }

    /**
     * Generate HTML for a single element
     */
    protected function generateElementHtml(array $element): string
    {
        $type = $element['type'];
        $position = $element['position'];
        $size = $element['size'];
        $styles = $element['styles'] ?? [];

        // Base positioning
        $css = "left: {$position['x']}px; top: {$position['y']}px;";
        $css .= "width: {$size['width']}px; height: {$size['height']}px;";

        // Apply additional styles
        foreach ($styles as $property => $value) {
            $cssProperty = $this->convertToCssProperty($property);
            $cssValue = $this->convertToCssValue($property, $value);
            $css .= "{$cssProperty}: {$cssValue};";
        }

        $content = match ($type) {
            'text' => htmlspecialchars($element['content'] ?? '', ENT_QUOTES, 'UTF-8'),
            'icon' => $this->renderIcon($element['content'] ?? ''),
            'shape' => '',
            'image' => $this->renderImage($element['content'] ?? ''),
            default => '',
        };

        $class = "element element-{$type}";

        return "<div class=\"{$class}\" style=\"{$css}\">{$content}</div>\n";
    }

    /**
     * Convert camelCase to kebab-case for CSS properties
     */
    protected function convertToCssProperty(string $property): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $property));
    }

    /**
     * Convert value to CSS-compatible format
     */
    protected function convertToCssValue(string $property, mixed $value): string
    {
        // Handle numeric values that need units
        if (in_array($property, ['fontSize', 'borderRadius']) && is_numeric($value)) {
            return $value . 'px';
        }

        if ($property === 'fontWeight' && is_numeric($value)) {
            return (string) $value;
        }

        return (string) $value;
    }

    /**
     * Render icon (placeholder for now)
     */
    protected function renderIcon(string $iconName): string
    {
        // In production, map to actual icon SVGs or font icons
        return "<span>📄</span>";
    }

    /**
     * Render image
     */
    protected function renderImage(string $imageUrl): string
    {
        if (empty($imageUrl)) {
            return '';
        }

        return "<img src=\"{$imageUrl}\" style=\"width: 100%; height: 100%; object-fit: cover;\" />";
    }

    /**
     * Render HTML to PNG using Playwright
     */
    protected function renderWithPlaywright(string $html, int $width, int $height): string
    {
        // Create temporary HTML file
        $tempHtmlPath = sys_get_temp_dir() . '/' . Str::uuid() . '.html';
        $tempPngPath = sys_get_temp_dir() . '/' . Str::uuid() . '.png';

        try {
            file_put_contents($tempHtmlPath, $html);

            // Get project base path
            $basePath = base_path();

            // Build Playwright command with proper module resolution
            $nodeScript = <<<JS
const path = require('path');
const { chromium } = require(path.join('{$basePath}', 'node_modules', 'playwright'));

(async () => {
    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const page = await browser.newPage({
        viewport: { width: {$width}, height: {$height} },
        deviceScaleFactor: 2
    });

    await page.goto('file://{$tempHtmlPath}', {
        waitUntil: 'networkidle'
    });

    await page.screenshot({
        path: '{$tempPngPath}',
        type: 'png',
        fullPage: false
    });

    await browser.close();
})();
JS;

            $tempScriptPath = sys_get_temp_dir() . '/' . Str::uuid() . '.js';
            file_put_contents($tempScriptPath, $nodeScript);

            // Execute Playwright
            $command = "node {$tempScriptPath}";
            $output = [];
            $returnCode = 0;

            exec($command . ' 2>&1', $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($tempPngPath)) {
                Log::error('Playwright rendering failed', [
                    'output' => implode("\n", $output),
                    'return_code' => $returnCode,
                ]);
                throw new \Exception('Playwright rendering failed');
            }

            $pngData = file_get_contents($tempPngPath);

            return $pngData;

        } finally {
            // Cleanup temp files
            @unlink($tempHtmlPath);
            @unlink($tempPngPath);
            @unlink($tempScriptPath ?? '');
        }
    }

    /**
     * Render HTML to PNG using wkhtmltoimage (fallback)
     */
    protected function renderWithWkhtmltoimage(string $html, int $width, int $height): string
    {
        $tempHtmlPath = sys_get_temp_dir() . '/' . Str::uuid() . '.html';
        $tempPngPath = sys_get_temp_dir() . '/' . Str::uuid() . '.png';

        try {
            file_put_contents($tempHtmlPath, $html);

            // Build wkhtmltoimage command
            $command = "wkhtmltoimage --width {$width} --height {$height} --quality {$this->quality} {$tempHtmlPath} {$tempPngPath}";
            $output = [];
            $returnCode = 0;

            exec($command . ' 2>&1', $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($tempPngPath)) {
                Log::error('wkhtmltoimage rendering failed', [
                    'output' => implode("\n", $output),
                    'return_code' => $returnCode,
                ]);
                throw new \Exception('wkhtmltoimage rendering failed');
            }

            $pngData = file_get_contents($tempPngPath);

            return $pngData;

        } finally {
            @unlink($tempHtmlPath);
            @unlink($tempPngPath);
        }
    }

    /**
     * Store PNG file
     */
    protected function storePng(string $pngData): string
    {
        $filename = Str::uuid() . '.png';
        $path = $this->storagePath . '/' . $filename;

        Storage::disk($this->storageDisk)->put($path, $pngData);

        return $path;
    }

    /**
     * Check if rendering engine is available
     */
    public function isEngineAvailable(): bool
    {
        return match ($this->engine) {
            'playwright' => $this->checkPlaywright(),
            'wkhtmltoimage' => $this->checkWkhtmltoimage(),
            default => false,
        };
    }

    /**
     * Check if Playwright is installed
     */
    protected function checkPlaywright(): bool
    {
        exec('node -v 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Check if wkhtmltoimage is installed
     */
    protected function checkWkhtmltoimage(): bool
    {
        exec('wkhtmltoimage --version 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }
}
