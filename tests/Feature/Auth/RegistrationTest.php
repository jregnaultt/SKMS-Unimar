<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Role::create(['name' => 'estudiante']);
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'cedula' => 'V-12345678',
            'telefono' => '+584141234567',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'cedula' => 'V-12345678',
            'telefono' => '+584141234567',
        ]);
    }

    public function test_cedula_must_have_correct_format(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'cedula' => '12345678', // Invalid format, missing V- or E-
            'telefono' => '+584141234567',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['cedula']);
        $this->assertGuest();
    }

    public function test_telefono_must_be_valid(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'cedula' => 'V-12345678',
            'telefono' => '123456', // Invalid format
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['telefono']);
        $this->assertGuest();
    }

    public function test_new_users_get_estudiante_role(): void
    {
        // Ensure roles exist in the database (since spatie uses DB)
        Role::create(['name' => 'estudiante']);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'cedula' => 'V-12345678',
            'telefono' => '+584141234567',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();

        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue($user->hasRole('estudiante'));
    }
}
