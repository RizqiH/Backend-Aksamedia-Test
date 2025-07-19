<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\Admin;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Handle admin login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            Log::info('AuthController - Login method started', [
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'origin' => $request->header('Origin'),
                'csrf_token_header' => $request->header('X-CSRF-TOKEN') ? 'present' : 'missing',
            ]);

            $credentials = $request->validated();

            // Log untuk debugging
            Log::info('AuthController - Login attempt', [
                'username' => $credentials['username'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            $admin = Admin::where('username', $credentials['username'])->first();

            if (!$admin) {
                Log::warning('Login failed - User not found', ['username' => $credentials['username']]);
                return $this->unauthorizedResponse('Invalid credentials');
            }

            if (!Hash::check($credentials['password'], $admin->password)) {
                Log::warning('Login failed - Invalid password', ['username' => $credentials['username']]);
                return $this->unauthorizedResponse('Invalid credentials');
            }

            $token = $admin->createToken('auth_token')->plainTextToken;

            Log::info('Login successful', [
                'admin_id' => $admin->id,
                'username' => $admin->username
            ]);

            return $this->successResponse([
                'token' => $token,
                'admin' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'username' => $admin->username,
                    'phone' => $admin->phone,
                    'email' => $admin->email,
                ],
            ], 'Login successful');

        } catch (\Exception $e) {
            Log::error('AuthController - Login error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Handle admin logout
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return $this->successResponse(null, 'Logout successful');
        } catch (\Exception $e) {
            Log::error('Logout error', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id
            ]);
            return $this->errorResponse('Failed to logout', 500);
        }
    }
}
