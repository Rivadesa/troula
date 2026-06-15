<?php

namespace App\Filament\Resources\CategoriaComplementoResource\Pages;

use App\Filament\Resources\CategoriaComplementoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategoriaComplemento extends EditRecord
{
    protected static string $resource = CategoriaComplementoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
