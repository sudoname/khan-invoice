<?php

namespace App\Filament\Widgets;

use App\Models\MarketingDesign;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Support\Enums\FontWeight;

class RecentMarketingDesigns extends BaseWidget
{
    protected static ?string $heading = 'Recent Marketing Designs';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        $query = MarketingDesign::query()
            ->with(['template', 'brandKit'])
            ->where('user_id', auth()->id());

        return $table
            ->query($query->latest()->limit(10))
            ->columns([
                Tables\Columns\ImageColumn::make('rendered_url')
                    ->label('Preview')
                    ->circular()
                    ->size(50)
                    ->defaultImageUrl(url('/images/placeholder-design.png')),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->limit(30),

                Tables\Columns\TextColumn::make('template.name')
                    ->label('Template')
                    ->badge()
                    ->color('info'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'rendering',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('download_count')
                    ->label('Downloads')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->since()
                    ->description(fn (MarketingDesign $record): string => $record->created_at->format('M d, Y')),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (MarketingDesign $record): string =>
                        route('filament.app.resources.marketing-designs.view', ['record' => $record])
                    ),

                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (MarketingDesign $record) => $record->isCompleted())
                    ->url(fn (MarketingDesign $record) => $record->rendered_url)
                    ->openUrlInNewTab()
                    ->after(fn (MarketingDesign $record) => $record->incrementDownloadCount()),
            ])
            ->emptyStateHeading('No designs yet')
            ->emptyStateDescription('Create your first marketing design to get started')
            ->emptyStateIcon('heroicon-o-sparkles');
    }

    /**
     * Check if the widget should be visible
     */
    public static function canView(): bool
    {
        // Show widget if user has created at least one design
        return MarketingDesign::where('user_id', auth()->id())->exists();
    }
}
