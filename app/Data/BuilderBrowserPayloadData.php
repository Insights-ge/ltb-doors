<?php

namespace App\Data;

use JsonSerializable;

readonly class BuilderBrowserPayloadData implements JsonSerializable
{
    public function __construct(
        public BuilderCatalogData $catalog,
        public BuilderMetadataData $metadata,
    ) {}

    /**
     * Metadata contains visual mappings and translated presentation labels.
     * It must not be merged into catalog records or imply manufacturing rules.
     *
     * @return array{catalog: BuilderCatalogData, metadata: BuilderMetadataData}
     */
    public function jsonSerialize(): array
    {
        return [
            'catalog' => $this->catalog,
            'metadata' => $this->metadata,
        ];
    }
}
