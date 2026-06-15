<?php

namespace App\Filament\Resources\PackResource\Pages;

use App\Filament\Resources\PackResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPacks extends ListRecords
{
    protected static string $resource = PackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
