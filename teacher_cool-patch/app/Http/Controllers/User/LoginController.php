<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Jobs\AdminEmail;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Hash;
use App\Models\UserDetails;
use App\Jobs\SendWelcomeEmail;
use App\Jobs\TeacherStatus;
use App\Models\SMS;
use App\Models\TeacherSetting;
use App\Models\Subject;
use Exception;
use Twilio\Rest\Client;
use App\Models\Content;
use App\Helpers\Currency;
use App\Services\WhatsappService;

class LoginController extends Controller
{  
    private WhatsappService $whatsappService;
 
    public function __construct(WhatsappService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }
    public function registerInfo()
    {
        try{
            $data['all_subjects'] = Subject::
                                select('subject_name','id','category_id')
                                ->get();
            $data['subjects_by_category'] = $data['all_subjects']->groupBy(function($data) {
                                    return $data->category_id;
                                });
            $data['category_status'] = Content::getContentCategory();
            $data['currency'] = Currency::list();
            if(!$data){
                return sendError('No record Found');
            }
            return sendResponse($data);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'    => 'required|email',
                'password' => 'required',
                'user_type' => 'required'
            ]);

            if ($validator->fails()) return sendError('Validation Error.', $validator->errors(), 422);

            $credentials = $request->only('email', 'password');
            if (Auth::attempt($credentials)) {
                $user= Auth::user();
               
                if($user->is_active && $user->email_verified_at != null){
                    $success['user']  = $user;
                    if($user->user_type == User::TEACHER_TYPE && $request->user_type == User::STUDENT_TYPE){
                        return sendError('User is not a student',[], 403);
                    }
                    if($user->user_type == User::STUDENT_TYPE && $request->user_type == User::TEACHER_TYPE){
                        return sendError('User is not a teacher',[], 403);
                    }

                    if ($user->user_type == User::TEACHER_TYPE) {
                        if($user->teacher_status == User::TEACHER_STATUS_PENDING){
                            $data['profile_status']  = 'pending';
                            return sendResponse($data, 'Your Profile is in Review');
                        }elseif($user->teacher_status == User::TEACHER_STATUS_DISAPPROVED){
                            $data['profile_status']  = 'rejected';
                            return sendResponse($data,'Unfortunately, Your Profile is Rejected');
                        }
                        $success['user_type']  = 'teacher';
                        $success['token'] = $user->createToken('accessToken', ['teacher'])->accessToken;
                    }
                    else{
                        $success['user_type']  = 'student';
                        $success['token'] = $user->createToken('accessToken', ['user'])->accessToken;
                    }
                    return sendResponse($success, 'You are successfully logged in.');
                    // return response()->json([$success, "msg"=> 'You are successfully logged in.']);
                }
                return sendError('Account not activated',[], 403);
                
               
            }else {
                return response()->json( ['error' => 'invalid credentials', 'code'=>'401']);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'code' => '401', 'msg' => 'You  are not authorised']);
        }
    }

    public function register(Request $request)
    {
        try {
            
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users',
                'password' => 'required|min:4',
                'first_name' => 'required|min:3',
                'is_teacher_request' => 'required',
                'contact' => 'required',
                'currency' => 'required',
                // 'country' => 'required',
                // 'qualification' => 'required',
                // 'profile' => 'file|mimes:jpg,png,jpeg',
                // 'id_proof' => 'file|mimes:jpg,png,jpeg,pdf',
                // 'document_path' => 'file|mimes:jpg,png,jpeg,pdf',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $profile_path = '';
            if ($request->file('profile')) {
                // $name = $request->file('profile')->getClientOriginalName();
                $extension = $request->file('profile')->getClientOriginalExtension();
                $originalfileName = $request->file('profile')->getClientOriginalName();
                $originalfileName = pathinfo($originalfileName, PATHINFO_FILENAME);
                $originalfileName = implode('-',explode(' ', $originalfileName));
                $fileName = $originalfileName."-".time().'.'.$extension;
                $profile_path = $request->file('profile')->storeAs('profile',$fileName,'public');
            }

            $reffer_id = null;
            if($request->reffer_code){
                $refferUser = User::where('reffer_code','=',$request->reffer_code)->first();
                if($refferUser){
                    $reffer_id = $refferUser->id;
                }
            }

            $verifyCode = getString(10);
            
            /* Save User Data*/
            $user = new User;
            $user->name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->user_type = User::STUDENT_TYPE;
            $user->is_active = User::IS_ACTIVE;
            $user->profile_path = $profile_path;
            $user->reffer_user_id = $reffer_id;
            $user->email_verify_code = $verifyCode;
            if($request->is_teacher_request){
                $user->teacher_status = User::TEACHER_STATUS_PENDING;
                $user->requested_for_teacher = 1;
		        $user->user_type =User::TEACHER_TYPE;
                $user->teacher_id_number  ='TCH-'.time();
            }
            
            $user->save();

            $id_proof_path = '';
            if ($request->file('id_proof')) {
                // $name = $request->file('id_proof')->getClientOriginalName();
                $extension = $request->file('id_proof')->getClientOriginalExtension();
                $originalfileName = $request->file('id_proof')->getClientOriginalName();
                $originalfileName = pathinfo($originalfileName, PATHINFO_FILENAME);
                $originalfileName = implode('-',explode(' ', $originalfileName));
                $fileName = $originalfileName."-".time().'.'.$extension;
                $id_proof_path = $request->file('id_proof')->storeAs('teacher',$fileName,'public');
            }

            $document_path = '';
            if ($request->file('document_path')) {
                // $name = $request->file('document_path')->getClientOriginalName();
                $extension = $request->file('document_path')->getClientOriginalExtension();
                $originalfileName = $request->file('document_path')->getClientOriginalName();
                $originalfileName = pathinfo($originalfileName, PATHINFO_FILENAME);
                $originalfileName = implode('-',explode(' ', $originalfileName));
                $fileName = $originalfileName."-".time().'.'.$extension;
                $document_path = $request->file('document_path')->storeAs('teacher',$fileName,'public');
            }

            /* Save User Details*/
            $userDetails = new UserDetails;
            $userDetails->user_id = $user->id;
            $userDetails->phone_code = $request->phone_code;
            $userDetails->contact = $request->contact;
            $userDetails->city =  $request->city;
            $userDetails->state = $request->state;
            $userDetails->country = $request->country;
            $userDetails->currency = $request->currency;
            $userDetails->qualification = $request->qualification;
            $userDetails->university = $request->university; 
            $userDetails->gender = $request->gender;
            $userDetails->age = $request->age; 
            
            $saveUser= $userDetails->save();
            $saveTeacher = false;
            if($request->is_teacher_request){
                $teacherSetting = new TeacherSetting;
                $teacherSetting->user_id = $user->id;
                $teacherSetting->id_proof = $id_proof_path;
                $teacherSetting->document_path = $document_path; 
                $teacherSetting->working_hours = $request->working_hours;
                $teacherSetting->expected_income = $request->expected_income;
                $teacherSetting->subject_id = $request->subject;
                $teacherSetting->category = $request->category;
                $saveTeacher=$teacherSetting->save();
            }
            
            if($saveTeacher){
                
                $smsUserData['body'] = 'Dear Admin, A new teacher with email: '.$request->email.' is registered successfully with Teacher Cool.';
                $this->whatsappService->sendMessage($smsUserData);
            }elseif($saveUser){
                $smsUserData['body'] = 'Dear Admin, A new user with email: '.$request->email.' is registered successfully with Teacher Cool.';
                $this->whatsappService->sendMessage($smsUserData);
            }
            
            $url = env('APP_URL_FRONT').'/verify-email/' . $verifyCode;
            // $url = url('/verify-email/').'/'. $verifyCode;
            
            $welcomedata=[
                'to'=> $request->email,
                'receiver_name'=> $request->first_name." ".$request->last_name,
                'url'=> $url,
                'data' => 
                $saveTeacher? "Your Request as Teacher is pending for confirmation. We will revert within 48 hrs.Please verify your email from the link below:  " 
                :($saveUser? "Hope, You will have wonderful experience here.Please verify your email from the link below:":''),
                'subject' => "Welcome To Teacher Cool"
            ];
            dispatch(new SendWelcomeEmail($welcomedata))->afterResponse();
            return response()->json(['status' => 'Success', 'code' => 200, 'user' => $user]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function verifyEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email_token' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $data = User::where('email_verify_code', '=', $request->email_token)->first();
            if(!$data){
                return sendError('No record Found');
            }
            $is_seller = $data->user_type == User::SELLER_TYPE ? true : false; 
            $updateData = User::where('email_verify_code', '=', $request->email_token)
                    ->update(['email_verified_at' => date('Y-m-d H:i:s'), 'email_verify_code' => null]);
                    
            if($updateData && $data->requested_for_teacher){
                $teacherEmailData=[
                    'to'=>$data->email,
                    'receiver_name'=>$data->name." ".$data->last_name,
                    'body' =>"Your Request as Teacher has been Pending for approval. We will revert you within 24hrs." ,
                    'subject' => "Regarding Approval Request"
                ];
                dispatch(new TeacherStatus($teacherEmailData))->afterResponse();
                $adminEmailData=[
                    'to'=> env('ADMIN_EMAIL_ADDRESS'),
                    'name'=>'Teacher Cool',
                    'url' => env('APP_URL_FRONT').'/viewuser/' . $data->id,
                    'body' =>"New teacher, ".$data->name." ".$data->last_name." has been Register with ".$data->email." email." ,
                    'subject' => "Regarding Teacher Approval"
                ];
                dispatch(new AdminEmail($adminEmailData))->afterResponse();
            }
            if($updateData){
                // return view('verify-email',  ['isValid' => true]);
                return sendResponse(['is_seller' => $is_seller], 'Email Verified');
            }
            return sendError('Something went Wrong');
        } catch (Exception $e) {
            // return view('verify-email',  ['isValid' => false]);
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    
    // public function verifyEmail($code)
    // {
    //     try {
            
    //         $data = User::where('email_verify_code', '=', $code)->first();
            
    //         if(!$data){
    //             return view('verify-email', ['isValid' => false]);
    //         }
            
    //         $updateData = User::where('email_verify_code', '=', $code)
    //                 ->update(['email_verified_at' => date('Y-m-d H:i:s'), 'email_verify_code' => null]);
                    
    //         if($updateData && $data->requested_for_teacher){
    //             $teacherEmailData=[
    //                 'to'=>$data->email,
    //                 'receiver_name'=>$data->name,
    //                 'body' =>"Your Request as Teacher has been Pending for approval. We will revert you within 24hrs." ,
    //                 'subject' => "Regarding Approval Request"
    //             ];
    //             dispatch(new TeacherStatus($teacherEmailData))->afterResponse();
    //             $adminEmailData=[
    //                 'to'=> env('ADMIN_EMAIL_ADDRESS'),
    //                 'name'=>'Teacher Cool',
    //                 'body' =>"New teacher, ".$data->name." has been Register with ".$data->email." email." ,
    //                 'subject' => "Regarding Teacher Approval"
    //             ];
    //             dispatch(new AdminEmail($adminEmailData))->afterResponse();
    //         }
    //         if($updateData){
    //             return view('verify-email',  ['isValid' => true]);
    //         }
    //         return view('verify-email', ['isValid' => false]);
    //     } catch (Exception $e) {
    //         return view('verify-email',  ['isValid' => false]);
    //     }
    // }
}
