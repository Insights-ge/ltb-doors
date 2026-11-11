<?php

use App\Enums\DoorColor;

return [
    // Illustrative rendering colours, independent of catalog and admin badge colours.
    'neutral_fill' => '#B8B8B8',

    'frame_colors' => [
        DoorColor::Black->value => '#202020',
        DoorColor::Inox->value => '#A4A6A5',
        DoorColor::Graphite->value => '#4A4D4F',
        DoorColor::Silver->value => '#C5C7C9',
    ],

];
