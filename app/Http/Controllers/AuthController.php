<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $emailUser = '';
        $user = User::where(['email' => $request['email']])->first();
        if(!isset($user->email)){
            abort(401, 'Email não cadastrado.');
        }

        if(!Hash::check($request->password, $user->password)){
            abort(401, 'Senha Inválida');
        }

        return response()->json(['token' => $user->createToken($user->email)->plainTextToken]);
    }

    public function register(UserRequest $request, User $user)
    {
        $data = $request->validated();
        $data['password'] = bcrypt($data['password']);

        if(!$user = $user->create($data)){
            abort(500, 'Error create a new user...');
        }

        return response()->json(['user' => $user]);
    }

    public function me()
    {
        return response()->json(new UserResource(auth()->user()));
    }
}
