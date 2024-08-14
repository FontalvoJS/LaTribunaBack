<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class EnsureTokenIsValid
{
    public function handle(Request $request, Closure $next)
    {
        $removeCookie = cookie('jwt', '', -1, "/", null, false, true, false, false, "lax");
        $path = $request->getRequestUri();
        $isAuthUrl = preg_match('/\/auth\//', $path);
        $articles = preg_match('/\/articles\//', $path);
        if ($isAuthUrl || $articles) {
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
            return response()->json(['error' => 'La sesión ha expirado'], Response::HTTP_UNAUTHORIZED)->withCookie($removeCookie);
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'La sesión no es valida'], Response::HTTP_UNAUTHORIZED)->withCookie($removeCookie);
        } catch (JWTException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNAUTHORIZED)->withCookie($removeCookie);
        }
        return response()->json(['error' => 'Usuario no autenticado'], Response::HTTP_UNAUTHORIZED)->withCookie($removeCookie);
    }
}
