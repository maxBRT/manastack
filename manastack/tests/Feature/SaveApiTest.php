<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Client;
use App\Models\Game;
use App\Models\Player;
use App\Models\Save;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SaveApiTest extends TestCase
{
    use RefreshDatabase;

    private Game $game;

    private Player $player;

    private Client $client;

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

        $this->player = Player::factory()->create([
            'game_id' => $this->game->id,
        ]);

        $this->client = Client::factory()->create([
            'player_id' => $this->player->id,
            'client_id' => 'device-abc-123',
        ]);
    }

    private function apiHeaders(): array
    {
        return ['X-API-Key' => $this->plainTextKey];
    }

    public function test_index_lists_saves_for_player(): void
    {
        Save::factory()->count(3)->create(['player_id' => $this->player->id]);

        $response = $this->getJson(
            "/api/saves/{$this->client->client_id}",
            $this->apiHeaders()
        );

        $response->assertStatus(200)->assertJsonCount(3, 'data');
    }

    public function test_index_returns_empty_list_when_no_saves(): void
    {
        $response = $this->getJson(
            "/api/saves/{$this->client->client_id}",
            $this->apiHeaders()
        );

        $response->assertStatus(200)->assertJsonCount(0, 'data');
    }

    public function test_store_creates_a_save(): void
    {
        $response = $this->postJson(
            "/api/saves/{$this->client->client_id}",
            ['name' => 'slot1', 'data' => ['level' => 5, 'hp' => 100]],
            $this->apiHeaders()
        );

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'slot1');
        $response->assertJsonPath('data.save_data', ['level' => 5, 'hp' => 100]);
        $response->assertJsonPath('data.player_id', $this->player->id);

        $this->assertDatabaseHas('saves', [
            'player_id' => $this->player->id,
            'name' => 'slot1',
        ]);
    }

    public function test_store_requires_name_and_data(): void
    {
        $response = $this->postJson(
            "/api/saves/{$this->client->client_id}",
            [],
            $this->apiHeaders()
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'data']);
    }

    public function test_show_returns_a_save(): void
    {
        $save = Save::factory()->create([
            'player_id' => $this->player->id,
            'name' => 'autosave',
            'data' => ['level' => 10],
        ]);

        $response = $this->getJson(
            "/api/saves/{$this->client->client_id}/{$save->id}",
            $this->apiHeaders()
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $save->id);
        $response->assertJsonPath('data.name', 'autosave');
        $response->assertJsonPath('data.save_data', ['level' => 10]);
    }

    public function test_show_returns_404_for_save_of_different_player(): void
    {
        $otherPlayer = Player::factory()->create(['game_id' => $this->game->id]);
        $save = Save::factory()->create(['player_id' => $otherPlayer->id]);

        $response = $this->getJson(
            "/api/saves/{$this->client->client_id}/{$save->id}",
            $this->apiHeaders()
        );

        $response->assertStatus(404);
    }

    public function test_update_modifies_a_save(): void
    {
        $save = Save::factory()->create([
            'player_id' => $this->player->id,
            'name' => 'slot1',
            'data' => ['level' => 1],
        ]);

        $response = $this->putJson(
            "/api/saves/{$this->client->client_id}/{$save->id}",
            ['data' => ['level' => 50, 'hp' => 200]],
            $this->apiHeaders()
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $save->id);
        $response->assertJsonPath('data.name', 'slot1');
        $response->assertJsonPath('data.save_data', ['level' => 50, 'hp' => 200]);
    }

    public function test_destroy_deletes_a_save(): void
    {
        $save = Save::factory()->create([
            'player_id' => $this->player->id,
        ]);

        $response = $this->deleteJson(
            "/api/saves/{$this->client->client_id}/{$save->id}",
            [],
            $this->apiHeaders()
        );

        $response->assertStatus(204);
        $this->assertDatabaseMissing('saves', ['id' => $save->id]);
    }

    public function test_destroy_returns_404_for_nonexistent_save(): void
    {
        $response = $this->deleteJson(
            "/api/saves/{$this->client->client_id}/nonexistent-id",
            [],
            $this->apiHeaders()
        );

        $response->assertStatus(404);
    }

    public function test_cannot_access_saves_for_client_from_different_game(): void
    {
        $otherGame = Game::factory()->create();
        $otherPlayer = Player::factory()->create(['game_id' => $otherGame->id]);
        $otherClient = Client::factory()->create(['player_id' => $otherPlayer->id]);

        $response = $this->getJson(
            "/api/saves/{$otherClient->client_id}",
            $this->apiHeaders()
        );

        $response->assertStatus(404);
    }

    public function test_returns_404_for_unknown_client_id(): void
    {
        $response = $this->getJson(
            '/api/saves/unknown-device-id',
            $this->apiHeaders()
        );

        $response->assertStatus(404);
    }

    public function test_multiple_clients_share_saves_through_player(): void
    {
        $secondClient = Client::factory()->create([
            'player_id' => $this->player->id,
            'client_id' => 'device-xyz-456',
        ]);

        Save::factory()->count(2)->create(['player_id' => $this->player->id]);

        $responseA = $this->getJson(
            "/api/saves/{$this->client->client_id}",
            $this->apiHeaders()
        );

        $responseB = $this->getJson(
            "/api/saves/{$secondClient->client_id}",
            $this->apiHeaders()
        );

        $responseA->assertStatus(200)->assertJsonCount(2, 'data');
        $responseB->assertStatus(200)->assertJsonCount(2, 'data');
    }
}
