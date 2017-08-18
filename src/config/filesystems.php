<?php
return [
    'media' => [
        'driver' => 'local',
        'root'   => public_path().'/media',
    ],

    'media-private' => [
        'driver' => 'local',
        'root'   => storage_path().'/app/media',
    ],

    'uploads' => [
        'driver' => 'local',
        'root'   => storage_path('uploads'),
    ],
];