<?php

namespace App\Models;

use App\Enums\InfillTexture;
use Database\Factories\InfillFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['code', 'description', 'unique_code', 'visual_color', 'visual_texture'])]
#[UseFactory(InfillFactory::class)]
class Infill extends Model
{
    /** @use HasFactory<InfillFactory> */
    use HasFactory;

    /** @return BelongsToMany<Component, $this, ComponentInfill> */
    public function incompatibleComponents(): BelongsToMany
    {
        return $this->belongsToMany(Component::class)
            ->using(ComponentInfill::class)
            ->withTimestamps();
    }

    protected function casts(): array
    {
        return ['visual_texture' => InfillTexture::class];
    }
}
