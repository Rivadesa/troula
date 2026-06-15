<?php

namespace App\Filament\Resources\ExperienciaResource\Pages;

use App\Filament\Resources\ExperienciaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExperiencia extends EditRecord
{
    protected static string $resource = ExperienciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
