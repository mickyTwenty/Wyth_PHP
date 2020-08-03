<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LoginTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testPostUserLoginEmpty()
    {
        $this->json('POST', 'api/v1/login')
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'error_code'
            ])
            ->assertJson([
                'status' => false,
                'error_code' => 'validation_error',
            ]);
    }

    public function testUserGeneratingAndLogin()
    {
        $payload = ['email' => 'normal@appmaisters.com', 'password' => 'abc123'];

        $this->json('POST', 'api/v1/login', $payload)
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'body' => [
                    'user_id',
                    '_token',
                ]
            ])
            ->assertJson([
                'status' => true,
            ]);
    }
}
