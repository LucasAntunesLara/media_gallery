<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class VideoFavoriteController extends Controller {

    public function addFavorite($videoId) {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $video = Video::find($videoId);

        if (!$video) {
            return response()->json(['error' => 'Vídeo não encontrado'], 404);
        }

        $user->favoriteVideos()->attach($video);

        return response()->json(['message' => 'Vídeo adicionado aos favoritos com sucesso']);
    }

    public function removeFavorite($videoId) {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $video = Video::find($videoId);

        if (!$video) {
            return response()->json(['error' => 'Vídeo não encontrado'], 404);
        }

        $user->favoriteVideos()->detach($video);

        return response()->json(['message' => 'O vídeo foi removido dos favoritos.']);
    }
}
