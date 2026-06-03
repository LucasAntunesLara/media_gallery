<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImageRequest;
use App\Http\Requests\StoreVideoRequest;
use App\Models\Video;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class VideoController extends Controller {
    public function index(Request $request): JsonResponse {
        $user = null;

        try {
            $token = JWTAuth::getToken();

            if (!$token) throw new Exception('Token ausente na requisição.');

            $user = JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            $user = null;
        } catch (Exception $e) {
            if ($e->getMessage() === 'Token ausente na requisição.') {
                $user = null;
            } else {
                Log::error('Erro ao verificar token JWT no index de vídeos: ' . $e->getMessage(), [
                    'arquivo' => $e->getFile(),
                    'linha'    => $e->getLine(),
                    'trace'    => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'message' => 'Erro interno ao processar autenticação de vídeo.'
                ], 500);
            }
        }

        $per_page = $request->integer('per_page', 15);

        if ($request->has('per_page')) $per_page = $request->query('per_page');


        $videos = Video::query()
            ->unless($user, function ($query) {
                $query->where('isPrivate', false);
            })
            ->when($user, function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('isFavourite', false)
                        ->orWhereHas('favoritedByUsers', function ($pivotQuery) use ($user) {
                            $pivotQuery->where('user_id', $user->id);
                        });
                });
            })
            ->latest()
            ->paginate($per_page);

        return response()->json($videos);
    }
    public function show(Video $video) {
        $user = auth()->user();

        if ($video->isPrivate && !$user) return response()->json(['message' => 'Este vídeo é privado. Faça login para poder assisti-lo.'], 403);


        return response()->json([
            'id' => $video->id,
            'name' => $video->name,
            'isPrivate' => $video->isPrivate,
            'url' => $video->url,
            'tags' => $video->tags,
            'favorited' => $user ? $user->favoriteVideos()->where('video_id', $video->id)->exists() : false,
            'created_at' => $video->created_at->format('d F Y'),
        ]);
    }

    public function store(Request $request): JsonResponse {
        $videoPath = null;
        $thumbnailPath = null;

        try {
            if (!$request->hasFile('url') || !$request->file('url')->isValid()) return response()->json(['message' => 'Arquivo de vídeo inválido ou ausente.'], 400);

            $videoPath = $request->file('url')->store('videos', 'public');

            if ($request->hasFile('thumbnail') && $request->file('thumbnail')->isValid()) $thumbnailPath = $request->file('thumbnail')->store('images', 'public');

            if (!$videoPath) throw new Exception('Falha ao salvar o arquivo de vídeo no disco.');

            $newVideo = Video::create([
                'name'        => $request->input('name'),
                'url'         => $videoPath,
                'thumbnail'   => $thumbnailPath,
                'tags'        => $request->input('tags'),
                'isPrivate'   => $request->boolean('isPrivate', false),
            ]);

            return response()->json([
                'message' => 'Novo vídeo adicionado com sucesso!',
                'video'   => $newVideo,
            ], 201);
        } catch (Exception $e) {
            if ($videoPath) $this->safeDeleteFile($videoPath);
            if ($thumbnailPath) $this->safeDeleteFile($thumbnailPath);

            Log::error('Erro ao cadastrar vídeo: ' . $e->getMessage(), [
                'arquivo' => $e->getFile(),
                'linha'    => $e->getLine(),
                'trace'   => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erro interno no servidor ao processar o upload: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Video $video) {
        try {
            $this->safeDeleteFile($video->url);
            $this->safeDeleteFile($video->thumbnail);

            if (method_exists($video, 'favoritedByUsers')) $video->favoritedByUsers()->detach();

            $video->delete();

            return response()->json([
                'message' => 'Vídeo removido com sucesso!',
            ]);
        } catch (Exception $e) {
            Log::error('Erro crítico ao deletar vídeo com id [' . $video->id . ']', [
                'mensagem' => $e->getMessage(),
                'linha' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Erro interno ao tentar remover o vídeo.',
            ], 500);
        }
    }

    private function safeDeleteFile(?string $path): void {
        if (empty($path)) return;

        try {
            $cleanPath = preg_replace('/^http(s)?:\/\/[^\/]+/', '', $path);
            $cleanPath = ltrim($cleanPath, '/');
            if (str_starts_with($cleanPath, 'storage/')) {
                $cleanPath = substr($cleanPath, 8);
            }

            if (Storage::disk('public')->exists($cleanPath)) {
                Storage::disk('public')->delete($cleanPath);
            }
        } catch (Exception $e) {
            Log::warning("Não foi possível remover o arquivo físico no caminho [{$path}]: " . $e->getMessage());
        }
    }
}
