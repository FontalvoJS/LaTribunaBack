<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:40|min:4',
            'email' => 'required|string|email|max:255|unique:users|confirmed',
            'email_confirmation' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(compact('user', 'token'), 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $verifIfExists = User::where('email', $request->email)->first();
        if (!$verifIfExists) {
            return response()->json(['error' => '¿Qué espera que no se ha registrado?, usted se lo pierde.'], 401);
        }
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Contraseña incorrecta'], 401);
        }
        return response()->json(compact('token'), 200);
    }

    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'Bye bye, ¡Vuelve pronto!'], 200);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $status = Password::sendResetLink(
            $request->only('email')
        );
        return $status === Password::RESET_LINK_SENT ? response()->json(['status' => __($status)], 200) : response()->json(['error' => __($status)], 400);
    }
}
