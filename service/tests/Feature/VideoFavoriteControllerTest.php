<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Helpers\WithAuth;
use Tests\TestCase;


class VideoFavoriteControllerTest extends TestCase {
    use RefreshDatabase, WithAuth;


    protected function setUp(): void {
        parent::setUp();
        $this->initializeWithAuth();
    }

    public function test_authenticated_user_can_favorite_a_video(): void {
        $this->withoutExceptionHandling();

        /** @var User $user */
        $user = User::factory()->create();
        $video = Video::factory()->create();

        $response = $this->postJson(
            "/videos/{$video->id}/favorite",
            [],
            $this->getAuthHeaders($user)
        );

        $response->assertStatus(200)
            ->assertJson(['message' => 'Vídeo adicionado aos favoritos com sucesso']);

        $this->assertDatabaseHas('video_user_favorites', [
            'user_id' => $user->id,
            'video_id' => $video->id
        ]);
    }

    public function test_authenticated_user_can_remove_a_video_from_their_favorites() {
        $this->withoutExceptionHandling();

        /** @var User $user */
        $user = User::factory()->create();
        $video = Video::factory()->create();

        if (method_exists($user, 'favoriteVideos')) $user->favoriteVideos()->attach($video->id);

        $response = $this->deleteJson("/videos/{$video->id}/favorite", [], $this->getAuthHeaders($user));

        $response->assertStatus(200)
            ->assertJson(['message' => 'O vídeo foi removido dos favoritos.']);

        $this->assertDatabaseMissing('video_user_favorites', [
            'user_id' => $user->id,
            'video_id' => $video->id
        ]);
    }

    public function test_blocks_favorite_if_user_is_not_authenticated() {
        $video = Video::factory()->create();

        $response = $this->postJson("/videos/{$video->id}/favorite", [], [
            'Accept' => 'application/json'
        ]);

        $response->assertStatus(401);
    }

    public function test_returns_404_when_trying_to_mark_an_unexisting_video_as_favorite() {
        $this->withoutExceptionHandling();

        /** @var User $user */
        $user = User::factory()->create();
        $unexistingId = 9999;

        $response = $this->postJson("/videos/{$unexistingId}/favorite", [], $this->getAuthHeaders($user));

        $response->assertStatus(404)
            ->assertJson(['error' => 'Vídeo não encontrado']);
    }
}
