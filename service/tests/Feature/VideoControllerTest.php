<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\WithAuth;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class VideoControllerTest extends TestCase {
    use RefreshDatabase, WithAuth;

    /**
     * Configura o ambiente de testes antes de cada execução.
     */
    protected function setUp(): void {
        parent::setUp();

        // Garante que as configurações padrão de guards apontem para api
        config(['auth.defaults.guard' => 'api']);
        config(['auth.guards.api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ]]);

        auth()->shouldUse('api');
    }

    /**
     * Helper privado para gerar os headers com o Token JWT válido
     */
    // private function getAuthHeaders(User $user): array {
    //     $token = JWTAuth::fromUser($user);

    //     // Sincroniza o token gerado diretamente no estado estático do pacote JWT
    //     // Evita que o middleware zere o usuário logado se não conseguir ler o header
    //     JWTAuth::setToken($token);

    //     return [
    //         'Authorization' => 'Bearer ' . $token,
    //         'Accept' => 'application/json'
    //     ];
    // }

    public function test_unauthenticated_users_can_only_list_public_videos() {
        Video::factory()->count(2)->create(['isPrivate' => false]);
        Video::factory()->count(3)->create(['isPrivate' => true]);

        $response = $this->getJson('/videos/list');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_authenticated_users_can_access_public_and_private_videos() {
        /** @var User $user */
        $user = User::factory()->create();
        Video::factory()->create(['isPrivate' => false, 'isFavourite' => false]);
        Video::factory()->create(['isPrivate' => true, 'isFavourite' => false]);

        $headers = $this->getAuthHeaders($user);

        // Vincula o usuário diretamente no driver do JWT para esta thread
        $this->actingAs($user, 'api');

        $response = $this->getJson('/videos/list', $headers);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_blocks_access_to_a_private_video_if_user_is_not_authenticated() {
        $videoPrivado = Video::factory()->create(['isPrivate' => true]);

        $response = $this->getJson("/videos/show/{$videoPrivado->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Este vídeo é privado. Faça login para poder assisti-lo.'
            ]);
    }

    public function test_allows_access_to_a_private_video_if_the_user_is_authenticated() {
        $this->withoutExceptionHandling();

        /** @var User $user */
        $user = User::factory()->create();
        $videoPrivado = Video::factory()->create(['isPrivate' => true]);

        $headers = $this->getAuthHeaders($user);
        $this->actingAs($user, 'api');

        $response = $this->getJson("/videos/show/{$videoPrivado->id}", $headers);

        $response->assertStatus(200)
            ->assertJsonPath('id', $videoPrivado->id)
            ->assertJsonPath('favorited', false);
    }

    public function test_return_favorited_as_true_if_authenticated_user_has_marked_the_video_as_favorite() {
        $this->withoutExceptionHandling();

        /** @var User $user */
        $user = User::factory()->create();
        $video = Video::factory()->create(['isPrivate' => false]);

        if (method_exists($user, 'favoriteVideos')) {
            $user->favoriteVideos()->attach($video->id);
        }

        $headers = $this->getAuthHeaders($user);
        $this->actingAs($user, 'api');

        $response = $this->getJson("/videos/show/{$video->id}", $headers);

        $response->assertStatus(200)
            ->assertJsonPath('id', $video->id)
            ->assertJsonPath('favorited', true);
    }
}
