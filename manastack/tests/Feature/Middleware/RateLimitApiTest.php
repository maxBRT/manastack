<?php

namespace Tests\Feature\Middleware;

use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class RateLimitApiTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear(key: 'ratelimit-key:test-key');
    }

    public function test_request_passes_when_under_rate_limit(): void
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
                'game' => $testApiKey->game(),
                ], 
            ['X-API-KEY' => $plainTextKey]
            );
        $response->assertStatus(201);
    }

    public function test_request_is_rejected_when_rate_limit_exceeded(): void
    {
        $plainTextKey = Str::random(40);
        ApiKey::factory()->create([
            'key' => hash('sha256', $plainTextKey),
        ]);
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/players', ['name' => 'Player', 'client_id'=> 'test-client-id'], ['X-API-Key' => $plainTextKey]);
        }
        $response = $this->postJson('/api/players', ['name' => 'Player', 'client_id'=> 'test-client-id'], ['X-API-Key' => $plainTextKey]);

        $response->assertStatus(429)
            ->assertJson([
                'status' => 'error',
                'message' => 'Too many requests.',
            ]);
    }

    public function test_response_includes_retry_after_header(): void
    {
        $plainTextKey = Str::random(40);
        ApiKey::factory()->create([
            'key' => hash('sha256', $plainTextKey),
        ]);
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/players', ['name' => 'Player', 'client_id'=> 'test-client-id'], ['X-API-Key' => $plainTextKey]);
        }
        $response = $this->postJson('/api/players', ['name' => 'Player', 'client_id'=> 'test-client-id'], ['X-API-Key' => $plainTextKey]);

        $response->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_rate_limit_is_per_api_key(): void
    {
        $plainTextKeyLimited = Str::random(40);
        ApiKey::factory()->create([
            'key' => hash('sha256', $plainTextKeyLimited),
        ]);
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/players', ['name' => 'Player', 'client_id'=> 'test-client-id'], ['X-API-Key' => $plainTextKeyLimited]);
        }
        $newPlainTextKey = Str::random(40);
        ApiKey::factory()->create([
            'key' => hash('sha256', $newPlainTextKey),
        ]);
        $response = $this->postJson('/api/players', ['name' => 'Player', 'client_id'=> 'test-client-id'], ['X-API-Key' => $newPlainTextKey]);
        $response->assertStatus(201);
    }
}
