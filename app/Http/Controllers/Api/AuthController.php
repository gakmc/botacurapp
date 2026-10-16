<?php

namespace App\Http\Controllers\Api;

use App\Auditoria;
use App\Http\Controllers\Controller;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use App\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $credentials = $request->only('email', 'password');
        $auditoria   = app(AuditoriaService::class);

        // El guard JWT no dispara eventos Login/Failed: se audita aquí
        if (! $token = auth('api')->attempt($credentials)) {
            $user = User::where('email', $request->email)->first();

            $auditoria->registrarComo(null, [
                'categoria'   => Auditoria::CATEGORIA_SEGURIDAD,
                'evento'      => Auditoria::EVENTO_LOGIN_FALLIDO,
                'auditable'   => $user,
                'contexto'    => $user,
                'user_id'     => $user ? $user->id : null,
                'descripcion' => 'intentó iniciar sesión en la API con el email "' . $request->email . '"'
                    . ($user ? ' (contraseña incorrecta)' : ' (email no registrado)'),
            ], Auditoria::ACTOR_INVITADO);

            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

        $user = auth('api')->user();
        $auditoria->registrarComo($user, [
            'categoria'   => Auditoria::CATEGORIA_SEGURIDAD,
            'evento'      => Auditoria::EVENTO_LOGIN,
            'auditable'   => $user,
            'contexto'    => $user,
            'descripcion' => 'inició sesión en la API',
        ]);

        return $this->respondWithToken($token);
    }

    public function me()
    {
        return response()->json(auth('api')->user());
    }

    public function logout()
    {
        $user = auth('api')->user();
        if ($user) {
            app(AuditoriaService::class)->registrarComo($user, [
                'categoria'   => Auditoria::CATEGORIA_SEGURIDAD,
                'evento'      => Auditoria::EVENTO_LOGOUT,
                'auditable'   => $user,
                'contexto'    => $user,
                'descripcion' => 'cerró sesión en la API',
            ]);
        }

        auth('api')->logout();
        return response()->json(['message' => 'Sesión cerrada']);
    }

    public function refresh()
    {
        return $this->respondWithToken(JWTAuth::refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ]);
    }
}
