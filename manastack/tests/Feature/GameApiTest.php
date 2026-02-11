<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Game;
use Tests\TestCase;

class GameApiTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_index_list_all_games(): void
    {
        Game::factory()->count(4)->create();
        $response = $this->get('/api/games');
        $response->assertStatus(200)->assertJsonCount(4, 'data');
    }

    public function test_index_return_empty_list(): void
    {
        $response = $this->get('/api/games');
        $response->assertStatus(200)->assertJsonCount(0, 'data');
    }
}
