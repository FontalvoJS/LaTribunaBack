<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

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

            // Validar y actualizar el nombre de usuario
            if ($request->has('name')) {
                $rules = [
                    'name' => [
                        'required',
                        'string',
                        'min:4',
                        'max:30',
                        'regex:/^[\w@\-()\[\]]+$/'
                    ]
                ];

                // Si el nombre actual es diferente al nuevo nombre
                if ($user->name !== $request->name) {
                    // Agregar regla de unicidad solo si el nombre ya existe
                    $rules['name'][] = 'unique:users';
                }

                // Validar el nombre
                $validator = Validator::make($request->all(), $rules, $errorMessages);

                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors()->first()], Response::HTTP_BAD_REQUEST);
                }

                $user->name = $request->name;
                $user->name_modifyed_at = now();
                $user->save();
            }


            // Validar y actualizar el correo electrónico
            if ($request->has('email') && $user->email !== $request->email) {
                Validator::make($request->all(), [
                    'email' => 'required|string|email|max:255|unique:users'
                ], $errorMessages)->validate();

                $user->email = $request->email;
                $user->save();
            }

            // Validar y actualizar el club
            if ($request->has('club') && $user->club !== $request->club) {
                Validator::make($request->all(), [
                    'club' => 'required|string'
                ], $errorMessages)->validate();

                $user->club = $request->club;
                $user->save();
            }

            // Validar y actualizar el parche
            if ($request->has('parche') && $user->parche !== $request->parche) {
                Validator::make($request->all(), [
                    'parche' => 'required|string|min:2|max:6|regex:/^[\w@\-()\[\]]+$/'
                ], $errorMessages)->validate();

                $user->parche = $request->parche;
                $user->save();
            }

            return response()->json(['user' => $user, 'message' => 'Se ha actualizado tu perfil'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
