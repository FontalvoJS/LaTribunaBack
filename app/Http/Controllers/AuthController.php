<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\EmailVerifications;
use App\Models\Post;
use Illuminate\Support\Facades\Notification;
use App\Notifications\VerifyEmailNotify;
use Exception;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        try {
            $errorMessages = [
                'name.required' => 'El nombre es obligatorio',
                'name.string' => 'El nombre no es valido',
                'name.max' => 'El nombre no es valido',
                'name.min' => 'El nombre no es valido',
                'name.regex' => 'El nombre solo puede tener letras, numeros y los siguientes caracteres: @|()[]-_',
                'name.unique' => 'El nombre ya existe',
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
                    'unique:users',
                ],
                'email' => 'required|string|email|max:255|unique:users|confirmed',
                'email_confirmation' => 'required|string|email|max:255',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string|min:8',
            ], $errorMessages);

            if ($validator->fails()) {
                return response()->json($validator->errors(), Response::HTTP_BAD_REQUEST);
            }
            if (isset($request->verifyReg) && $request->verifyReg == true) {
                return response()->json(['message' => 'Se validaron los datos a registrar'], Response::HTTP_ACCEPTED);
            }
            User::create([
                'name' => $request->name,
                'role' => 'guest',
                'email' => $request->email,
                'active' => true,
                'password' => Hash::make($request->password),
            ]);
            return response()->json(['message' => 'Te has registrado exitosamente, ya puedes iniciar sesión.'], Response::HTTP_CREATED);
        } catch (Exception $e) {
            return response()->json(['error' => 'Hubo un error inesperado, inténtalo de nuevo. ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
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

        $remember = $request->has('remember') && $request->remember === "true";

        // Duración de la cookie: 60 minutos si no se recuerda, 43200 minutos (30 días) si se recuerda
        $cookieDuration = $remember ? 43200 : 120;

        $cookie = cookie('jwt', $token, $cookieDuration, "/", null, false, true, false, false, "lax");

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

    public function logout(Request $request)
    {
        try {
            $token = $request->cookie('jwt');
            if (!empty($token)) {
                auth::setToken($token)->invalidate();
                $fake_cookie = cookie('jwt', '', -1, "/", null, false, true, false, false, "lax");
                return response()->json(['message' => 'Sesión finalizada'], Response::HTTP_OK)->withCookie($fake_cookie);
            }
        } catch (JWTException $e) {
            return response()->json(['error: ' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
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

        return $status === Password::PASSWORD_RESET ? response()->json(['message' => 'Ya puedes iniciar sesión con tu nueva contraseña'], Response::HTTP_OK) : response()->json(['error' => 'Token vencido, genera otro para continuar'], Response::HTTP_BAD_REQUEST);
    }
    public function verifyToken($token)
    {
        if (!$token) {
            return response()->json(['error' => 'No existe token'], Response::HTTP_UNAUTHORIZED);
        }
        if (!auth::setToken($token)->user()) {
            return response()->json(['error' => 'Token inválido'], Response::HTTP_UNAUTHORIZED);
        }
        return response()->json(['message' => 'Token valido'], Response::HTTP_OK);
    }

    public function generateToken($email)
    {
        try {
            $email_verifications_table = new EmailVerifications();
            $alreadyExists = $email_verifications_table->where('email', $email)->exists();
            $userExists = User::where('email', $email)->exists();
            if ($userExists) {
                return response()->json(['error' => 'El usuario ya existe'], Response::HTTP_BAD_REQUEST);
            }
            if ($alreadyExists) {
                $email_verifications_table->where('email', $email)->delete();
            }
            $code = rand(100000, 999999);
            $email_verifications_table->token = $code;
            $email_verifications_table->token_type = 'email';
            $email_verifications_table->email = $email;
            $email_verifications_table->save();
            Notification::route('mail', $email)->notify(new VerifyEmailNotify($code));
            return response()->json(['message' => 'Se ha enviado un correo de verificación'], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json(['error' => 'Hubo un error inesperado. Inténtalo de nuevo. ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
    public function verifyEmail(Request $request)
    {
        $errorMessages = [
            'token.required' => 'El token es obligatorio',
        ];
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ], $errorMessages);
        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_BAD_REQUEST);
        }
        $token = $request->token;
        if (!$token && strlen($token) > 6 || strlen($token) < 6) {
            return response()->json(['error' => 'Codigo inválido'], Response::HTTP_UNAUTHORIZED);
        }
        $email_verifications_table = new EmailVerifications();
        $verifyCode = $email_verifications_table->where(['token' => $token, 'token_type' => 'email', 'active' => true])->first();
        if (!$verifyCode) {
            return response()->json(['error' => 'El codigo no coincide'], Response::HTTP_UNAUTHORIZED);
        }
        try {
            $response = $this->register($request);
            $email_verifications_table->where('token', $token)->update(['verified' => true, 'active' => false]);
            $user = User::where('email', $request->email)->first();
            if ($user) {
                $user->email_verified = true;
                $user->active = true;
                $user->save();
            }
            return $response;
        } catch (Exception $e) {
            return response()->json(['error' => 'Hubo un error inesperado. Inténtalo de nuevo. ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
