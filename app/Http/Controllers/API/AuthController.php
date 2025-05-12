<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register( Request $request){
        // validate the request
        $field = $request -> validate([
            'name' =>  'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $field['name'],
            'email' => $field['email'],
            'password' => bcrypt($field['password']),
        ]);

        $token = $user -> createToken('absensi-token') -> plainTextToken;

        return response() -> json([
            'user' => $user,
            'token' => $token,
        ], 201);

    }

    public function login(Request $request)
    {
        // validate the request
        $field = $request -> validate([
            'email' => 'required|string|max:255',
            'password' => 'required|string|min:8',
        ]);

        $user = User::where('email', $field['email']) -> first();

        // check if user exists
        if (!$user || !Hash::check($field['password'], $user -> password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.']
            ]);
        }

        $token = $user -> createToken('absensi-token') -> plainTextToken;

        return response() ->  json([
            'user' => $user,
            'token' => $token,
            'message' => 'Berhasil login'
        ]);

    }

    public function logout(Request $request)
    {
        $request -> user() -> tokens() -> delete();
        return response() -> json([
            'message' => 'Berhasil logout'
        ]);

    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}