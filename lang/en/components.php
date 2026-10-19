<?php

return [
    'layout' => [
        'components' => 'Components',
        'create_component' => 'Create Component',
        'edit_component' => 'Edit Component',
    ],
    'table' => [
        'type' => 'Type',
        'code' => 'Code',
        'description' => 'Description',
        'color' => 'Color',
        'unique_code' => 'Unique code',
        'empty_heading' => 'No components found',
        'empty_description' => 'Components are seeded from the catalog import.',
        'delete_in_use_title' => 'Component is in use',
        'delete_in_use_body' => 'This component is used by one or more door variants and cannot be deleted.',
    ],
    'form' => [
        'type' => 'Type',
        'code' => 'Code',
        'description' => 'Description',
        'color' => 'Color',
        'unique_code' => 'Unique code',
    ],
    'import' => [
        'actions' => [
            'download_sample' => 'Download sample',
            'sample_file_name' => 'components-import-sample.xlsx',
            'import' => 'Import from Excel',
            'modal_heading' => 'Import components from Excel',
            'modal_description' => 'Columns: type, unique_code, code, description, color. Download the sample for the exact headers. Existing components are updated from the file; one error stops the whole import.',
            'modal_submit' => 'Import',
            'file' => 'Excel file (.xlsx)',
            'preview' => 'Preview',
            'preview_counts' => 'To create: :create · To update: :update · Unchanged: :unchanged',
            'preview_errors' => 'Errors found: :count. Fix the file and upload it again.',
            'file_has_errors' => 'The file has errors. Fix them and upload it again.',
            'queued_title' => 'Import started',
            'queued_body' => 'You will get a notification when it is finished.',
        ],
        'errors' => [
            'required' => ':column is required.',
            'too_long' => ':column may not be longer than :max characters.',
            'invalid_type' => 'Type ":value" is not valid. Allowed: :allowed.',
            'invalid_color' => 'Colour ":value" is not valid. Allowed: :allowed.',
            'duplicate_in_file' => 'Component :unique_code (:type) is also in row :row.',
        ],
        'notifications' => [
            'succeeded_title' => 'Component import finished',
            'succeeded_body' => ':created created, :updated updated, :unchanged unchanged.',
            'failed_title' => 'Component import failed',
            'failed_body' => 'Nothing was saved. Fix the file and import it again. Errors found: :count.',
            'crashed_body' => 'Something went wrong while importing. Nothing was saved. Please try again.',
        ],
    ],
];
