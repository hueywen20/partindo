<?php

namespace App\Filament\Admin\Resources\Uoms\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class UomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
                TextInput::make('name')
                    ->required(),
                Toggle::make('status')
                    ->default(1)
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
