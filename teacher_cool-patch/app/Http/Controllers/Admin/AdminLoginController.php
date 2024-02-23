<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Admin;
use Exception;
class AdminLoginController extends Controller
{
    public function login(Request $request)
    { 
        try {
            $validator = Validator::make($request->all(), [
                'email'    => 'required|email',
                'password' => 'required'
            ]);
            if ($validator->fails()) return response()->json( ['error' => $validator->errors(), 'code'=>'401']); 
            
            $credentials = $request->only('email', 'password');
            if (Auth::guard('admin')->attempt($credentials)) {
                $user = Auth::guard('admin')->user();
                
                if($user->is_active){
                    $success['name']  = $user->name;
                    if($user->role == 0){
                        $success['type'] = 'super-admin';
                        $success['token'] = $user->createToken('accessToken', ['admin'])->accessToken;
                    }else{
                        $success['type'] = 'sub-admin';
                        $success['token'] = $user->createToken('accessToken', ['sub-admin'])->accessToken;
                    }
    
                    return response()->json(['status' => 'success', 'code' => '200', 'data' => $success]);
                }
                return sendError('Account not Activated');
                
            } else {
                return response()->json(['status' => 'error', 'code' => '404', 'msg' => ' Invalid credential']);
            }
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '401', 'msg' => $e->getmessage()]);
        }
    }

    
}
