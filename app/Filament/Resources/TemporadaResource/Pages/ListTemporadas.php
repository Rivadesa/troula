<?php

namespace App\Filament\Resources\TemporadaResource\Pages;

use App\Filament\Resources\TemporadaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTemporadas extends ListRecords
{
    protected static string $resource = TemporadaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
