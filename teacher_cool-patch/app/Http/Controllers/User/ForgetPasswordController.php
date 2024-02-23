<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use Validator;
use App\Models\User;
use App\Models\PasswordReset;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Str;
use DB;
use App\Http\Controllers\Controller;
use App\Jobs\ResetPassword;

class ForgetPasswordController extends Controller
{
    public function forgetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email'
            ]);
            if ($validator->fails()) return sendError('Validation Error.', $validator->errors(), 422);

            $user = User::where('email', $request->email)->get();
            if (count($user) > 0) {
                $token = Str::random(40);

                $url = env('APP_URL_FRONT').'/reset-password/' . $token;
                $data = [
                    'url' => $url,
                    'name' => $user[0]['name'],
                    'to' => $request->email,
                    'subject' => "Reset password"
                ];
                dispatch(new ResetPassword($data))->afterResponse();

                $datetime = Carbon::now()->format('Y-m-d H:i:s');
                PasswordReset::updateOrCreate(
                    ['email' => $request->email],
                    [
                        'token' => $token,
                        'created_at' => $datetime
                    ]

                );
                return response()->json(['status' => 'success', 'code' => '200', 'msg' => 'Please check your mail to reset your password']);
            } else {
                return response()->json(['status' => 'error', 'code' => '400', 'data' => 'user not found']);
            }
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'message' => $e->getmessage()]);
        }
    }
    
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'password' => 'required|string|confirmed|min:8',
                'token' => 'required|string'
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $resetData = DB::table('password_resets')
                    ->select('email')
                    ->where('token', $request->token)
                    ->first();
            if(!$resetData){
                return response()->json(['code' => '302', 'error' => 'Token not found']);
            }
            $user = User::where('email',$resetData->email)->first();
            $user->password = Hash::make($request->password);
            $user->save();
            PasswordReset::where('email', $user->email)->delete();
            return response()->json(['status' => 'success', 'code' => '200', 'msg' => "Password updated"]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'message' => $e->getmessage()]);
        }
    }

    public function verifyResetPassToken(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string'
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $resetData = DB::table('password_resets')
                    ->select('email')
                    ->where('token', $request->token)
                    ->first();
            if(!$resetData){
                return sendError('Invalid Token');
            }
            
            return response()->json(['status' => 'success', 'code' => '200', 'msg' => "Token verified"]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'message' => $e->getmessage()]);
        }
    }
}
