<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Models\User;
use App\Models\SubscribedUserDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Notifications\EmailNotification;
use Category;
use Exception;
use App\Models\Subject;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Notification;
use App\Services\WhatsappService;

class UserAssignmentController extends Controller
{
    private WhatsappService $whatsappService;
 
    public function __construct(WhatsappService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }
    public function index(Request $request)
    {
        try{
            $keyword = $request->keyword;
            $assignment_status = $request->assignment_status;
            $sort = $request->sort;
            $page_size = ($request->page_size)? $request->page_size : 10;
            $category = $request->category;

            $start_date = ($request->start_date)? date("Y-m-d", strtotime($request->start_date)): null;
            $end_date = ($request->end_date)? date("Y-m-d", strtotime($request->end_date)): null;
            
            $user = Auth::user();
            
            $data = DB::table('assignments')
                ->leftJoin('users as teacher', 'teacher.id', '=', 'assignments.teacher_id')
                ->select('assignments.*', 'teacher.email as teacher_email','teacher.teacher_id_number as teacher_id_number','teacher.name as teacher_name')
                ->where('assignments.user_id','=', $user->id);

            if($keyword && $keyword != ''){
                $data = $data->where(function($query) use ($keyword){
                            $query->where('assignments.question', 'like', '%'.$keyword.'%')
                            ->orWhere('assignments.question_description', 'like', '%'.$keyword.'%');
                        });
            }
            if($assignment_status){
                $data = $data->where('assignments.assignment_status','=', $assignment_status);
            }

            if($category){
                $data = $data->where('assignments.category','=', $category);
            }

            if($start_date && $start_date != ''){
                $data = $data->where('assignments.due_date','>=', $start_date);
            }

            if($end_date && $end_date != ''){
                $data = $data->where('assignments.due_date','<=', $end_date);
            }


            if($sort == 'asc'){
                $data = $data->orderBy('assignments.created_at');
            }else{
                $data = $data->orderByDesc('assignments.created_at');
            }
            $data = $data->paginate($page_size);

            $planData = DB::table('subscribed_user_details')
                            ->join('subscription_plans', 'subscription_plans.id', '=','subscribed_user_details.subscription_plan_id')
                            ->join('subscriptions', 'subscriptions.id', '=','subscription_plans.subscription_id')
                            ->select('subscriptions.slug')
                            ->where('user_id', $user->id)->first();
            
            $can_resubmit = 0;
            if($planData && $planData->slug == "platinum"){
                $can_resubmit = 1;
            }
            
            $response = [
                'success' => true,
                'data'    => $data,
                'message' => 'Success',
                'all_assignment_status' => Assignment::assignmentStatus(),
                'category_status' => Category::getCetegoryForOuestions(),
                'can_resubmit' => $can_resubmit,
            ];
        
            return response()->json($response, 200);
            
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }


    public function assignment(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'category' => 'required',
                // 'subject_id' => 'required',
                'question' => 'required',
                'description' => 'required',
                'due_date' => 'required',
                'word_count' => 'required',
                'time_zone' => 'required',
                
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $dueDate = date("Y-m-d", strtotime($request->due_date)).' 23:59:59';

            $dueDateTime = convertToUTCtime($dueDate, $request->time_zone);
            // dd($dueDateTime);

            $user = Auth::user();

            $subscriptionData = SubscribedUserDetail::where("user_id",$user->id)->first();
            $proceed_to_payment = 0;
            $assignment_paid = 1;
            // if($subscriptionData == null){
            //     $proceed_to_payment = 1;
            //     $assignment_paid = 0;
            // }
            if($subscriptionData == null || $subscriptionData->assignment_request < 1 || $subscriptionData->subscription_expire_date < date("Y-m-d h:i:s")){
                $proceed_to_payment = 1;
                $assignment_paid = 0;
            }

            // Decrease Assignment Count
            if($subscriptionData != null){
                $settings = SystemSetting::select('word_per_assignment')->first();
                $max_word_count_limit = $settings->word_per_assignment;
                $remove_count = ceil($request->word_count/$max_word_count_limit);
                if($subscriptionData->assignment_request < $remove_count){
                    return sendError("Word count limit exceed");
                }
                $subscriptionData->assignment_request = $subscriptionData->assignment_request - $remove_count;
                $subscriptionData->save();
            }
            
            
            $assignment_path = null;
            if ($request->file('assignment_attachment')) {
                // $name = $request->file('assignment_attachment')->getClientOriginalName();
                $extension = $request->file('assignment_attachment')->getClientOriginalExtension();
                $originalfileName = $request->file('assignment_attachment')->getClientOriginalName();
                $originalfileName = pathinfo($originalfileName, PATHINFO_FILENAME);
                $originalfileName = implode('-',explode(' ', $originalfileName));
                $fileName = $originalfileName."-".time().'.'.$extension;
                $assignment_path = $request->file('assignment_attachment')->storeAs('assignment',$fileName,'public');
            }
            
            $data = new Assignment;
            $data->user_id = $user->id;
            $data->assignment_id = 'ASNG-'.time();
            $data->question = $request->question;
            $data->subject_id = $request->subject_id;
            $data->category = $request->category;
            $data->category_other = $request->category_other;
            $data->question = $request->question;
            $data->question_description = $request->description;
            $data->due_date = $dueDateTime;
            $data->assignment_status = Assignment::ASSIGNMENT_STATUS_PENDING;
            $data->is_paid_for_assignment = $assignment_paid;
            $data->question_assingment_path = $assignment_path;
            $data->word_count = $request->word_count;
            $data->save();

            

            // Send Notifications to Teachers
            $userData  = DB::table('users')
                        ->join('teacher_settings','teacher_settings.user_id','=','users.id')
                        ->where('users.is_active', 1)
                        ->where('users.email_verified_at', '!=', null)
                        ->where('users.user_type', User::TEACHER_TYPE)
                        ->select('users.*');
            if($request->category == Category::CONTENT_CATEGORY_IT || $request->category == Category::CONTENT_CATEGORY_IT_WITHOUT_CODING){
                $userData = $userData->where('teacher_settings.category', Category::CONTENT_CATEGORY_IT);
            }elseif($request->category == Category::CONTENT_CATEGORY_NON_IT){
                $userData = $userData->where('teacher_settings.category', Category::CONTENT_CATEGORY_NON_IT);
            }
            $userData = $userData->get();
            // dd($userData);
            // foreach($userData as $val){
                
            //     $teacher = User::find($val->id);
                $message = [
                    'title' => 'New Assignment Notification',
                    'message' => $request->question,
                    'url' => env('APP_URL_FRONT').'/teacher/manageorder',
                ];
            //     $teacher->notify(new EmailNotification($message));
            //     // print_r($teacher->email);
            //     // echo "<br>";
            // }
            //Whatsapp Notification   
            
            $smsUserData['body'] = "Dear Admin, a student have posted a assignment for category ".$request->category.".\nAnd the questions is: \n".$request->question; 
            $this->whatsappService->sendMessage($smsUserData);
            Notification::send($userData, new EmailNotification($message));


            $result = [
                'proceed_to_payment' => $proceed_to_payment,
                'data' => $data
            ];

            return sendResponse($result);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function singleAssignment($id)
    {
        try{
            if($id < 1 ){
                return sendError("Invalid Request");
            }

            $user = Auth::user();
            
            $data = DB::table('assignments')
                        ->leftJoin('users as teacher', 'teacher.id', '=', 'assignments.teacher_id')
                        ->where('assignments.id',$id)
                        ->where('assignments.user_id',$user->id)
                        ->select('assignments.*','teacher.name as teacher_name')
                        ->first();
                        
            if($data == null){
                return sendError("Data not found", [], 404);
            }

            $response = [
                'success' => true,
                'data'    => $data,
                'message' => 'Success',
                'all_assignment_status' => Assignment::assignmentStatus(),
                'category_status' => Category::getCetegoryForOuestions(),
            ];
            
            return sendResponse($response, "Successfully");
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }


    public function resubmitAssignment(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'assignment_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $user = Auth::user();
            $data = Assignment::where('id', $request->assignment_id)
                                ->where('user_id', $user->id)
                                ->where('resubmit_request', '<', 3)
                                ->where('assignment_status', '!=', Assignment::ASSIGNMENT_STATUS_PENDING)
                                ->where('assignment_status', '!=', Assignment::ASSIGNMENT_STATUS_RESUBMIT_REQUEST)
                                ->first();
                               
            if(!$data){
                return sendError("Invalid Request");
            }

            if($data->resubmit_request + 1 > 3){
                return sendError("Re-submit quota full");
            }

            $assignmentData = Assignment::where('id', $request->assignment_id)
                                ->where('user_id', $user->id)
                                ->where('assignment_status', '!=', Assignment::ASSIGNMENT_STATUS_PENDING);
            
            $updateData['assignment_status'] = Assignment::ASSIGNMENT_STATUS_RESUBMIT_REQUEST;
            $updateData['resubmit_request'] = $data->resubmit_request + 1;
            $assignmentData->update($updateData);

            return sendResponse("Re-submitted request successfully");

        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function checkSubscription(Request $request)
    {
        try{
            $user = Auth::user();

            $subscriptionData = SubscribedUserDetail::select('*')
                                ->where("user_id",$user->id)->first();
            
            // $data['has_subscription'] = false;
            // $data['need_new_plan'] = false;
            // if($subscriptionData){
            //     $data['has_subscription'] = true;
            //     if($subscriptionData->subscription_expire_date < date("Y-m-d") || $subscriptionData->assignment_request < 1){
            //         $data['need_new_plan'] = true;
            //     }
            // }
            // $data['need_new_plan'] = false;
            $data['is_platinum'] = 0;
            if($subscriptionData){
                if($subscriptionData->subscription_expire_date >= date("Y-m-d") && $subscriptionData->subscription_plan_id == 1){
                    $data['is_platinum'] = 1;
                }
            }
            
            return sendResponse($data);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function assignmentData()
    {
        try{
            $data['all_subjects'] = Subject::
                                select('subject_name','id','category_id')
                                ->get();
            $data['subjects_by_category'] = $data['all_subjects']->groupBy(function($data) {
                                    return $data->category_id;
                                });
            $data['category_status'] = Category::getCetegoryForOuestions();
            return sendResponse($data);
        }
        catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    
}
