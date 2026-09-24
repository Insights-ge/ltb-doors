<?php

namespace App\Models;

use App\Enums\ComponentType;
use App\Enums\DoorColor;
use Database\Factories\ComponentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'code', 'description', 'color', 'unique_code'])]
#[UseFactory(ComponentFactory::class)]
class Component extends Model
{
    /** @use HasFactory<ComponentFactory> */
    use HasFactory;

    public function isInUse(): bool
    {
        return DoorVariant::query()
            ->where('side_profile_component_id', $this->id)
            ->orWhere('top_rail_component_id', $this->id)
            ->orWhere('bottom_rail_component_id', $this->id)
            ->orWhere('partition_component_id', $this->id)
            ->orWhere('soft_close_mechanism_component_id', $this->id)
            ->exists();
    }

    protected function casts(): array
    {
        return [
            'type' => ComponentType::class,
            'color' => DoorColor::class,
        ];
    }
}
