<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'purchase.password' => 'admin-password',
            'purchase.managing_director.email' => 'nurulhasan@example.com',
            'purchase.managing_director.password_hash' => Hash::make('nurulhasan'),
        ]);
    }

    public function test_managing_director_can_view_dashboard_and_month_reports_only(): void
    {
        $this->post(route('login.submit'), [
            'email' => 'nurulhasan@example.com',
            'password' => 'nurulhasan',
        ])->assertRedirect(route('home'))
            ->assertSessionHas('user_role', 'managing_director');

        $this->get(route('home'))->assertOk()
            ->assertDontSee(route('check.index'), false)
            ->assertDontSee(route('history.index'), false);
        $this->get(route('history.month', ['year' => '2026', 'month' => '01']))
            ->assertOk()
            ->assertDontSee('New entry');

        $this->get(route('check.index'))->assertForbidden();
        $this->get(route('history.index'))->assertForbidden();
        $this->get('/diag')->assertForbidden();

        $this->post(route('logout'))->assertRedirect(route('login'))
            ->assertSessionMissing('logged_in');
    }

    public function test_existing_admin_password_keeps_super_admin_access(): void
    {
        $this->post(route('login.submit'), ['password' => 'admin-password'])
            ->assertRedirect(route('home'))
            ->assertSessionHas('user_role', 'super_admin');

        $this->get(route('check.index'))->assertOk();
        $this->get(route('history.index'))->assertOk();
    }

    public function test_invalid_director_credentials_do_not_create_a_session(): void
    {
        $this->from(route('login'))->post(route('login.submit'), [
            'email' => 'nurulhasan@example.com',
            'password' => 'incorrect',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('credentials')
            ->assertSessionMissing('logged_in');
    }
}
