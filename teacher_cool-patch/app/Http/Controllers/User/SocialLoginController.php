<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\UserDetails;
use App\Models\TeacherSetting;
use Hash;
use Illuminate\Support\Facades\DB;
// use App\Models\Subject;
use App\Jobs\SendWelcomeEmail;

use App\Traits\InteractsWithFacebookGraphApi;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Socialite;
use Exception;
// use Session;
use App\Services\WhatsappService;

class SocialLoginController extends Controller
{
    private WhatsappService $whatsappService;
 
    public function __construct(WhatsappService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    use InteractsWithFacebookGraphApi;

    public $client_id = "1092342128245-pkcm74odif1n94k74cbouq5n5t9lkmul.apps.googleusercontent.com";
    public $client_secret = "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";

    public function decodeToken($token){
        $response = base64_decode(str_replace('_', '/', str_replace('-','+',explode('.', $token)[1]) ));
        return json_decode($response);
    }

    private function createUser($request, $data)
    {
        try{
            $verifyCode = getString(10);

            $user = new User;
            $user->name = $data['first_name'];
            $user->last_name = $data['last_name'];
            $user->email = $data['email'];
            $user->password = Hash::make('Mind@123');
            $user->user_type = User::STUDENT_TYPE;
            $user->is_active = User::IS_ACTIVE;
            $user->social_type = $request->social_type;
            $user->facebook_id = $request->facebook_id;
            $user->email_verify_code = $verifyCode;
            if( $request->social_type == 'linkedin'){
                $user->linkedinId = $data['linkedinId'];
            }
            if($request->social_type == "google"){
                $user->email_verified_at = date('Y-m-d H:i:s');
            }
            if($request->user_type == User::TEACHER_TYPE){
                $user->teacher_status = User::TEACHER_STATUS_PENDING;
                $user->user_type =User::TEACHER_TYPE;
                $user->teacher_id_number  ='TCH-'.time();
            }
            $user->save();
            $userDetails = new UserDetails;
            $userDetails->user_id = $user->id;
            $userDetails->contact = $data['contact'] ?? null;
            $saveUser = $userDetails->save();

            $smsUserData['body'] = 'Dear Admin, A new user with email: '.$data['email'].' is registered successfully with Teacher Cool.';
            
            $saveTeacher = null;
            if($request->user_type == User::TEACHER_TYPE){
                
                $teacherSetting = new TeacherSetting;
                $teacherSetting->user_id = $user->id;
                $saveTeacher = $teacherSetting->save();

                $smsUserData['body'] = 'Dear Admin, A new teacher with email: '.$data['email'].' is registered successfully with Teacher Cool.';     
            }
            $url = env('APP_URL_FRONT').'/verify-email/' . $verifyCode;

            if($request->social_type == "facebook"){
                $welcomedata=[
                    'to'=> $request->email,
                    'receiver_name'=> $data['first_name']." ".$data['last_name'],
                    'url'=> $url,
                    'data' => 
                    $saveTeacher? "Your Request as Teacher is pending for confirmation. We will revert within 48 hrs.Please verify your email from the link below:  " 
                    :($saveUser? "Hope, You will have wonderful experience here.Please verify your email from the link below:":''),
                    'subject' => "Welcome To Teacher Cool"
                ];
                dispatch(new SendWelcomeEmail($welcomedata))->afterResponse();
            }
            $this->whatsappService->sendMessage($smsUserData);     
            return $user;

        }catch(\Exception $e){
            dd($e);
            return response()->json(['status' => 'error', 'code' => '500', 'msg' => $e]);
        }
        
    }

    private function loginUser($userData, $request)
    {
        try{
            // dd($userData->email);
            $credentials = [
                'email'=>$userData->email,
                'password'=>'Mind@123',
            ];
            if(Auth::attempt($credentials)){
                $user= Auth::user();
                if($user->is_active && $user->email_verified_at != null){
                    $success['user']  = $user;
                    if($user->user_type == User::TEACHER_TYPE && $request->user_type == User::STUDENT_TYPE){
                        return ["msg"=>'User is not a student',"code"=> 403];
                    }
                    if($user->user_type == User::STUDENT_TYPE && $request->user_type == User::TEACHER_TYPE){
                        return ["msg"=>'User is not a teacher',"code"=> 403];
                    }
                    if ($user->user_type == User::TEACHER_TYPE) {
                        if($user->teacher_status == User::TEACHER_STATUS_PENDING){
                            $data['profile_status']  = 'pending';
                            return ["msg"=>"Your Profile is in Review", "code"=> 200];
                        }elseif($user->teacher_status == User::TEACHER_STATUS_DISAPPROVED){
                            $data['profile_status']  = 'rejected';
                            return ["msg"=>'Unfortunately, Your Profile is Rejected', "code"=> 200];
                        }
                        
                        $success['user_type']  = 'teacher';
                        $success['token'] = $user->createToken('accessToken', ['teacher'])->accessToken;
                    }
                    else{
                        $success['user_type']  = 'student';
                        $success['token'] = $user->createToken('accessToken', ['user'])->accessToken;
                    }
                    return ["data"=> $success,"msg"=>'You are successfully logged in.', "code"=> 200];
                    
                    // return response()->json([$success, "msg"=> 'You are successfully logged in.']);
                }
                return ["msg"=>'Account not Activated',"code"=> 403];
            
            }        
            return ["msg"=>'Invalid Request',"code"=> 403];
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'code' => '401', 'msg' => $e]);
        }
    }

    public function socialLogin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'social_type' => 'required',
                'user_type' => 'required',
            ]);

            if ($validator->fails()) return sendError('Validation Error.', $validator->errors(), 422);
            
            // Check the social Type 
            if($request->social_type == "google"){
                if(!$request->token){
                    return sendError("Token is required");
                }
                $tokenResult = $this->decodeToken($request->token);

                $finduser = User::where('email', $tokenResult->email)->first();
                if($finduser){
                    $result = $this->loginUser($finduser, $request);
                }else{
                    $data['first_name'] = $tokenResult->given_name;
                    $data['last_name'] = $tokenResult->family_name;
                    $data['email'] = $tokenResult->email;

                    $finduser = $this->createUser($request, $data);

                    $result = $this->loginUser($finduser, $request);

                }
                
            }elseif($request->social_type == "facebook"){
                if(!$request->email|| !$request->facebook_id || !$request->name){
                    return sendError("Invalid Request");
                }

                $finduser = User::where('email', $request->email)->first();
                if($finduser){
                    $result = $this->loginUser($finduser, $request);
                }else{
                    $name = explode(" ", $request->name);
                    $data['first_name'] = $name[0];
                    $data['last_name'] = $name[1];
                    $data['email'] = $request->email;
                    $data['contact'] = $request->contact ?? null;
                    $this->createUser($request, $data);
                    
                    // $result = $this->loginUser($finduser, $request);
                    $result = ['msg' => 'Success', 'code' => 200];
                }

            }else if($request->user_type == "linkedin"){
                $finduser = User::where('linkedinId',  $request->linkedinId)->first();
                if($finduser){
                    $result = $this->loginUser($finduser, $request);
                }else{
                    $name = explode(" ", $request->name);
                    $data['first_name'] = $name[0];
                    $data['last_name'] = $name[1];
                    $data['email'] = $request->email;
                    $data['contact'] = $request->contact ?? null;
                    $data['linkedinId'] = $request->linkedinId;;
                    $this->createUser($request, $data);                    
                    $result = $this->loginUser($finduser, $request);
                    $result = ['msg' => 'Success', 'code' => 200];
                }
            }else{  
                return sendError('Invalid Request');
            }
            // Check the Response
            if($result["code"] != 200){
                return sendError($result["msg"], [], $result["code"]);
            }
            if(!isset($result["data"])){
                return sendResponse($result["msg"]);
            }
            
            return sendResponse($result["data"], $result["msg"]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'code' => '401', 'msg' => $e]);
        }
    }

    public function fbCheck(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'facebook_id' => 'required',
                // 'user_type' => 'required',
            ]);

            if ($validator->fails()) return sendError('Validation Error.', $validator->errors(), 422);
            

            $finduser = User::where('facebook_id', $request->facebook_id)
                            ->where('social_type', "facebook")
                            ->first();
            if($finduser){
                return sendResponse($finduser);
            }
            
            return sendError('User Not Found', 404);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'msg' => 'Something went wrong']);
        }
    }

    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
    }
}
