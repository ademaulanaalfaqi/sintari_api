<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function registerUser(Request $request) {
        $rules = [
            'name' => 'required',
            'username' => 'required',
            'email' => 'required|email',
            'password' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'registrasi gagal',
                'data' => $validator->errors()
            ], 401);
        }

        $input = $request->all();
        $user = User::create($input);

        $success['token'] = $user->createToken('auth_token')->plainTextToken;
        $success['name'] = $user->name;

        return response()->json([
            'status' => true,
            'message' => 'registrasi berhasil',
            'data' => $success
        ], 200);
    }


    public function loginUser(Request $request)
    {
        $rules = [
            'username' => 'required',
            'password' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'login gagal',
                'data' => $validator->errors()
            ], 401);
        }

        if (!Auth::attempt($request->only(['username', 'password']))) {
            return response()->json([
                'status' => false,
                'message' => 'username dan password yang dimasukkan tidak sesuai'
            ], 401);
        }

        $datauser = User::where('username', $request->username)->first();
        return response()->json([
            'status' => true,
            'message' => 'login berhasil',
            'token' => $datauser->createToken('token_user')->plainTextToken
        ], 200);
    }
}
