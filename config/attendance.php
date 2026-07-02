<?php

return [
    'office' => [
        'latitude' => env('ATTENDANCE_OFFICE_LATITUDE'),
        'longitude' => env('ATTENDANCE_OFFICE_LONGITUDE'),
        'radius_meters' => (int) env('ATTENDANCE_OFFICE_RADIUS_METERS', 100),
    ],
];
