<?php

namespace App\Filament\Admin\Resources\Suppliers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('supplier_name')
                    ->required(),
                TextInput::make('company_name'),
                TextInput::make('address'),
                TextInput::make('phone_no')
                    ->tel(),
                Toggle::make('status')
                    ->default(1)
                    ->required()
                    ->columnSpanFull(),

            ]);
    }
}
