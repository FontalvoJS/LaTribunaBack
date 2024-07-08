<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class EnsureTokenIsValid
{
    public function handle(Request $request, Closure $next)
    {
        $path = $request->getRequestUri();
        $isAuthUrl = preg_match('/\/auth\//', $path);

        if ($isAuthUrl) {
            return $next($request);
        }

        try {
            $token = $request->cookie('jwt');

            if (!$token) {
                throw new JWTException('Tu sesión ha expirado, por favor inicia nuevamente');
            }

            $user = auth::setToken($token)->user();
            if ($user) {
                return $next($request);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'La sesión ha expirado'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'La sesión no es valida'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Error de autenticación: ' . $e->getMessage()], 401);
        }
    }
}
