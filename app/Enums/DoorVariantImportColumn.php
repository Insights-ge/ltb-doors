<?php

namespace App\Enums;

/**
 * The header row of the door variant import spreadsheet.
 */
enum DoorVariantImportColumn: string
{
    case ModelName = 'model_name';
    case ProductFullName = 'product_full_name';
    case ProductCode = 'product_code';
    case Color = 'color';
    case FrameDesignCode = 'frame_design_code';
    case UniqueCode = 'unique_code';
    case HeightRangeMin = 'height_range_min';
    case HeightRangeMax = 'height_range_max';
    case SideProfileUniqueCode = 'side_profile_unique_code';
    case TopRailUniqueCode = 'top_rail_unique_code';
    case BottomRailUniqueCode = 'bottom_rail_unique_code';
    case PartitionUniqueCode = 'partition_unique_code';
    case SoftCloseMechanismUniqueCode = 'soft_close_mechanism_unique_code';

    /**
     * The type of the component a column refers to by unique code, or null when the column isn't a component.
     */
    public function componentType(): ?ComponentType
    {
        return match ($this) {
            self::SideProfileUniqueCode => ComponentType::SideProfile,
            self::TopRailUniqueCode => ComponentType::TopRail,
            self::BottomRailUniqueCode => ComponentType::BottomRail,
            self::PartitionUniqueCode => ComponentType::Partition,
            self::SoftCloseMechanismUniqueCode => ComponentType::SoftCloseMechanism,
            default => null,
        };
    }
}
