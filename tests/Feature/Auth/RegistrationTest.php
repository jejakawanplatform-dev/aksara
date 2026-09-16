<?php

/**
 * Aksara — platform pembelajaran berbantuan AI.
 *
 * @copyright 2026 jejakawan (https://jejakawan.com)
 * @license   MIT
 *
 * Clone, fork, and modification are permitted under the MIT License.
 * See the LICENSE file in the project root.
 */

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_is_forbidden_when_public_registration_disabled(): void
    {
        setting()->set('security.allow_public_registration', false);

        $response = $this->get('/register');
        $response->assertStatus(403);
    }

    public function test_registration_screen_can_be_rendered_when_enabled(): void
    {
        setting()->set('security.allow_public_registration', true);

        $response = $this->get('/register');
        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Auth/Register'));
    }

    public function test_new_users_cannot_register_when_disabled(): void
    {
        setting()->set('security.allow_public_registration', false);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(403);
        $this->assertGuest();
    }

    public function test_new_users_can_register_when_enabled(): void
    {
        setting()->set('security.allow_public_registration', true);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
