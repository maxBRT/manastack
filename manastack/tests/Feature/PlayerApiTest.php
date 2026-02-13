<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Client;
use App\Models\Game;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlayerApiTest extends TestCase
{
    use RefreshDatabase;

    private Game $game;

    private string $plainTextKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->game = Game::factory()->create();
        $this->plainTextKey = Str::random(40);

        ApiKey::factory()->create([
            'game_id' => $this->game->id,
            'key' => hash('sha256', $this->plainTextKey),
        ]);
    }

    public function test_store_creates_a_new_player(): void
    {
        $response = $this->postJson('/api/players', [
            'client_id' => 'device-abc-123',
        ], ['X-API-Key' => $this->plainTextKey]);

        $response->assertStatus(201)->assertJsonPath('data.game_id', $this->game->id);
        $response->assertJsonPath('data.client_ids.0', 'device-abc-123');

        $this->assertDatabaseHas('clients', [
            'client_id' => 'device-abc-123',
        ]);
    }

    public function test_store_returns_existing_player_for_same_client_id(): void
    {
        $player = Player::factory()->create([
            'game_id' => $this->game->id,
        ]);
        Client::factory()->create([
            'player_id' => $player->id,
            'client_id' => 'device-abc-123',
        ]);

        $response = $this->postJson('/api/players', [
            'client_id' => 'device-abc-123',
        ], ['X-API-Key' => $this->plainTextKey]);

        $response->assertStatus(200)->assertJsonPath('data.id', $player->id);
        $response->assertJsonPath('data.client_ids.0', 'device-abc-123');

        $this->assertDatabaseCount('players', 1);
    }

    public function test_store_requires_client_id(): void
    {
        $response = $this->postJson('/api/players', [], [
            'X-API-Key' => $this->plainTextKey,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('client_id');
    }

    public function test_show_returns_a_player(): void
    {
        $player = Player::factory()->create([
            'game_id' => $this->game->id,
        ]);
        Client::factory()->create([
            'player_id' => $player->id,
            'client_id' => 'device-abc-123',
        ]);

        $response = $this->getJson("/api/players/{$player->id}", [
            'X-API-Key' => $this->plainTextKey,
        ]);

        $response->assertStatus(200)->assertJsonPath('data.id', $player->id);
        $response->assertJsonPath('data.client_ids.0', 'device-abc-123');
    }

    public function test_show_returns_404_for_player_from_different_game(): void
    {
        $otherGame = Game::factory()->create();
        $player = Player::factory()->create([
            'game_id' => $otherGame->id,
        ]);

        $response = $this->getJson("/api/players/{$player->id}", [
            'X-API-Key' => $this->plainTextKey,
        ]);

        $response->assertStatus(404);
    }

    public function test_request_without_api_key_returns_401(): void
    {
        $response = $this->postJson('/api/players', [
            'client_id' => 'device-abc-123',
        ]);

        $response->assertStatus(401);
    }

    public function test_request_with_invalid_api_key_returns_401(): void
    {
        $response = $this->postJson('/api/players', [
            'client_id' => 'device-abc-123',
        ], ['X-API-Key' => 'invalid-key']);

        $response->assertStatus(401);
    }

    public function test_request_with_expired_api_key_returns_401(): void
    {
        $expiredKey = Str::random(40);
        ApiKey::factory()->expired()->create([
            'game_id' => $this->game->id,
            'key' => hash('sha256', $expiredKey),
        ]);

        $response = $this->postJson('/api/players', [
            'client_id' => 'device-abc-123',
        ], ['X-API-Key' => $expiredKey]);

        $response->assertStatus(401);
    }
}
