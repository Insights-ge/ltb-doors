<?php

namespace App\Models;

use App\Enums\DoorModelStatus;
use App\Enums\DoorPanelType;
use Database\Factories\DoorModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
    public const MAX_PANELS = 7;

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

        $height = 0;
        foreach ($this->panel_layout as $panel) {
            if (! is_array($panel)) {
                return null;
            }

            $field = ($panel['type'] ?? null) === DoorPanelType::Fixed->value ? 'fixed_mm' : 'min_mm';
            $minimum = $panel[$field] ?? null;
            if (! is_int($minimum) || $minimum < 1 || $minimum > PHP_INT_MAX - $height) {
                return null;
            }

            $height += $minimum;
        }

        return $height;
    }

    /** @return Attribute<int<0, max>, never> */
    protected function partitionCount(): Attribute
    {
        return Attribute::get(fn (): int => $this->panel_layout === null ? 1 : max(0, count($this->panel_layout) - 1));
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
