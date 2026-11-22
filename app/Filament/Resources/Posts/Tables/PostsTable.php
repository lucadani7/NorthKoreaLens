<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Models\Category;
use App\Models\Post;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['source', 'category']))
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->limit(70)
                    ->wrap(),
                TextColumn::make('source.name')
                    ->label('Source')
                    ->placeholder('NK Lens team')
                    ->sortable(),
                TextColumn::make('category.slug')
                    ->label('Section')
                    ->badge()
                    ->formatStateUsing(fn (Post $record) => $record->category->name())
                    ->color(fn (Post $record) => $record->category->isPropaganda() ? 'danger' : 'primary'),
                IconColumn::make('propaganda')
                    ->state(fn (Post $record) => $record->isPropaganda())
                    ->boolean()
                    ->trueColor('danger')
                    ->falseIcon(null),
                TextColumn::make('published_at')
                    ->label('Published')
                    ->since()
                    ->sortable()
                    ->placeholder('Draft'),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                SelectFilter::make('category')
                    ->label('Section')
                    ->relationship('category', 'sort_order')
                    ->getOptionLabelFromRecordUsing(fn (Category $record) => $record->name()),
                SelectFilter::make('source')
                    ->relationship('source', 'name'),
                TernaryFilter::make('propaganda')
                    ->queries(
                        true: fn (Builder $query) => $query->whereIn('id', Post::query()->propaganda()->select('id')),
                        false: fn (Builder $query) => $query->whereNotIn('id', Post::query()->propaganda()->select('id')),
                        blank: fn (Builder $query) => $query,
                    ),
                TernaryFilter::make('published')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('published_at'),
                        false: fn (Builder $query) => $query->whereNull('published_at'),
                        blank: fn (Builder $query) => $query,
                    ),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
