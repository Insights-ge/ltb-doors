<?php

namespace App\Models;

use App\Enums\DoorModelStatus;
use Database\Factories\DoorModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'status',
    'min_height',
    'max_height',
    'min_width',
    'max_width',
    'panel_layout',
    'not_recommended_height_min',
    'not_recommended_height_max',
])]
#[UseFactory(DoorModelFactory::class)]
class DoorModel extends Model
{
    /** @use HasFactory<DoorModelFactory> */
    use HasFactory;

    /** @return Attribute<int, never> */
    protected function partitionCount(): Attribute
    {
        return Attribute::get(fn (): int => $this->panel_layout === null ? 1 : count($this->panel_layout) - 1);
    }

    /**
     * @return HasMany<DoorVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(DoorVariant::class);
    }

    public function minimumPanelStackHeight(): ?int
    {
        if ($this->panel_layout === null) {
            return null;
        }

        return array_sum(array_map(
            fn (array $panel): int => $panel['type'] === 'fixed' ? $panel['fixed_mm'] : $panel['min_mm'],
            $this->panel_layout,
        ));
    }

    protected function casts(): array
    {
        return [
            'status' => DoorModelStatus::class,
            'min_height' => 'integer',
            'max_height' => 'integer',
            'min_width' => 'integer',
            'max_width' => 'integer',
            'panel_layout' => 'array',
            'not_recommended_height_min' => 'integer',
            'not_recommended_height_max' => 'integer',
        ];
    }
}
