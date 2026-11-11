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
    'layouts' => [
        'no_partition' => 'This variant supports full panels only. Choose another variant to add divisions.',
        'remapping_description' => 'Finishes are kept from top to bottom. Removed panels are discarded; new panels start without a finish.',
        'title' => 'Shared layout',
        'shared_description' => 'Applies to both doors.',
        'full_panel' => 'Full panel',
        'two_horizontal_panels' => 'Two equal horizontal panels',
        'three_horizontal_panels' => 'Three equal horizontal panels',
    ],
    'finishes' => [
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
        'unit' => 'mm',
        'width' => 'Width (mm)',
        'height' => 'Height (mm)',
        'invalid' => 'Enter a positive measurement in millimetres.',
    ],
    'system' => [
        'discouraged_height' => 'Discouraged catalog height (scope unconfirmed)',
        'range_note' => 'Catalog ranges are for reference. Whether they describe the opening or a finished door is unconfirmed.',
        'range_width' => 'Catalog width',
        'range_height' => 'Catalog height',
        'range_variantHeight' => 'Variant height',

        'soft_close' => 'Soft close preference',
        'soft_close_note' => 'Hardware quantity and installation eligibility require confirmation.',

        'model' => 'Door system',
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
    'steps' => [
        'opening' => [
            'label' => 'Size',
            'description' => 'Set the width and height of the opening.',
        ],
        'system' => [
            'discouraged_height' => 'Discouraged catalog height (scope unconfirmed)',
            'range_note' => 'Catalog ranges are for reference. Whether they describe the opening or a finished door is unconfirmed.',
            'range_width' => 'Catalog width',
            'range_height' => 'Catalog height',
            'range_variantHeight' => 'Variant height',

            'soft_close' => 'Soft close preference',
            'soft_close_note' => 'Hardware quantity and installation eligibility require confirmation.',

            'label' => 'Door system and colour',
            'description' => 'Choose the door system and frame colour.',
        ],
        'panels' => [
            'label' => 'Layouts and finishes',
            'description' => 'Choose a shared layout and panel finishes for each door.',
        ],
        'review' => [
            'label' => 'Review',
            'description' => 'Review the measurements and selections for your doors.',
        ],
    ],
    'preview' => [
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
