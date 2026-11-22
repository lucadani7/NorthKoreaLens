<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    /** Posts without a source are written by the team member who creates them. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['source_id'])) {
            $data['user_id'] = auth()->id();
        }

        return $data;
    }
}
