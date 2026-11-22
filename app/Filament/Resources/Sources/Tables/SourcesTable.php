<?php

namespace App\Filament\Resources\Sources\Tables;

use App\Enums\SourceType;
use App\Filament\Resources\Sources\Schemas\SourceForm;
use App\Jobs\FetchSourceFeed;
use App\Models\Source;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Throwable;

class SourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (SourceType $state) => SourceForm::typeOptions()[$state->value])
                    ->color(fn (SourceType $state) => $state->isPropaganda() ? 'danger' : 'primary'),
                TextColumn::make('posts_count')
                    ->counts('posts')
                    ->label('Posts')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('last_fetched_at')
                    ->label('Last fetched')
                    ->since()
                    ->sortable()
                    ->placeholder('Never'),
                TextColumn::make('feed_url')
                    ->label('Feed')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('type')
                    ->options(SourceForm::typeOptions()),
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                Action::make('fetch')
                    ->label('Fetch now')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (Source $record) => $record->is_active && $record->feed_url !== null)
                    ->action(function (Source $record) {
                        $before = $record->posts()->count();

                        try {
                            FetchSourceFeed::dispatchSync($record);
                        } catch (Throwable $e) {
                            report($e);

                            Notification::make()
                                ->title("Could not fetch {$record->name}")
                                ->body(str($e->getMessage())->before("\n")->limit(150)->toString())
                                ->danger()
                                ->send();

                            return;
                        }

                        $new = $record->posts()->count() - $before;

                        Notification::make()
                            ->title("Fetched {$record->name}")
                            ->body("{$new} new posts.")
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ]);
    }
}
