<?php

namespace App\Filament\Resources\Sources\Schemas;

use App\Enums\SourceType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->alphaDash()
                ->unique(ignoreRecord: true)
                ->helperText('Used in URLs and commands, e.g. "nk-news".'),
            Select::make('type')
                ->options(self::typeOptions())
                ->required()
                ->helperText('State media posts are always labelled as propaganda.'),
            TextInput::make('language')
                ->required()
                ->default('en')
                ->maxLength(5)
                ->helperText('Language of the articles, e.g. "en" or "ko".'),
            TextInput::make('website_url')
                ->label('Website')
                ->url()
                ->required()
                ->maxLength(255),
            TextInput::make('feed_url')
                ->label('RSS or Atom feed')
                ->url()
                ->maxLength(255)
                ->helperText('Leave empty for sources without a feed; their posts are added by hand.'),
            Toggle::make('is_active')
                ->label('Fetch this source every hour')
                ->default(true),
        ]);
    }

    /** @return array<string, string> */
    public static function typeOptions(): array
    {
        return [
            SourceType::Independent->value => 'Independent newsroom',
            SourceType::Analysis->value => 'Analysis / think tank',
            SourceType::StateMedia->value => 'North Korean state media (propaganda)',
        ];
    }
}
