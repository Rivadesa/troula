<?php

namespace App\Filament\Resources\ConcelloZonaResource\Pages;

use App\Filament\Resources\ConcelloZonaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConcelloZonas extends ListRecords
{
    protected static string $resource = ConcelloZonaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
