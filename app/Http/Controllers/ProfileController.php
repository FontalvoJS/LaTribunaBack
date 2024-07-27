<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class ProfileController extends Controller
{
    public function updateProfile(Request $request)
    {
        $errorMessages = [
            'name.required' => 'El nombre es obligatorio',
            'name.string' => 'El nombre no es válido',
            'name.min' => 'El nombre debe tener al menos 4 caracteres',
            'name.max' => 'El nombre no debe exceder 30 caracteres',
            'name.regex' => 'El nombre solo puede contener letras, números, y los caracteres []_-@',
            'name.unique' => 'El nombre ya está en uso',
            'email.required' => 'El correo electrónico es obligatorio',
            'email.string' => 'El correo electrónico no es válido',
            'email.email' => 'El formato del correo electrónico no es válido',
            'email.max' => 'El correo electrónico no debe exceder 255 caracteres',
            'email.unique' => 'El correo electrónico ya está registrado',
            'club.required' => 'El club es obligatorio',
            'club.string' => 'El club no es válido',
            'parche.required' => 'El parche es obligatorio',
            'parche.string' => 'El parche no es válido',
            'parche.min' => 'El parche debe tener al menos 2 caracteres',
            'parche.max' => 'El parche no debe exceder 6 caracteres',
            'parche.regex' => 'El parche solo puede contener letras, números, y los caracteres []_-@'
        ];

        try {

            $user = User::findOrFail(Auth::user()->id);

            $nameChanged = $request->has('name') && $user->name !== $request->name;
            $emailChanged = $request->has('email') && $user->email !== $request->email;
            $clubChanged = $request->has('club') && $user->club !== $request->club;
            $parcheChanged = $request->has('parche') && $user->parche !== $request->parche;

            if (!$nameChanged && !$emailChanged && !$clubChanged && !$parcheChanged) {
                $userData = [
                    'id' => Crypt::encryptString(auth::user()->id),
                    'name' => auth::user()->name,
                    'role' => auth::user()->role,
                    'email' => auth::user()->email,
                    'parche' => auth::user()->parche,
                    'club' => auth::user()->club
                ];
                return response()->json(['user_session' => $userData, 'error' => 'No se han encontrado cambios para actualizar'], Response::HTTP_NOT_ACCEPTABLE);
            }

            // Validar y actualizar el nombre de usuario
            if ($nameChanged) {
                $rules = [
                    'name' => [
                        'required',
                        'string',
                        'min:4',
                        'max:30',
                        'regex:/^[\w@\-()\[\]]+$/'
                    ]
                ];

                // Agregar regla de unicidad solo si el nombre ya existe
                if ($user->name !== $request->name) {
                    $rules['name'][] = 'unique:users';
                }

                Validator::make($request->all(), $rules, $errorMessages)->validate();

                $user->name = $request->name;
                $user->name_modifyed_at = now();
                $user->save();
            }

            // Validar y actualizar el correo electrónico
            if ($emailChanged) {
                Validator::make($request->all(), [
                    'email' => 'required|string|email|max:255|unique:users'
                ], $errorMessages)->validate();

                $user->email = $request->email;
                $user->save();
            }

            // Validar y actualizar el club
            if ($clubChanged) {
                Validator::make($request->all(), [
                    'club' => 'required|string'
                ], $errorMessages)->validate();

                $user->club = $request->club;
                $user->save();
            }

            // Validar y actualizar el parche
            if ($parcheChanged) {
                Validator::make($request->all(), [
                    'parche' => 'required|string|min:2|max:6|regex:/^[\w@\-()\[\]]+$/'
                ], $errorMessages)->validate();

                $user->parche = $request->parche;
                $user->save();
            }

            return response()->json(['user_session' => $user, 'message' => 'Se ha actualizado tu perfil'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
