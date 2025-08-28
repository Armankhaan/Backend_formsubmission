<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request) {
        $data = $request->validate([
            'name' => ['nullable','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:6','confirmed'],
        ]);
        $user = User::create([
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_admin' => false, // seed one admin manually if needed
        ]);
        return response()->json(['message'=>'registered'], 201);
    }

    public function login(Request $request) {
        $cred = $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string'],
        ]);

        $user = User::where('email', $cred['email'])->first();
        if (!$user || !Hash::check($cred['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }

        // Revoke old tokens (optional)
        $user->tokens()->delete();

        $token = $user->createToken('web')->plainTextToken;
        return response()->json([
            'token' => $token,
            'user'  => ['id'=>$user->id, 'name'=>$user->name, 'email'=>$user->email, 'is_admin'=>$user->is_admin],
        ]);
    }
}

