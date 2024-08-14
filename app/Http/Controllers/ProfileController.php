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
            // 'email.required' => 'El correo electrónico es obligatorio',
            // 'email.string' => 'El correo electrónico no es válido',
            // 'email.email' => 'El formato del correo electrónico no es válido',
            // 'email.max' => 'El correo electrónico no debe exceder 255 caracteres',
            // 'email.unique' => 'El correo electrónico ya está registrado',
            'club.required' => 'El club es obligatorio',
            'club.string' => 'El club no es válido',
            'parche.required' => 'El parche es obligatorio',
            'parche.string' => 'El parche no es válido',
            'parche.min' => 'El parche debe tener al menos 2 caracteres',
            'parche.max' => 'El parche no debe exceder 6 caracteres',
            'parche.regex' => 'El parche solo puede contener letras, números, y los caracteres []_-@'
        ];

        try {
            // Decodificar el JSON del request
            $data = json_decode($request->getContent(), true);

            $user = User::findOrFail(Auth::user()->id);

            $nameChanged = isset($data['name']) && $user->name !== $data['name'];
            $clubChanged = isset($data['club']) && $user->club !== $data['club'];
            $parcheChanged = isset($data['parche']) && $user->parche !== $data['parche'];

            if (!$nameChanged && !$clubChanged && !$parcheChanged) {
                $userData = [
                    'id' => Crypt::encryptString(Auth::user()->id),
                    'name' => Auth::user()->name,
                    'role' => Auth::user()->role,
                    'email' => Auth::user()->email,
                    'parche' => Auth::user()->parche,
                    'club' => Auth::user()->club
                ];
                return response()->json(['user_session' => $userData, 'error' => 'No se han encontrado cambios para actualizar'], Response::HTTP_NOT_ACCEPTABLE);
            }

            $errorMessages = []; // Define tus mensajes de error personalizados aquí

            // Validar y actualizar el nombre de usuario
            if ($nameChanged) {
                $rules = [
                    'name' => [
                        'required',
                        'string',
                        'min:4',
                        'max:30',
                        'regex:/^[\w@\-()\[\]]+$/',
                        'unique:users,name,' . $user->id
                    ]
                ];

                Validator::make($data, $rules, $errorMessages)->validate();

                $user->name = $data['name'];
                $user->name_modifyed_at = now();
                $user->save();
            }

            // Validar y actualizar el club
            if ($clubChanged) {
                Validator::make($data, [
                    'club' => 'required|string'
                ], $errorMessages)->validate();

                $user->club = $data['club'];
                $user->save();
            }

            // Validar y actualizar el parche
            if ($parcheChanged) {
                Validator::make($data, [
                    'parche' => 'required|string|min:2|max:6|regex:/^[\w@\-()\[\]]+$/'
                ], $errorMessages)->validate();

                $user->parche = $data['parche'];
                $user->save();
            }
            $userData = [
                'id' => Crypt::encryptString(Auth::user()->id),
                'name' => $user->name,
                'role' => $user->role,
                'email' => $user->email,
                'parche' => $user->parche,
                'club' => $user->club
            ];
            return response()->json(['user_session' => $userData, 'message' => 'Se ha actualizado tu perfil'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
