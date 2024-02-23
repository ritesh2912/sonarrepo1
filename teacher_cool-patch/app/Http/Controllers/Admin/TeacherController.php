<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\TeacherStatus;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserDetails;
use App\Models\TeacherSetting;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        try{
            $keyword = $request->keyword;
            $sort = $request->sort;
            $teacher_status = $request->teacher_status;
            $page_size = ($request->page_size)? $request->page_size : 10;

            
            $data = DB::table('users')
                ->leftJoin('teacher_settings', 'users.id', '=', 'teacher_settings.user_id')
                ->leftJoin('subjects', 'subjects.id', '=', 'teacher_settings.subject_id')
                ->select('users.*','teacher_settings.id_proof','teacher_settings.document_path','teacher_settings.expected_income',
                'teacher_settings.preferred_currency','subjects.subject_name as subject','teacher_settings.category')
                ->where('users.user_type', User::TEACHER_TYPE)
                ->where('users.email_verified_at','!=', null)
                ->where(function($query) {
                    $query->where('users.teacher_status', User::TEACHER_STATUS_PENDING)
                            ->orWhere('users.teacher_status', User::TEACHER_STATUS_RESUBMIT);
                });

            if($keyword && $keyword != ''){
                $data = $data->where(function($query) use ($keyword){
                            $query->where('users.name', 'like', '%'.$keyword.'%')
                            ->orWhere('users.email', 'like', '%'.$keyword.'%');
                        });
            }
            if($teacher_status){
                $data = $data->where('users.teacher_status', $teacher_status);
            }

            if($sort == 'asc'){
                $data = $data->orderBy('users.updated_at');
            }else{
                $data = $data->orderByDesc('users.updated_at');
            }
            $data = $data->paginate($page_size);

            $response = [
                'success' => true,
                'data'    => $data,
                'teacher_request_status' => User::teacherRequestStatus(),
                'message' => 'Success',
            ];
        
            return response()->json($response, 200);
            
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function changeStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'status' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            if($request->status != User::TEACHER_STATUS_DISAPPROVED && $request->status != User::TEACHER_STATUS_APPROVED){
                return sendError("Invalid Status");
            }

            $user = User::find($request->id);
            if(!$user){
                return sendError("Invalid Record");
            }
            $old_status = $user->teacher_status;
          
            $user->teacher_status = $request->status;
            
            if($request->status == User::TEACHER_STATUS_APPROVED){
                $message = "Your Request as Teacher has been approved. Please login with your credentials.";
                if($old_status == User::TEACHER_STATUS_RESUBMIT){

                    $teacherSetting = TeacherSetting::where('user_id',$user->id)
                                        ->first();
                                        
                    $resubmitData =  json_decode($teacherSetting->resubmit_data);

                    // Save Users Table Data
                    if(isset($resubmitData->first_name) && $resubmitData->first_name){
                        $user->name = $resubmitData->first_name;
                    }
                    if(isset($resubmitData->last_name) && $resubmitData->last_name){
                        $user->last_name = $resubmitData->last_name;
                    }
                    $userDetailData = [];
                    // Save User Details Table Data
                    if(isset($resubmitData->contact) && $resubmitData->contact){
                        $userDetailData['contact'] = $resubmitData->contact;
                    }
                    if(isset($resubmitData->phone_code) && $resubmitData->phone_code){
                        $userDetailData['phone_code'] = $resubmitData->phone_code;
                    }
                    if(isset($resubmitData->country) && $resubmitData->country){
                        $userDetailData['country'] = $resubmitData->country;
                    }
                    if(isset($resubmitData->qualification) && $resubmitData->qualification){
                        $userDetailData['qualification'] = $resubmitData->qualification;
                    }
                    

                    $result = UserDetails::where('user_id',$user->id)
                            ->update($userDetailData);

                    // Save Teacher Settings Data
                    $teacherSettingData = [];
                    if(isset($resubmitData->working_hours) && $resubmitData->working_hours){
                        $teacherSettingData['working_hours'] = $resubmitData->working_hours;
                    }
                    if(isset($resubmitData->expected_income) && $resubmitData->expected_income){
                        $teacherSettingData['expected_income'] = $resubmitData->expected_income;
                    }
                    if(isset($resubmitData->preferred_currency) && $resubmitData->preferred_currency){
                        $teacherSettingData['preferred_currency'] = $resubmitData->preferred_currency;
                    }
                    if(isset($resubmitData->subject_id) && $resubmitData->subject_id){
                        $teacherSettingData['subject_id'] = $resubmitData->subject_id;
                    }
                    if(isset($resubmitData->category) && $resubmitData->category){
                        $teacherSettingData['category'] = $resubmitData->category;
                    }
                    if(isset($resubmitData->experience) && $resubmitData->experience){
                        $teacherSettingData['experience'] = $resubmitData->experience;
                    }
                    if(isset($resubmitData->id_proof) && $resubmitData->id_proof){
                        $teacherSettingData['id_proof'] = $resubmitData->id_proof;
                        // if (Storage::exists('app/public'.$teacherSetting->id_proof)) {
                        //     Storage::delete('app/public'.$teacherSetting->id_proof);
                        // }
                    }
                    if(isset($resubmitData->document_path) && $resubmitData->document_path){
                        $teacherSettingData['document_path'] = $resubmitData->document_path;
                        // if (Storage::exists('app/public'.$teacherSetting->document_path)) {
                        //     Storage::delete('app/public'.$teacherSetting->document_path);
                        // }
                    }
                    
                    $teacherSettingData['resubmit_data'] = null;

                    $teacherSetting = TeacherSetting::where('user_id',$user->id)
                                        ->update($teacherSettingData);
                    
                    $message = "Your Re-submit Profile Request has been approved. Please login with your credentials.";

                
                }
                // $data->user_type = User::TEACHER_TYPE;
                $emailData=[
                    'to'=>$user->email,
                    'receiver_name'=>$user->name,
                    'login_url'=> env('APP_URL_FRONT').'/login',
                    'body' => $message,
                    'subject' => "Regarding Approval"
                ];
                
            }else{

                if($old_status == User::TEACHER_STATUS_RESUBMIT){
                    $user->teacher_status = User::TEACHER_RESUBMIT_REJECT;
                    
                    $teacherSettingData['resubmit_data'] = null;

                    $teacherSetting = TeacherSetting::where('user_id',$user->id)
                                        ->update($teacherSettingData);
                }
                $emailData=[
                    'to'=>$user->email,
                    'receiver_name'=>$user->name,
                    'login_url'=> false,
                    'body' =>"Unfortunately your profile request has been disapproved." ,
                    'subject' => "Regarding Disapproval"
                ];
            }
            $user->save();

            //Send email Notification Pending
            dispatch(new TeacherStatus($emailData))->afterResponse();

            return sendResponse($user, 'Updated Successfully');
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }
}
