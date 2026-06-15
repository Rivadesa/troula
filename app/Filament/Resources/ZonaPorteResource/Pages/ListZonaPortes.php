<?php

namespace App\Filament\Resources\ZonaPorteResource\Pages;

use App\Filament\Resources\ZonaPorteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListZonaPortes extends ListRecords
{
    protected static string $resource = ZonaPorteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
