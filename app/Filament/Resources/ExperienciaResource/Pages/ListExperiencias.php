<?php

namespace App\Filament\Resources\ExperienciaResource\Pages;

use App\Filament\Resources\ExperienciaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExperiencias extends ListRecords
{
    protected static string $resource = ExperienciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
