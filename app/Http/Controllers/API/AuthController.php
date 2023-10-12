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
    function registerUser(Request $request){
        $user = new User();
        $rules = [
            'name'=>'required',
            'username'=>'required',
            'email'=>'required|email|unique:users,email',
            'password'=>'required',
        ];

        $validator = Validator::make($request->all(),$rules);
        if($validator->fails()){
            return response()->json([
                'status'=>false,
                'message'=>'proses validasi gagal',
                'data'=>$validator->errors()
            ],401);
        }
        $user->nip = $request->nip;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'status'=>true,
            'message'=>'sukses validasi'
        ],200);
    }

    function loginUser(Request $request){
        $rules = [
            'username'=>'required',
            'password'=>'required',
        ];

        $validator = Validator::make($request->all(),$rules);
        if($validator->fails()){
            return response()->json([
                'status'=>false,
                'message'=>'proses login gagal',
                'data'=>$validator->errors()
            ],401);
        }

        if (!Auth::attempt($request->only(['username','password']))) {
            return response()->json([
                'status'=>false,
                'message'=>'username atau password tidak sesuai'
            ],401);
        }
        $user = User::where('username', $request->username)->first();
        // $userDetail = Pegawai::where('pegawai_id',$user->pegawai_id)->first();
        $auth = Auth::user();
        // $data['nama'] = $auth->name;
        // $data['pegawai_id'] = $auth->pegawai_id;
        // $data['nip'] = $auth->nip;
        // $data['email'] = $auth->email;
        return response()->json([
            'status'=>true,
            'message'=>'berhasil login',
            'token'=>$user->createToken('api-user')->plainTextToken,
            'data'=>$auth,
            // 'data' =>$userDetail
        ],200);
    }

    function logout(){
        Auth::logout();
        return response()->json([
            'status'=>true,
            'message'=>'berhasil logout'
        ],200);
    }
}
