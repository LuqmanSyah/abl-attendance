<?php

return [
    'office' => [
        'latitude' => env('ATTENDANCE_OFFICE_LATITUDE'),
        'longitude' => env('ATTENDANCE_OFFICE_LONGITUDE'),
        'radius_meters' => (int) env('ATTENDANCE_OFFICE_RADIUS_METERS', 100),
    ],

    'face' => [
        'enabled' => env('ATTENDANCE_FACE_ENABLED', true),
        'service_url' => env('ATTENDANCE_FACE_SERVICE_URL', 'http://127.0.0.1:5000'),
        'tolerance' => (float) env('ATTENDANCE_FACE_TOLERANCE', 0.5),
    ],
];
