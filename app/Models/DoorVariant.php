<?php

namespace App\Models;

use App\Enums\DoorColor;
use Database\Factories\DoorVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
#[UseFactory(DoorVariantFactory::class)]
class DoorVariant extends Model
{
    /** @use HasFactory<DoorVariantFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<DoorModel, $this>
     */
    public function doorModel(): BelongsTo
    {
        return $this->belongsTo(DoorModel::class);
    }

    /**
     * @return BelongsTo<Component, $this>
     */
    public function sideProfile(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'side_profile_component_id');
    }

    /**
     * @return BelongsTo<Component, $this>
     */
    public function topRail(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'top_rail_component_id');
    }

    /**
     * @return BelongsTo<Component, $this>
     */
    public function bottomRail(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'bottom_rail_component_id');
    }

    /**
     * @return BelongsTo<Component, $this>
     */
    public function partition(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'partition_component_id');
    }

    /**
     * @return BelongsTo<Component, $this>
     */
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
