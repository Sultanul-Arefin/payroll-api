<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $user = User::factory()->create();

        $response = $this->post('api/v1/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);

        $this->assertEquals(
            'User logged in successful',
            $response->json('message')
        );
        $this->assertEquals($user->email, $response->json('data')['email']);
        $this->assertEquals(
            $user->first_name,
            $response->json('data')['name']
        );
        $this->assertNotNull('token', $response->json('data')['token']);
        $response->assertOk();
    }

    /**
     * @test
     */
    public function users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password'
        ]);

        $this->assertGuest();
    }
}
