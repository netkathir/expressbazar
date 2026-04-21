<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_is_available(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $response->assertSee('Admin Login');
    }

    public function test_admin_can_log_in_and_open_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@expressbazar.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);

        $dashboard = $this->actingAs($admin)->get('/admin/dashboard');
        $dashboard->assertOk();
        $dashboard->assertSee('Admin Dashboard');
    }

    public function test_guest_cannot_open_admin_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect(route('admin.login'));
    }
}
