<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ContactController extends Controller
{
    public function create(Request $request)
    {
        $error_messages = [
            'name.required' => 'El nombre es obligatorio',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser válido',
            'subject.required' => 'El asunto es obligatorio',
            'message.required' => 'El mensaje es obligatorio',
            'message.min' => 'El mensaje debe tener al menos 50 caracteres',
            'message.max' => 'El mensaje no debe exceder 1000 caracteres',
            'name.min' => 'El nombre debe tener al menos 10 caracteres',
            'name.max' => 'El nombre no debe exceder 60 caracteres',
            'email.min' => 'El email debe tener al menos 10 caracteres',
            'email.max' => 'El email no debe exceder 60 caracteres',
            'subject.min' => 'El asunto debe tener al menos 10 caracteres',
            'subject.max' => 'El asunto no debe exceder 200 caracteres',
        ];
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:60|min:10',
            'email' => 'required|email|max:60|min:10',
            'message' => 'required|max:1000|min:50',
            'subject' => 'required|max:200|min:10',
        ], $error_messages);
        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_BAD_REQUEST);
        }
        try {
            $contact = new Contact();
            $contact->name = $request->name;
            $contact->email = $request->email;
            $contact->subject = $request->subject;
            $contact->message = $request->message;
            $contact->save();
            return response()->json(['message' => 'Gracias por contactarnos, nos pondremos en contacto contigo de ser necesario.'], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al enviar el mensaje de contacto'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
