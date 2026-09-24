<?php

namespace App\Filament\Resources\DoorVariants\Schemas;

use App\Filament\Resources\DoorVariants\Schemas\Sections\ComponentsSection;
use App\Filament\Resources\DoorVariants\Schemas\Sections\GeneralInformationSection;
use App\Filament\Resources\DoorVariants\Schemas\Sections\SizingSection;
use Filament\Schemas\Schema;

class DoorVariantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                GeneralInformationSection::schema(),
                SizingSection::schema(),
                ComponentsSection::schema(),
            ]);
    }
}
