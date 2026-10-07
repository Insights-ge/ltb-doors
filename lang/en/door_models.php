<?php

return [
    'layout' => [
        'door_models' => 'Door Models',
        'create_door_model' => 'Create Door Model',
        'edit_door_model' => 'Edit Door Model',
    ],
    'table' => [
        'status' => 'Status',
        'name' => 'Name',
        'height_range' => 'Height range',
        'width_range' => 'Width range',
        'not_recommended_height_range' => 'Not recommended height',
        'variants_count' => 'Variants',
        'created_at' => 'Created at',
        'empty_heading' => 'No door models found',
        'empty_description' => 'Door models are seeded from the catalog import.',
    ],
    'form' => [
        'status' => 'Status',
        'name' => 'Name',
        'sizing_section' => 'Sizing',
        'min_height' => 'Min height (mm)',
        'max_height' => 'Max height (mm)',
        'min_width' => 'Min width (mm)',
        'max_width' => 'Max width (mm)',
        'not_recommended_height_min' => 'Not recommended height from (mm)',
        'not_recommended_height_max' => 'Not recommended height to (mm)',
    ],
    'relations' => [
        'variants' => 'Variants',
    ],
];
