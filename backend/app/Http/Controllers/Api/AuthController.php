<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Hash bcrypt acak yang dipakai saat email tidak ditemukan, supaya waktu respons
     * login gagal selalu mirip (mempersulit menebak email mana yang terdaftar).
     */
    private const DUMMY_HASH = '$2y$12$Hm0/WYhaBVJVG6duUJFZlOanV22WXeP4sm7Z.5wWzkqOMUgfbT/ni';

    /**
     * POST /api/register → 201 + token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return $this->tokenResponse($user, Response::HTTP_CREATED);
    }

    /**
     * POST /api/login → 200 + token, atau 401 kalau kredensial salah.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();
        $passwordValid = Hash::check($request->validated('password'), $user?->password ?? self::DUMMY_HASH);

        if (! $user || ! $passwordValid) {
            return response()->json(['message' => 'Email atau password salah.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->tokenResponse($user, Response::HTTP_OK);
    }

    /**
     * GET /api/me → data user yang sedang login.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * POST /api/logout → cabut token yang sedang dipakai.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil logout.']);
    }

    private function tokenResponse(User $user, int $status): JsonResponse
    {
        $minutes = (int) config('sanctum.expiration');
        $expiresAt = $minutes > 0 ? now()->addMinutes($minutes) : null;

        $token = $user->createToken('spa', ['*'], $expiresAt);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt?->toIso8601String(),
            'user' => new UserResource($user),
        ], $status);
    }
}
