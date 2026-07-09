<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_redirects_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_the_login_page_returns_a_successful_response(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
    }

    public function test_authenticated_users_are_redirected_to_their_role_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($admin)
            ->get('/login')
            ->assertRedirect('/admin');

        $this->actingAs($supervisor)
            ->get('/login')
            ->assertRedirect('/atasan');

        $this->actingAs($employee)
            ->get('/login')
            ->assertRedirect('/pegawai');
    }

    public function test_login_redirects_to_the_matching_role_dashboard(): void
    {
        $supervisor = User::factory()->create([
            'email' => 'supervisor@example.com',
            'role' => 'supervisor',
        ]);

        $this->post('/login', [
            'email' => $supervisor->email,
            'password' => 'password',
        ])->assertRedirect('/atasan');

        $this->assertAuthenticatedAs($supervisor);
    }

    public function test_panel_access_matches_the_authenticated_user_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $employee = User::factory()->create(['role' => 'employee']);

        $this->get('/admin')->assertRedirect('/login');
        $this->get('/atasan')->assertRedirect('/login');
        $this->get('/pegawai')->assertRedirect('/login');

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($supervisor)->get('/atasan')->assertOk();
        $this->actingAs($employee)->get('/pegawai')->assertOk();

        $this->actingAs($employee)->get('/admin')->assertForbidden();
        $this->actingAs($admin)->get('/pegawai')->assertForbidden();
    }

    public function test_admin_can_open_attendance_settings_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/pengaturan-absensi')
            ->assertOk()
            ->assertSee('Pengaturan Absensi')
            ->assertSee('Latitude Kantor');
    }
}
