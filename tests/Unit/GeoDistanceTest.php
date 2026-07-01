<?php

namespace Tests\Unit;

use App\Support\GeoDistance;
use PHPUnit\Framework\TestCase;

class GeoDistanceTest extends TestCase
{
    public function test_it_marks_points_inside_and_outside_radius(): void
    {
        $distance = GeoDistance::meters(-6.2000000, 106.8166667, -6.2005000, 106.8166667);

        $this->assertGreaterThan(50, $distance);
        $this->assertLessThan(70, $distance);
        $this->assertSame('inside_radius', GeoDistance::locationStatus($distance, 100));
        $this->assertSame('outside_radius', GeoDistance::locationStatus($distance, 25));
    }
}
