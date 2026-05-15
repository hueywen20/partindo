<?php

namespace App\Filament\Admin\Resources\Brands\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
             ->components([
                TextInput::make('name')
                    ->required(),
                Toggle::make('status')
                    ->default(1)
                    ->required()
                    ->columnSpanFull(),
        ]);
    }
}
