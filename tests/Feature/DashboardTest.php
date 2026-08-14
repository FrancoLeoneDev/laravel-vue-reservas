<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `/dashboard` no renderiza nada propio: es el destino al que redirige Fortify
 * después del login y desde ahí cada rol se manda a donde le sirve. Por eso estos
 * tests esperan un 302 y no un 200.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_clients_land_on_their_bookings()
    {
        $this->actingAs(User::factory()->client()->create());

        $this->get(route('dashboard'))->assertRedirect(route('bookings.index'));
    }

    public function test_admins_land_on_the_agenda()
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('dashboard'))->assertRedirect(route('admin.agenda'));
    }
}
