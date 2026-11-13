<?php

return [
    'title' => 'Sliding door builder',
    'navigation_label' => 'Builder',
    'steps_label' => 'Door builder steps',
    'catalog' => [
        'no_models_heading' => 'No door systems available',
        'no_models_description' => 'There are currently no door systems available to configure.',
        'no_infills_heading' => 'No infills available',
        'no_infills_description' => 'Infills are currently unavailable. Please try again later.',
    ],
    'finishes' => [
        'unavailable' => 'This finish is no longer available. Choose another finish or clear it.',
        'incompatible' => 'Incompatible with the selected system and options',
        'replace_heading' => 'Replace infills?',
        'replace_description' => 'This change will replace or remove existing infills. Continue?',
        'cancel' => 'Cancel',
        'replace' => 'Replace finishes',

        'title' => 'Infill',
        'unselected' => 'No finish selected',
    ],
    'size' => [
        'door_count' => 'Number of doors',
        'no_door_counts' => 'No door count from 2 to 5 fits these dimensions with the available models.',
        'partition_summary' => 'Partitions: :count; infills per door: :infills.',

        'unit' => 'mm',
        'width' => 'Width (mm)',
        'height' => 'Height (mm)',
    ],
    'system' => [
        'discouraged_height' => 'Discouraged catalog height (scope unconfirmed)',
        'range_width' => 'Catalog width',
        'range_height' => 'Catalog height',
        'range_variantHeight' => 'Variant height',

        'soft_close' => 'Soft close',

        'model' => 'Door model',
        'design' => 'Frame design',
        'variant' => 'Colour and variant',
        'choose' => 'Choose an option',
    ],
    'navigation' => [
        'reset' => 'Reset design',
        'reset_heading' => 'Reset this design?',
        'reset_description' => 'Your measurements and selections will be cleared.',

        'previous' => 'Previous',
        'next' => 'Next',
    ],
    'validation' => [
        'shared_infills_mismatch' => 'Use the same infill for this infill on all doors.',
        'no_variant_for_height' => 'No available variant supports this height.',
        'variant_components_mismatch' => 'The components must match the selected variant. Choose an available variant.',
        'variant_height_mismatch' => 'Choose a variant available for this height.',

        'model_unavailable' => 'This model has no variants with partition components. Choose another model.',
        'model_structure_changed' => 'The infill divisions must match the selected model.',

        'geometry_invalid' => 'These dimensions cannot produce a preview.',
        'finish_incompatible' => 'Door :door, infill :infill: finish is incompatible with the selected system/options.',
        'partition_missing' => 'This variant has no partition component. Choose another variant.',
        'soft_close_provisional' => 'Soft-close hardware quantity and installation eligibility require confirmation.',
        'discouraged_height_reference' => 'The catalog includes a discouraged height range; its measurement scope requires confirmation.',
    ],
    'review' => [
        'not_included' => 'Not included',
        'shared_infills' => 'Infills',
        'divisions' => 'Infill divisions',

        'export' => 'Download configuration (JSON)',
        'export_failed' => 'The configuration could not be downloaded. Please try again.',

        'print' => 'Print design',
        'edit' => 'Edit',
        'edit_size' => 'Edit size',
        'edit_system' => 'Edit system',
        'edit_layout' => 'Edit finishes',

        'colour' => 'Colour',
        'yes' => 'Yes',
        'no' => 'No',
    ],
    'drafts' => [
        'outdated_infills' => 'This saved design has different infills on each door. Start a new design to use shared infills.',
        'outdated_layout' => 'The saved infill divisions no longer match this model. Start a new design.',

        'discard' => 'Remove saved draft',
        'restored' => 'Your last valid design was restored from this browser.',
        'outdated' => 'Your saved design needs changes because catalog options have changed. Your choices are kept for correction.',
        'invalid' => 'The saved design could not be read. Start a new design.',

        'saved' => 'Last valid design saved in this browser on this device.',
        'unavailable' => 'Browser storage is unavailable. You can continue without saving.',
    ],
    'steps' => [
        'opening' => [
            'label' => 'Size and doors',
            'description' => 'Enter width and height, then choose an available door count and model.',
        ],
        'system' => [
            'label' => 'Frame and colour',
            'description' => 'Choose the frame design, colour and options.',
        ],
        'infills' => [
            'label' => 'Infills',
            'description' => 'Choose a finish for each infill.',
        ],
        'review' => [
            'label' => 'Review',
            'description' => 'Review the measurements and selections for your doors.',
        ],
    ],
    'preview' => [
        'drag_hint' => 'Drag a filled infill onto another to swap them, or drop it in the remove area.',
        'choose_infill' => 'Choose infill',
        'remove_infill' => 'Remove infill',
        'drop_remove' => 'Drop here to remove',

        'layout_label' => ':infills infills (:partitions partitions)',

        'title' => 'Door preview',
        'drawing_label' => 'Illustrative sliding door front view with opening dimensions',
        'infills_label' => 'Select an infill',
        'door_label' => 'Door :number',
        'infill_short_label' => 'Infill :number',
        'infill_label' => 'Infill :infill on all doors',
        'no_selection' => 'No infill selected.',
        'infill_selected' => 'Infill :infill selected on all doors.',
        'width_annotation' => 'Opening width: :value mm',
        'height_annotation' => 'Opening height: :value mm',
        'empty_heading' => 'No preview yet',
        'empty_description' => 'Your door design will appear here.',
    ],
    'summary' => [
        'title' => 'Design summary',
        'empty_heading' => 'No selections yet',
        'empty_description' => 'Your measurements and door selections will appear here.',
    ],
];
