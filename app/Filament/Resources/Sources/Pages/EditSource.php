<?php

namespace App\Filament\Resources\Sources\Pages;

use App\Filament\Resources\Sources\SourceResource;
use Filament\Resources\Pages\EditRecord;

/**
 * No delete action on purpose: deleting a source would leave its imported posts
 * without attribution. Switch "Fetch this source every hour" off instead.
 */
class EditSource extends EditRecord
{
    protected static string $resource = SourceResource::class;
}
