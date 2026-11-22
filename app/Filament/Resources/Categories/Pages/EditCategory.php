<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\EditRecord;

/**
 * No delete action: posts point to their section, and the propaganda section drives the labels.
 */
class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;
}
