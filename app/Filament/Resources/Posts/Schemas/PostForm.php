<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Models\Category;
use App\Models\Topic;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Content')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('excerpt')
                        ->rows(3)
                        ->helperText('For third-party articles keep this to a short excerpt, never the full text.'),
                    Textarea::make('body')
                        ->rows(12)
                        ->helperText('Full text, only for content written by the NK Lens team.'),
                    Textarea::make('editorial_note')
                        ->label('What the article doesn\'t say')
                        ->rows(4)
                        ->helperText('Editorial context with independent sources. Shown in a box under the post, above all on propaganda.'),
                ]),
            Section::make('Translations')
                ->description('The same post in other languages. Empty fields fall back to the text above.')
                ->columnSpanFull()
                ->collapsible()
                ->schema([
                    Repeater::make('translations')
                        ->hiddenLabel()
                        ->relationship()
                        ->itemLabel(fn (array $state): ?string => config('locales.supported.'.($state['locale'] ?? ''), $state['locale'] ?? null))
                        ->collapsed()
                        ->addActionLabel('Add a language')
                        ->schema([
                            Select::make('locale')
                                ->label('Language')
                                ->options(config('locales.supported'))
                                ->required()
                                ->distinct(),
                            TextInput::make('title')
                                ->required()
                                ->maxLength(255),
                            Textarea::make('excerpt')->rows(3),
                            Textarea::make('body')->rows(12),
                            Textarea::make('editorial_note')
                                ->label('What the article doesn\'t say')
                                ->rows(4),
                        ]),
                ]),
            Section::make('Classification')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('category_id')
                        ->label('Section')
                        ->relationship('category', 'sort_order', fn ($query) => $query->orderBy('sort_order'))
                        ->getOptionLabelFromRecordUsing(fn (Category $record) => $record->name())
                        ->required(),
                    Select::make('topics')
                        ->label('Topics')
                        ->relationship('topics', 'sort_order', fn ($query) => $query->orderBy('sort_order'))
                        ->getOptionLabelFromRecordUsing(fn (Topic $record) => $record->name())
                        ->multiple()
                        ->preload()
                        ->helperText('Shown as filters inside the section, e.g. Curiosities → History.'),
                    Select::make('source_id')
                        ->label('Source')
                        ->relationship('source', 'name')
                        ->searchable()
                        ->preload()
                        ->helperText('Empty for posts written by the NK Lens team.'),
                    TextInput::make('language')
                        ->required()
                        ->default('en')
                        ->maxLength(5),
                    DateTimePicker::make('published_at')
                        ->label('Published')
                        ->helperText('Empty keeps the post as a draft. A future date schedules it.'),
                ]),
            Section::make('Links')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('original_url')
                        ->label('Original article')
                        ->url()
                        ->maxLength(2048),
                    // Not ->url(): team posts use our own copies, e.g. /images/curiosities/juche-tower.jpg.
                    TextInput::make('image_url')
                        ->label('Image')
                        ->helperText('A full URL, or a path under public/ such as /images/curiosities/….jpg.')
                        ->maxLength(2048),
                    TextInput::make('image_credit')
                        ->label('Image credit')
                        ->helperText('Author and licence, e.g. "Photo: …, CC BY-SA 4.0, via Wikimedia Commons".')
                        ->maxLength(500),
                    Textarea::make('epigraph')
                        ->label('Epigraph')
                        ->helperText('Optional verse or quote, shown centred in bold italics above the text. One line per row; the same in every language.')
                        ->rows(5)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
