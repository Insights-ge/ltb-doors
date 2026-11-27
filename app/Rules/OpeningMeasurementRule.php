<?php

namespace App\Rules;

use App\Data\BuilderDoorModelData;
use App\Enums\OpeningMeasurement;
use App\Services\BuilderOpeningSizing;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

readonly class OpeningMeasurementRule implements ValidationRule
{
    public function __construct(
        private OpeningMeasurement $measurement,
        private ?BuilderDoorModelData $model,
        private ?int $doorCount,
        private ?float $openingWidth,
        private ?float $openingHeight,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->model === null) {
            return;
        }

        if ($this->measurement === OpeningMeasurement::Width) {
            $width = BuilderOpeningSizing::measurement($value);
            if ($width !== null && $this->doorCount !== null && $this->doorCount >= 2 && $this->doorCount <= 5
                && ! BuilderOpeningSizing::hasVariantForWidth($this->model, $width, $this->doorCount)) {
                $fail('sliding_door_builder.validation.no_variant_for_width')->translate();
            }

            return;
        }

        if (BuilderOpeningSizing::matchingVariants($this->model, $this->openingWidth, $this->openingHeight, $this->doorCount) === []) {
            $fail('sliding_door_builder.validation.no_variant_for_dimensions')->translate();
        }
    }
}
