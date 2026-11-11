<?php

return [
    'title' => 'Sliding door builder',
    'navigation_label' => 'Builder',
    'steps_label' => 'Door builder steps',
    'catalog' => [
        'no_models_heading' => 'No door systems available',
        'no_models_description' => 'There are currently no door systems available to configure.',
        'no_infills_heading' => 'No panel finishes available',
        'no_infills_description' => 'Panel finishes are currently unavailable. Please try again later.',
    ],
    'finishes' => [
        'unavailable' => 'This finish is no longer available. Choose another finish or clear it.',
        'incompatible' => 'Incompatible with the selected system and options',
        'replace_heading' => 'Replace panel finishes?',
        'replace_description' => 'This change will replace or remove existing panel finishes. Continue?',
        'cancel' => 'Cancel',
        'replace' => 'Replace finishes',

        'apply_all' => 'Apply finish to all doors',
        'apply_all_description' => 'Use the selected finish on every panel of both doors.',
        'copy_door' => 'Copy this door',
        'copy_description' => 'Copy all panel finishes from the selected door to the other door.',

        'title' => 'Panel finish',
        'select_panel' => 'Select a door and panel in the preview to edit its finish.',
        'unselected' => 'No finish selected',
    ],
    'size' => [
        'partition_summary' => 'Partitions: :count; panels per door: :panels.',

        'unit' => 'mm',
        'width' => 'Width (mm)',
        'height' => 'Height (mm)',
    ],
    'system' => [
        'discouraged_height' => 'Discouraged catalog height (scope unconfirmed)',
        'range_note' => 'Variant height ranges are for reference; their scope remains unconfirmed.',
        'range_width' => 'Catalog width',
        'range_height' => 'Catalog height',
        'range_variantHeight' => 'Variant height',

        'soft_close' => 'Soft close preference',
        'soft_close_note' => 'Hardware quantity and installation eligibility require confirmation.',

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
        'model_unavailable' => 'This model has no variants with partition components. Choose another model.',
        'model_structure_changed' => 'The panel divisions must match the selected model.',

        'geometry_invalid' => 'These dimensions cannot produce a preview.',
        'finish_incompatible' => 'Door :door, panel :panel: finish is incompatible with the selected system/options.',
        'partition_missing' => 'This variant has no partition component. Choose another variant.',
        'soft_close_missing' => 'Soft close is unavailable for this variant. Turn it off or choose another variant.',
        'sizing_unconfirmed' => 'Variant height range scope remains unconfirmed.',
        'soft_close_provisional' => 'Soft-close hardware quantity and installation eligibility require confirmation.',
        'discouraged_height_reference' => 'The catalog includes a discouraged height range; its measurement scope requires confirmation.',
    ],
    'review' => [
        'divisions' => 'Panel divisions',

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
        'outdated_layout' => 'The saved panel divisions no longer match this model. Start a new design.',

        'discard' => 'Remove saved draft',
        'restored' => 'Your last valid design was restored from this browser.',
        'outdated' => 'Your saved design needs changes because catalog options have changed. Your choices are kept for correction.',
        'invalid' => 'The saved design could not be read. Start a new design.',

        'saved' => 'Last valid design saved in this browser on this device.',
        'unavailable' => 'Browser storage is unavailable. You can continue without saving.',
    ],
    'steps' => [
        'opening' => [
            'label' => 'Model and size',
            'description' => 'Choose a door model, then enter width and height.',
        ],
        'system' => [
            'label' => 'Frame and colour',
            'description' => 'Choose the frame design, colour and options.',
        ],
        'panels' => [
            'label' => 'Panel finishes',
            'description' => 'Choose a finish for each panel.',
        ],
        'review' => [
            'label' => 'Review',
            'description' => 'Review the measurements and selections for your doors.',
        ],
    ],
    'preview' => [
        'layout_label' => ':panels panels (:partitions partitions)',

        'title' => 'Door preview',
        'drawing_label' => 'Illustrative sliding door front view with opening dimensions',
        'doors_label' => 'Select a door',
        'panels_label' => 'Select a panel',
        'door_label' => 'Door :number',
        'panel_short_label' => 'Panel :number',
        'panel_label' => 'Door :door, panel :panel',
        'panels_for_door' => 'Panels for door :number',
        'no_selection' => 'No door or panel selected.',
        'door_selected' => 'Door :number selected.',
        'panel_selected' => 'Door :door, panel :panel selected.',
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
