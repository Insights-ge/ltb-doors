<?php

return [
    'navigation_label' => 'Cache Tools',
    'title' => 'Cache Tools',
    'run' => 'Run',
    'sections' => [
        'general' => 'General',
        'clear' => 'Clear',
        'cache' => 'Cache',
    ],
    'commands' => [
        'optimize_clear' => 'optimize:clear',
        'filament_optimize_clear' => 'filament:optimize-clear',
        'optimize' => 'optimize',
        'filament_optimize' => 'filament:optimize',
        'permission_cache_reset' => 'permission:cache-reset',
        'config_clear' => 'config:clear',
        'cache_clear' => 'cache:clear',
        'view_clear' => 'view:clear',
        'route_clear' => 'route:clear',
        'event_clear' => 'event:clear',
        'config_cache' => 'config:cache',
        'route_cache' => 'route:cache',
        'view_cache' => 'view:cache',
        'event_cache' => 'event:cache',
        'icons_cache' => 'icons:cache',
        'permission_cache' => 'permission:cache',
    ],
    'notifications' => [
        'success_title' => 'Command executed',
        'success_body' => 'The command \':command\' was executed successfully.',
        'error_title' => 'Command failed',
    ],
];
