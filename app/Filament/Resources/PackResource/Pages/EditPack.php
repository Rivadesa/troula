<?php

namespace App\Filament\Resources\PackResource\Pages;

use App\Filament\Resources\PackResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPack extends EditRecord
{
    protected static string $resource = PackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
