<?php

namespace Tests\Feature;

use App\Models\Position;
use App\Models\User;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\PositionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_seeder_creates_users_with_roles_from_positions(): void
    {
        $this->seed([
            DivisionSeeder::class,
            PositionSeeder::class,
            EmployeeSeeder::class,
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'admin@example.com',
        ]);

        $this->assertEqualsCanonicalizing(['Atasan', 'Pegawai'], Position::query()->pluck('name')->all());
        $this->assertSame('supervisor', User::where('email', 'andi.pratama@example.com')->value('role'));
        $this->assertSame('employee', User::where('email', 'dedi.kurniawan@example.com')->value('role'));
        $this->assertSame('employee', User::where('email', 'budi.santoso@example.com')->value('role'));
    }
}
