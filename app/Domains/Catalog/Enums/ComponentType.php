<?php

namespace App\Domains\Catalog\Enums;

enum ComponentType: string
{
    case SideProfile = 'side_profile';
    case TopRail = 'top_rail';
    case BottomRail = 'bottom_rail';
    case Partition = 'partition';
    case SoftCloseMechanism = 'soft_close_mechanism';
}
