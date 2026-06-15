<?php

namespace App\Filament\Resources\CategoriaComplementoResource\Pages;

use App\Filament\Resources\CategoriaComplementoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategoriaComplementos extends ListRecords
{
    protected static string $resource = CategoriaComplementoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
