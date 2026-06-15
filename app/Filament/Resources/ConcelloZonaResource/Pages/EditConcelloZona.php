<?php

namespace App\Filament\Resources\ConcelloZonaResource\Pages;

use App\Filament\Resources\ConcelloZonaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConcelloZona extends EditRecord
{
    protected static string $resource = ConcelloZonaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
