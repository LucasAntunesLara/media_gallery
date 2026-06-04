<?php

namespace Tests\Helpers;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

trait WithAuth {
    /**
     * Inicializa as configurações necessárias para o funcionamento do JWT nos testes.
     */
    protected function initializeWithAuth() {
        config(['auth.defaults.guard' => 'api']);
        config(['auth.guards.api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ]]);

        auth()->shouldUse('api');
    }

    /**
     * Gera os headers padrão com o Token JWT válido para o usuário informado.
     */
    protected function getAuthHeaders(User $user): array {
        $token = JWTAuth::fromUser($user);

        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ];
    }
}
