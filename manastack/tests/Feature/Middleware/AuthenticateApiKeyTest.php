<?php

namespace Tests\Feature\Middleware;

use App\Models\ApiKey;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticateApiKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_without_api_key_returns_401(): void
    {
        $response = $this->postJson('/api/players', ['name' => 'Player', 'client_id'=> 'test-client-id']);
        $response->assertStatus(401);
    }

    public function test_request_with_invalid_api_key_returns_401(): void
    {
        $response = $this->postJson('/api/players', ['name' => 'Player', 'client_id'=> 'test-client-id'], ['X-API-Key' => 'jello']);
        $response->assertStatus(401);
    }

    public function test_request_with_valid_api_key_passes_through(): void
    {
        $plainTextKey = Str::random(40);
        $testApiKey = ApiKey::factory()->create([
            'key' => hash('sha256', $plainTextKey),
        ]);

        $response = $this->postJson( 
            '/api/players', 
            [
                'name' => 'Test Player', 
                'client_id' => 'test-client-id',
                ], 
            ['X-API-KEY' => $plainTextKey]
            );
        $response->assertStatus(201);
    }

    public function test_valid_api_key_updates_last_used_at(): void
    {
        $plainTextKey = Str::random(40);
        $testApiKey = ApiKey::factory()->create([
            'key' => hash('sha256', $plainTextKey),
        ]);
        $initialTime = $testApiKey->last_used_at;
       
        $this->postJson( 
            '/api/players', 
            [
                'name' => 'Test Player', 
                'client_id' => 'test-client-id',
                ], 
            ['X-API-KEY' => $plainTextKey]
            );
        $testApiKey->refresh();
        $this->assertTrue($testApiKey->last_used_at > $initialTime);
    }
}