<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Autentica al usuario y emite un token de acceso.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'errors' => ['Las credenciales proporcionadas son incorrectas.'],
            ], 422);
        }

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'token' => $token,
            ],
            'meta' => ['message' => 'Login exitoso.'],
        ]);
    }

    /**
     * Revoca el token actual del usuario.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'meta' => ['message' => 'Sesión cerrada correctamente.'],
        ]);
    }

    /**
     * Devuelve el perfil del usuario autenticado.
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('employee');

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'employee' => $user->employee,
            ],
        ]);
    }
}
