<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Models\Category;
use Filament\Forms\Components\DateTimePicker;
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
            Section::make('Classification')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('category_id')
                        ->label('Section')
                        ->relationship('category', 'sort_order', fn ($query) => $query->orderBy('sort_order'))
                        ->getOptionLabelFromRecordUsing(fn (Category $record) => $record->name())
                        ->required(),
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
                    TextInput::make('image_url')
                        ->label('Image')
                        ->url()
                        ->maxLength(2048),
                ]),
        ]);
    }
}
