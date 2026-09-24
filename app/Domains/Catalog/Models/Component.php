<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Catalog\Enums\ComponentType;
use App\Domains\Catalog\Enums\DoorColor;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'code', 'description', 'color', 'unique_code'])]
class Component extends Model
{
    protected function casts(): array
    {
        return [
            'type' => ComponentType::class,
            'color' => DoorColor::class,
        ];
    }
}
