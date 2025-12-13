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
        $html = preg_replace('/^### (.*?)$/m', '<h3 class="text-lg font-semibold mt-6 mb-3 text-gray-900 dark:text-gray-100">$1</h3>', $html);
        $html = preg_replace('/^## (.*?)$/m', '<h2 class="text-xl font-bold mt-8 mb-4 text-gray-900 dark:text-gray-100">$1</h2>', $html);
        $html = preg_replace('/^# (.*?)$/m', '<h1 class="text-2xl font-bold mt-10 mb-6 text-gray-900 dark:text-gray-100">$1</h1>', $html);

        // Code blocks with language and copy button
        $html = preg_replace_callback(
            '/```(\w+)?\n(.*?)\n```/s',
            function ($matches) {
                $language = $matches[1] ?? 'text';
                $code = htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8');
                $id = 'code-' . md5($code);

                return sprintf(
                    '<div class="relative my-4 group">
                        <div class="flex items-center justify-between bg-gray-800 dark:bg-gray-900 px-4 py-2 rounded-t-lg">
                            <span class="text-xs text-gray-400 uppercase font-semibold">%s</span>
                            <button
                                onclick="copyCode(\'%s\')"
                                class="text-xs text-gray-400 hover:text-white transition px-2 py-1 rounded hover:bg-gray-700"
                            >
                                Copy
                            </button>
                        </div>
                        <pre id="%s" class="bg-gray-900 dark:bg-black text-gray-100 p-4 rounded-b-lg overflow-x-auto language-%s"><code>%s</code></pre>
                    </div>',
                    $language,
                    $id,
                    $id,
                    $language,
                    $code
                );
            },
            $html
        );

        // Inline code
        $html = preg_replace('/`([^`]+)`/', '<code class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-sm font-mono text-primary-600 dark:text-primary-400">$1</code>', $html);

        // Bold
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong class="font-semibold">$1</strong>', $html);

        // Italic
        $html = preg_replace('/\*(.*?)\*/', '<em class="italic">$1</em>', $html);

        // Lists
        $html = preg_replace('/^\- (.+)$/m', '<li class="ml-4">$1</li>', $html);
        $html = preg_replace('/(<li.*<\/li>)+/s', '<ul class="list-disc list-inside space-y-1 my-3">$0</ul>', $html);

        // Links
        $html = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2" class="text-primary-600 dark:text-primary-400 hover:underline">$1</a>', $html);

        // Line breaks
        $html = nl2br($html);

        return $html;
    }
}
