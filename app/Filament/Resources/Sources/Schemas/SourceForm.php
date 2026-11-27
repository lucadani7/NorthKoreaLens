<?php

namespace App\Filament\Resources\Sources\Schemas;

use App\Enums\SourceType;
use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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
            FileUpload::make('logo_path')
                ->label('Logo')
                ->image()
                ->disk('public')
                ->directory('source-logos')
                ->visibility('public')
                ->maxSize(2048)
                ->helperText('Shown next to the publication name on cards, article pages and the Sources page.'),
            TextInput::make('feed_url')
                ->label('RSS or Atom feed')
                ->url()
                ->maxLength(255)
                ->helperText('Leave empty for sources without a feed; their posts are added by hand.'),
            Select::make('feed_format')
                ->label('Feed format')
                ->options([
                    'feed' => 'RSS or Atom feed (YouTube channels too)',
                    'rodong' => 'Rodong Sinmun home page',
                ])
                ->default('feed')
                ->required(),
            TextInput::make('fetch_every_hours')
                ->label('Fetch every (hours)')
                ->numeric()
                ->minValue(1)
                ->maxValue(168)
                ->default(1)
                ->required(),
            Select::make('category_id')
                ->label('Section')
                ->options(fn () => Category::orderBy('sort_order')->get()->mapWithKeys(
                    fn (Category $category) => [$category->id => $category->name()],
                ))
                ->placeholder('Default for the type (news, analysis or propaganda)'),
            TagsInput::make('keywords')
                ->label('Only import items that mention')
                ->placeholder('e.g. North Korea')
                ->helperText('Matched in titles and descriptions, ignoring case. Used by feeds and youtube:backfill; leave empty to import everything.'),
            Toggle::make('needs_review')
                ->label('Import as drafts, to be published by hand')
                ->default(false),
            Toggle::make('forbids_ai')
                ->label('The source forbids AI tools on its content')
                ->helperText('Readers see a notice on its posts, and NK Lens runs no AI features on them.')
                ->default(false),
            Toggle::make('skip_shorts')
                ->label('YouTube: regular videos only, no Shorts')
                ->default(false),
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
            SourceType::Testimony->value => 'Escapee testimony (e.g. a YouTube channel)',
            SourceType::TravelVlog->value => 'Travel vlog (a channel that has visited North Korea)',
            SourceType::StateMedia->value => 'North Korean state media (propaganda)',
        ];
    }
}
