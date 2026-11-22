<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('slug')
                ->required()
                ->alphaDash()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->helperText('Add its name in every language under lang/{locale}/categories.php.'),
            TextInput::make('sort_order')
                ->numeric()
                ->required()
                ->default(0),
        ]);
    }
}
