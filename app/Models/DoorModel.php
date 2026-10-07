<?php

namespace App\Models;

use App\Enums\DoorModelStatus;
use Database\Factories\DoorModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
    'not_recommended_height_min',
    'not_recommended_height_max',
])]
#[UseFactory(DoorModelFactory::class)]
class DoorModel extends Model
{
    /** @use HasFactory<DoorModelFactory> */
    use HasFactory;

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
            'status' => DoorModelStatus::class,
            'min_height' => 'integer',
            'max_height' => 'integer',
            'min_width' => 'integer',
            'max_width' => 'integer',
            'not_recommended_height_min' => 'integer',
            'not_recommended_height_max' => 'integer',
        ];
    }
}
