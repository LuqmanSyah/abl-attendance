<?php

namespace App\Support;

class GeoDistance
{
    public static function meters(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): int
    {
        $earthRadiusMeters = 6371000;

        $fromLatitudeRadians = deg2rad($fromLatitude);
        $toLatitudeRadians = deg2rad($toLatitude);
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);

        $a = sin($latitudeDelta / 2) ** 2
            + cos($fromLatitudeRadians) * cos($toLatitudeRadians) * sin($longitudeDelta / 2) ** 2;

        return (int) round($earthRadiusMeters * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    public static function locationStatus(int $distanceMeters, int $radiusMeters): string
    {
        return $distanceMeters <= $radiusMeters
            ? 'inside_radius'
            : 'outside_radius';
    }
}
