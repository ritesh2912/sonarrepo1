<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\User;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\Assignment;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Jobs\WelcomeSubAdmin;

use Laravel\Passport\TokenRepository;
use Laravel\Passport\RefreshTokenRepository;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        try{
            // $assignments = DB::table('assignments');
            $totalAssignment =  DB::table('assignments')->get()->count();
            $assignmentAnswered = DB::table('assignments')
                                    ->where('assignment_status', Assignment::ASSIGNMENT_STATUS_SUBMITTED)
                                    ->get()->count();
            $assignmentApproved = DB::table('assignments')
                                    ->where('assignment_status', Assignment::ASSIGNMENT_STATUS_APPROVED)
                                    ->get()->count();
            $assignmentPending = DB::table('assignments')
                                    ->where('assignment_status', Assignment::ASSIGNMENT_STATUS_PENDING)
                                    ->get()->count();
            $assignmentPendingResubmit = DB::table('assignments')
                                    ->where('assignment_status', Assignment::ASSIGNMENT_STATUS_RESUBMIT_REQUEST)
                                    ->get()->count();
            $assignmentResubmitAnswer = DB::table('assignments')
                                    ->where('assignment_status', Assignment::ASSIGNMENT_STATUS_RESUBMIT_ANSWER)
                                    ->get()->count();
            $assignmentRejected = DB::table('assignments')
                                    ->where('assignment_status', Assignment::ASSIGNMENT_STATUS_REJECTED)
                                    ->get()->count();

            $data['teachers'] = User::where('user_type', User::TEACHER_TYPE)->get()->count();
            $data['students'] = User::where('user_type', User::STUDENT_TYPE)->get()->count();
            $data['orders'] = Order::get()->count();
            $data['earning'] = 0; // Code Pending
            $data['total_assignments'] =  $totalAssignment;
            $data['assignment_answered'] =  $assignmentAnswered;
            $data['assignment_approved'] =  $assignmentApproved;
            $data['assignment_pending'] =  $assignmentPending;
            $data['assignment_rejected'] =  $assignmentRejected;
            $data['assignment_pending_resubmit'] =  $assignmentPendingResubmit;
            $data['assignment_resubmit_answer'] =  $assignmentResubmitAnswer;

            return sendResponse($data);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
        
    }

    public function profile()
    {
        try{
            $user = Auth::user();

            return sendResponse($user);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function getUsers(Request $request)
    {
        try{

            $validator = Validator::make($request->all(), [
                'user_type' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $keyword = $request->keyword;
            $user_type = $request->user_type;
            $gender = $request->gender;
            $age = $request->age;
            $teacher_status = $request->teacher_status;
            $is_subscribe = $request->is_subscribe;
            $sort = $request->sort;
            $page_size = ($request->page_size)? $request->page_size : 10;
            
            $data = DB::table('users')
                ->join('user_details', 'users.id', '=', 'user_details.user_id')
                ->where('users.user_type', $user_type)
                ->where('users.email_verified_at','!=', null);
            if($user_type == User::TEACHER_TYPE){
                $data = $data->leftJoin('teacher_settings', 'users.id', '=', 'teacher_settings.user_id')
                            ->leftJoin('subjects', 'subjects.id', '=', 'teacher_settings.subject_id')
                            ->select('users.*', 'user_details.gender','user_details.age','user_details.contact','user_details.city','user_details.state','user_details.country','user_details.university','user_details.qualification','subjects.subject_name as subject','teacher_settings.category');
            }elseif($user_type == User::STUDENT_TYPE){
                $data = $data->leftJoin('subscribed_user_details', 'users.id', '=', 'subscribed_user_details.user_id')
                        ->select('users.*', 'user_details.gender','user_details.age','user_details.contact','user_details.city','user_details.state','user_details.country','user_details.university','user_details.qualification','subscribed_user_details.subscription_name','subscribed_user_details.subscription_expire_date');
                // $data = $data->select('users.*', 'user_details.gender','user_details.age','user_details.contact','user_details.city','user_details.state','user_details.country','user_details.university');
            }

            if($keyword && $keyword != ''){
                $data = $data->where(function($query) use ($keyword){
                            $query->where('users.name', 'like', '%'.$keyword.'%')
                            ->orWhere('users.email', 'like', '%'.$keyword.'%')
                            ->orWhere('user_details.contact', 'like', '%'.$keyword.'%');
                        });
            }
            
            if($teacher_status == User::TEACHER_STATUS_PENDING || $teacher_status == User::TEACHER_STATUS_APPROVED || $teacher_status == User::TEACHER_STATUS_DISAPPROVED || $teacher_status == User::TEACHER_STATUS_RESUBMIT|| $teacher_status == User::TEACHER_RESUBMIT_REJECT){
                $data = $data->where('users.teacher_status', $teacher_status);
            }

            if($is_subscribe){
                $data = $data->where('users.is_subscribe', $is_subscribe);
            }

            if($gender){
                $data = $data->where('user_details.gender', $gender);
            }
            
            if($age){
                $data = $data->where('user_details.age','<=', $age);
            }
            
            if($sort == 'asc'){
                $data = $data->orderBy('users.created_at');
            }else{
                $data = $data->orderByDesc('users.created_at');
            }
            $data = $data->paginate($page_size);
            
            $response = [
                'success' => true,
                'data'    => $data,
                'message' => 'Success',
                'teacher_request' => User::allTeacherStatus(),
                'subscription_status' => Subscription::subscriptionStatus(),
            ];

            return response()->json($response, 200);

        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }

    }

    public function userDetails($id)
    {
        try{
            $data['user'] = DB::table('users')
                ->join('user_details', 'users.id', '=', 'user_details.user_id')
                // ->leftJoin('subscribed_users', 'users.id', '=', 'subscribed_users.user_id')
                ->leftJoin('teacher_settings', 'users.id', '=', 'teacher_settings.user_id')
                ->leftJoin('subjects', 'teacher_settings.subject_id', '=', 'subjects.id')
                ->select('users.*','user_details.gender','user_details.age', 'user_details.city','user_details.state','user_details.country','user_details.university','user_details.phone_code','user_details.contact','user_details.qualification','user_details.university','teacher_settings.document_path','teacher_settings.working_hours','teacher_settings.id_proof','teacher_settings.expected_income','teacher_settings.preferred_currency','teacher_settings.category','teacher_settings.experience','teacher_settings.experience_letter','teacher_settings.resubmit_data','subjects.subject_name')
                ->where('users.id', $id)
                ->get();
            $data['subjects'] = Subject::select('id', 'subject_name')->get();
            $data['profile_status'] = User::allTeacherStatus();
            return sendResponse($data);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    // public function deleteUsers(Request $request)
    // {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'id' => 'required',
    //         ]);
    //         if ($validator->fails()) {
    //             return response()->json(['code' => '302', 'error' => $validator->errors()]);
    //         }
            
    //         User::destroy($request->id);
    //         DB::table('user_details')->where('user_id',$request->id)->delete();

    //         return sendResponse("User Deleted successfully.");

    //     } catch(\Exception $e) {
    //         return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
    //     }
    // }

    public function usersStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'user_status' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }
            if($request->user_status != User::IS_ACTIVE && $request->user_status != User::NOT_ACTIVE){
                return sendError('Invalid Status Request');
            }
            
            $user = User::find($request->user_id);
            
            if(!$user){
                return sendError('Invalid User Request');
            }
            $user->is_active = $request->user_status;
            $user->save();

            if($request->user_status == User::NOT_ACTIVE){

                $tokenData = DB::table('oauth_access_tokens');
                if($user->user_type == User::TEACHER_TYPE){
                    $tokenData = $tokenData->where('scopes', '["teacher"]');
                }else{
                    $tokenData = $tokenData->where('scopes', '["user"]');
                }
                $tokenData = $tokenData->delete();
                
            }

            return sendResponse("User Status Updated Successfully.");

        } catch(\Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function addSubAdmin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:admins',
                'name' => 'required|min:3',
                'contact' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }
            
            $password = getString(6);

            $user = new Admin;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->contact = $request->contact;
            $user->address = ($request->address)? $request->address:"";
            $user->role = Admin::SUB_ADMIN;
            $user->password = Hash::make($password);
            $user->email_verified_at = date('Y-m-d H:i:s');
            $user->is_active = Admin::IS_ACTIVE;
            $user->save();
            
            $data = [
                'to' =>  $request->email,
                'name' => $request->name,
                'email' => $request->email,
                'password' => $password,
                'subject' => "Account Created"
            ];
            
            dispatch(new WelcomeSubAdmin($data))->afterResponse();
            return response()->json(['status' => 'Success', 'code' => 200, 'user' => $user]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function editSubAdmin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'name' => 'required|min:3',
                'contact' => 'required',
                'is_active' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $data = Admin::find($request->id);
            // dd($request->all());
            $data->name = $request->name;
            $data->contact = $request->contact;
            $data->is_active = $request->is_active;
            if($request->address){
                $data->address = $request->address;
            }
            $data->save();

            return sendResponse($data, 'Updated Successfully');
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function getSubAdmin(Request $request){
        try{
            $keyword = $request->keyword;
            $id = $request->id;
            $sort = $request->sort;
            $page_size = ($request->page_size)? $request->page_size : 10;

            if($id){
                $data = Admin::where('id', $id)
                        ->whereNot('role','=',0)->first();
            }else{
                $data = Admin::whereNot('role','=',0);
                if($keyword && $keyword != ''){
                    $data = $data->where('name', 'like', '%'.$keyword.'%')
                                ->orWhere('email', 'like', '%'.$keyword.'%');
                }

                if($sort == 'asc'){
                    $data = $data->orderBy('created_at');
                }else{
                    $data = $data->orderByDesc('created_at');
                }
                
                $data = $data->paginate($page_size);
            }
    
            return sendResponse($data);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }

    }

    // public function deleteSubAdmin(Request $request)
    // {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'id' => 'required',
    //         ]);
    //         if ($validator->fails()) {
    //             return response()->json(['code' => '302', 'error' => $validator->errors()]);
    //         }
    //         if($request->id == 1){
    //             return sendError('Cannot Perform the Action');
    //         }

    //         Admin::destroy($request->id);
            
    //         return sendResponse("Sub-Admin Deleted successfully.");

    //     } catch(\Exception $e) {
    //         return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
    //     }
    // }

    public function editProfile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|min:3',
                'contact' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $user = Auth::user();

            $data = Admin::find($user->id);

            if ($request->file('profile_path')) {
                // $name = $request->file('profile_path')->getClientOriginalName();
                $extension = $request->file('profile_path')->getClientOriginalExtension();
                // $originalfileName = $request->file('profile_path')->getClientOriginalName();
                // $originalfileName = pathinfo($originalfileName, PATHINFO_FILENAME);
                // $originalfileName = implode('-',explode(' ', $originalfileName));
                $fileName = time().'.'.$extension;
                $profile_path = $request->file('profile_path')->storeAs('profile',$fileName,'public');

                $data->profile = $profile_path;
            }

            $data->name = $request->name;
            $data->contact = $request->contact;
            if($request->gender){
                $data->gender = $request->gender;
            }
            if($request->address){
                $data->address = $request->address;
            }
            $data->save();

            return sendResponse($data, 'Updated Successfully');
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }

    }

    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required',
                'new_password' => 'required|string|min:8|confirmed',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            if (!(Hash::check($request->get('current_password'), Auth::user()->password))) {
                // The passwords matches
                return sendError("Your current password is Incorrect.", [], 400);
            }

            if(strcmp($request->get('current_password'), $request->get('new_password')) == 0){
                // Current password and new password same
                return sendError("New password cannot be same as your current password.", [], 400);
            }
            
            $user = Auth::user();

            $data = Admin::find($user->id);
            
            $data->password = Hash::make($request->new_password);
            $data->save();

            return sendResponse($data, 'Password updated successfully');
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }

    }
}
