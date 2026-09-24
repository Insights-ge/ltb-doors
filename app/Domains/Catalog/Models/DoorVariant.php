<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Catalog\Enums\DoorColor;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'door_model_id',
    'product_code',
    'product_full_name',
    'color',
    'frame_design_code',
    'unique_code',
    'height_range_min',
    'height_range_max',
    'side_profile_component_id',
    'top_rail_component_id',
    'bottom_rail_component_id',
    'partition_component_id',
    'soft_close_mechanism_component_id',
])]
class DoorVariant extends Model
{
    public function doorModel(): BelongsTo
    {
        return $this->belongsTo(DoorModel::class);
    }

    public function sideProfile(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'side_profile_component_id');
    }

    public function topRail(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'top_rail_component_id');
    }

    public function bottomRail(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'bottom_rail_component_id');
    }

    public function partition(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'partition_component_id');
    }

    public function softCloseMechanism(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'soft_close_mechanism_component_id');
    }

    protected function casts(): array
    {
        return [
            'color' => DoorColor::class,
            'height_range_min' => 'integer',
            'height_range_max' => 'integer',
        ];
    }
}
