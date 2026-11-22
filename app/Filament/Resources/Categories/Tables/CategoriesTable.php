<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->state(fn (Category $record) => $record->name()),
                TextColumn::make('slug')
                    ->badge()
                    ->color(fn (Category $record) => $record->isPropaganda() ? 'danger' : 'primary'),
                TextColumn::make('posts_count')
                    ->counts('posts')
                    ->label('Posts'),
            ])
            // Drag rows to change the order of the sections in the site menu.
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->paginated(false)
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
