<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpFoundation\Response;


class AuthController extends Controller
{

    public function register(Request $request)
    {

        $errorMessages = [
            'name.required' => 'El nombre es obligatorio',
            'email.required' => 'El email es obligatorio',
            'password.required' => 'La contraseña es obligatoria',
            'password.string' => 'La contraseña no es valida',
            'password.max' => 'La contraseña no es valida',
            'password.min' => 'La contraseña no es valida',
            'email.email' => 'El email no es valido',
            'email.string' => 'El email no es valido',
            'email.max' => 'El email no es valido',
            'email.min' => 'El email no es valido',
            'email.unique' => 'El email ya existe',
            'name.regex' => 'El nombre solo puede tener letras, numeros y los siguientes caracteres: @|()[]-_',
            'email_confirmation.required' => 'La confirmación del email es obligatoria',
            'email_confirmation.email' => 'El email no es valido',
            'email_confirmation.string' => 'El email no es valido',
            'email_confirmation.max' => 'El email no es valido',
            'email_confirmation.min' => 'El email no es valido',
            'password_confirmation.required' => 'La confirmación de la contraseña es obligatoria',
            'password_confirmation.string' => 'La confirmación de la contraseña no es valida',
            'password_confirmation.max' => 'La confirmación de la contraseña no es valida',
            'password_confirmation.min' => 'La confirmación de la contraseña no es valida',

        ];
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:40',
                'min:4',
                'regex:/^[\w@|\-()\[\]]+$/', // Expresión regular personalizada
            ],
            'email' => 'required|string|email|max:255|unique:users|confirmed',
            'email_confirmation' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
        ], $errorMessages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_BAD_REQUEST);
        }

        $user = User::create([
            'name' => $request->name,
            'role' => 'guest',
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(compact('user'), Response::HTTP_CREATED);
    }

    public function login(Request $request)
    {
        $errorMessages = [
            'email.required' => 'El email es obligatorio',
            'password.required' => 'La contraseña es obligatoria',
        ];
        Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ], $errorMessages);
        $verifIfExists = User::select('email')->where('email', $request->email)->orWhere('name', $request->email)->first();
        if (!$verifIfExists) {
            return response()->json(['error' => 'El email no se encuentra registrado'], Response::HTTP_UNAUTHORIZED);
        }
        if (!$token = auth::attempt([
            'email' => $verifIfExists->email,
            'password' => $request->password
        ])) {
            return response()->json(['error' => 'Contraseña incorrecta, intenta nuevamente'], Response::HTTP_UNAUTHORIZED);
        }
        $cookie = cookie('jwt', $token, 60, "/", null, false, true, false, false, "lax");
        $userData = [
            'id' => Crypt::encryptString(auth::user()->id),
            'name' => auth::user()->name,
            'role' => auth::user()->role,
            'email' => auth::user()->email,
            'parche' => auth::user()->parche,
            'club' => auth::user()->club
        ];
        return response()->json(["user_session" => $userData], Response::HTTP_OK)->withCookie($cookie);
    }

    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'Se ha cerrado la sesión'], Response::HTTP_OK);
    }

    public function forgotPassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ]);
        $exists = User::where('email', $request->email)->exists();
        if (!$exists) {
            return response()->json(['error' => 'Su email no se encuentra registrado'], Response::HTTP_BAD_REQUEST);
        }
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }
        $status = Password::sendResetLink(
            $request->only('email')
        );
        return $status === Password::RESET_LINK_SENT ? response()->json(['status' => __($status)], Response::HTTP_OK) : response()->json(['error' => 'Hubo un error inesperado: ' . $status . '. Inténtalo de nuevo'], Response::HTTP_BAD_REQUEST);
    }
    public function resetPassword(Request $request)
    {
        $tokenMessahe = [
            'token.required' => 'El token es obligatorio',
            'email.required' => 'El email es obligatorio',
            'password.required' => 'La nueva contraseña es obligatoria',
            'password_confirmation.required' => 'La confirmación de la nueva contraseña es obligatoria',
        ];
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
        ], $tokenMessahe);
        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_BAD_REQUEST);
        }
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET ? response()->json(['status' => 'Ya puedes iniciar sesión con tu nueva contraseña'], Response::HTTP_OK) : response()->json(['error' => 'Token vencido, genera otro para continuar'], Response::HTTP_BAD_REQUEST);
    }
}
