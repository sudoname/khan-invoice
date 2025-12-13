<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;

class ApiDocumentation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.app.pages.api-documentation';
    protected static ?string $navigationLabel = 'API Documentation';
    protected static ?string $title = 'API Documentation';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 96;

    /**
     * Get the API documentation content parsed into sections
     */
    public function getApiDocumentation(): array
    {
        $docPath = base_path('API-DOCUMENTATION.md');

        if (!file_exists($docPath)) {
            return [];
        }

        $content = file_get_contents($docPath);

        // Split content by main headers (##)
        $sections = [];
        $lines = explode("\n", $content);
        $currentSection = null;
        $currentContent = [];

        foreach ($lines as $line) {
            // Main section header (##)
            if (preg_match('/^## (.+)$/', $line, $matches)) {
                // Save previous section
                if ($currentSection !== null) {
                    $sections[] = [
                        'title' => $currentSection,
                        'content' => implode("\n", $currentContent),
                        'icon' => $this->getSectionIcon($currentSection),
                    ];
                }
                // Start new section
                $currentSection = trim($matches[1]);
                $currentContent = [];
            } else {
                $currentContent[] = $line;
            }
        }

        // Save last section
        if ($currentSection !== null) {
            $sections[] = [
                'title' => $currentSection,
                'content' => implode("\n", $currentContent),
                'icon' => $this->getSectionIcon($currentSection),
            ];
        }

        return $sections;
    }

    /**
     * Get icon for section based on title
     */
    private function getSectionIcon(string $title): string
    {
        $iconMap = [
            'Authentication' => 'heroicon-o-key',
            'Base URL' => 'heroicon-o-globe-alt',
            'Invoices API' => 'heroicon-o-document-text',
            'Customers API' => 'heroicon-o-users',
            'Payments API' => 'heroicon-o-banknotes',
            'Reports API' => 'heroicon-o-chart-bar',
            'Rate Limiting' => 'heroicon-o-shield-check',
            'Error Handling' => 'heroicon-o-exclamation-circle',
            'Best Practices' => 'heroicon-o-light-bulb',
        ];

        foreach ($iconMap as $keyword => $icon) {
            if (stripos($title, $keyword) !== false) {
                return $icon;
            }
        }

        return 'heroicon-o-document';
    }

    /**
     * Parse markdown to HTML
     */
    public function parseMarkdown(string $markdown): string
    {
        // Basic markdown parsing
        $html = $markdown;

        // Headers
        $html = preg_replace('/^### (.*?)$/m', '<h3 class="text-lg font-semibold mt-6 mb-3">$1</h3>', $html);
        $html = preg_replace('/^## (.*?)$/m', '<h2 class="text-xl font-bold mt-8 mb-4">$1</h2>', $html);
        $html = preg_replace('/^# (.*?)$/m', '<h1 class="text-2xl font-bold mt-10 mb-6">$1</h1>', $html);

        // Code blocks
        $html = preg_replace('/```(\w+)?\n(.*?)\n```/s', '<pre class="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg overflow-x-auto my-4"><code>$2</code></pre>', $html);

        // Inline code
        $html = preg_replace('/`([^`]+)`/', '<code class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-sm">$1</code>', $html);

        // Bold
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);

        // Italic
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);

        // Line breaks
        $html = nl2br($html);

        return $html;
    }
}
