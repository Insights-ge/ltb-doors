<?php

namespace App\Domains\Catalog\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'min_height',
    'max_height',
    'min_width',
    'max_width',
    'not_recommended_height_min',
    'not_recommended_height_max',
])]
class DoorModel extends Model
{
    /**
     * @return HasMany<DoorVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(DoorVariant::class);
    }

    protected function casts(): array
    {
        return [
            'min_height' => 'integer',
            'max_height' => 'integer',
            'min_width' => 'integer',
            'max_width' => 'integer',
            'not_recommended_height_min' => 'integer',
            'not_recommended_height_max' => 'integer',
        ];
    }
}
