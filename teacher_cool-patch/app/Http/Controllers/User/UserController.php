<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Content;
use App\Models\User;
use App\Models\UserDetails;
use App\Models\TeacherSetting;
use App\Models\Subject;
use App\Models\ContentType;
use App\Models\Assignment;
use App\Models\SubscribedUserDetail;
use App\Models\UserDownload;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Notifications\SystemNotification;
use App\Jobs\AdminEmail;
use Illuminate\Support\Facades\File;
use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Storage;
use App\Models\JobInternship;
use App\Models\QuestionAnswer;
use App\Models\NewsLetter;
use App\Models\Reward;
use App\Jobs\StudentEmailJob;
use App\Models\SystemSetting;
use App\Models\TeacherWallet;
use App\Models\CurrencyExchange;

class UserController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();
            
            $userData = DB::table('users')
                    ->join('user_details', 'users.id', '=', 'user_details.user_id');
            if($user->user_type == User::TEACHER_TYPE){
                $userData = $userData->leftJoin('teacher_settings', 'users.id', '=', 'teacher_settings.user_id')
                            ->select('user_details.*','users.name as first_name','users.last_name','users.user_type','users.teacher_status','users.email','users.profile_path','users.teacher_id_number','teacher_settings.id_proof','teacher_settings.document_path','teacher_settings.working_hours','teacher_settings.expected_income','teacher_settings.category','teacher_settings.subject_id','teacher_settings.preferred_currency','teacher_settings.experience','teacher_settings.experience_letter','teacher_settings.teacher_bio','teacher_settings.resubmit_data');
            }else{
                $userData = $userData->leftJoin('subscribed_user_details', 'users.id', '=', 'subscribed_user_details.user_id')
                            ->select('user_details.*','users.name as first_name','users.last_name','users.user_type','users.teacher_status','users.email','users.profile_path','users.teacher_id_number', 'subscribed_user_details.subscription_name','subscribed_user_details.subscription_expire_date','subscribed_user_details.file_download','subscribed_user_details.assignment_request');
            }
            
            $data['user'] = $userData->where('users.id', $user->id)->first();

            $data['all_subjects'] = Subject::
                                select('subject_name','id','category_id')
                                ->get();
            $data['subjects_by_category'] = $data['all_subjects']->groupBy(function($data) {
                                    return $data->category_id;
                                });
            $data['category_status'] = Content::getContentCategory();
            $data['experience_arr'] = User::experienceArr();
            $data['profile_status'] = User::allTeacherStatus();
            
            if(!$data){
                sendError('Inavlid User', 401);
            }
            return sendResponse($data);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function editProfile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|min:3',
                'contact' => 'required',
                'country' => 'required',
                'qualification' => 'required',
                'currency' => 'required'
                // 'profile_path' => 'file|mimes:jpg,png,jpeg',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $user = Auth::user();

            $userData = User::find($user->id);

            $is_resubmit = false;
            $new_data = [];

            if ($request->file('profile_path')) {
                $extension = $request->file('profile_path')->getClientOriginalExtension();
                $fileName = time().'.'.$extension;
                $profile_path = $request->file('profile_path')->storeAs('profile',$fileName,'public');

                $userData->profile_path = $profile_path;
            }

            if($userData->user_type == User::STUDENT_TYPE){
                $userData->name = $request->first_name;
                $userData->last_name = $request->last_name;
            }else{
                if($request->first_name != $userData->name){
                    $new_data['first_name'] = $request->first_name;
                    $is_resubmit = true;
                }
                if($request->last_name != $userData->last_name){
                    $new_data['last_name'] = $request->last_name;
                    $is_resubmit = true;
                }
            }            
            $userData->updated_at = date('Y-m-d H:i:s');
            
            $userData->save();

            // Edit User Details 
            $userDetailsData = UserDetails::where('user_id',$user->id)
                            ->first();
            $data = [];
            if($userData->user_type == User::STUDENT_TYPE){
                $data['phone_code'] = $request->phone_code;
                $data['contact'] = $request->contact;
                $data['gender'] = $request->gender;
                $data['age'] = $request->age;
                $data['city'] = $request->city;
                $data['state'] = $request->state;
                $data['country'] = $request->country;
                $data['qualification'] = $request->qualification;
                $data['university'] = $request->university;
                $data['currency'] = $request->currency;

                $userDetails = UserDetails::where('user_id',$user->id)
                            ->update($data);

                if($userDetails){
                    return sendResponse($userData, 'Profile Updated Successfully');
                }
            }else{
                if($request->contact != $userDetailsData->contact){
                    $new_data['contact'] = $request->contact;
                    $is_resubmit = true;
                }
                if($request->phone_code != $userDetailsData->phone_code){
                    $new_data['phone_code'] = $request->phone_code;
                    $is_resubmit = true;
                }
                if(strtolower($request->qualification) != strtolower($userDetailsData->qualification)){
                    $new_data['qualification'] = $request->qualification;
                    $is_resubmit = true;
                }
                // if($request->gender != $userDetailsData->gender){
                //     $new_data['gender'] = $request->gender;
                //     $is_resubmit = true;
                // }
                // if($request->age != $userDetailsData->age){
                //     $new_data['age'] = $request->age;
                //     $is_resubmit = true;
                // }
                // if($request->city != $userDetailsData->city){
                //     $new_data['city'] = $request->city;
                //     $is_resubmit = true;
                // }
                // if($request->state != $userDetailsData->state){
                //     $new_data['state'] = $request->state;
                //     $is_resubmit = true;
                // }
                if(strtolower($request->country) != strtolower($userDetailsData->country)){
                    $new_data['country'] = $request->country;
                    $is_resubmit = true;
                }

                $data['gender'] = $request->gender;
                $data['age'] = $request->age;
                $data['city'] = $request->city;
                $data['state'] = $request->state;
                $data['university'] = $request->university;
                $userDetails = UserDetails::where('user_id',$user->id)
                            ->update($data);
            }
            

            if($userData->user_type == User::TEACHER_TYPE){
                $settingsData = TeacherSetting::where('user_id',$user->id)
                                ->first();
                // dd( $settingsData);
                // $teacherSettingData['user_id'] = $user->id;
                // $teacherSettingData['working_hours'] = $request->working_hours;
                // $teacherSettingData['expected_income'] = $request->expected_income;
                // $teacherSettingData['preferred_currency'] = $request->preferred_currency;
                // $teacherSettingData['subject_id'] = $request->subject;
                // $teacherSettingData['category'] = $request->category;
                // $teacherSettingData['experience'] = $request->experience;
                $teacherSettingData['teacher_bio'] = $request->teacher_bio;
                
                if ($request->file('id_proof')) {
                    $extension = $request->file('id_proof')->getClientOriginalExtension();
                    $originalfileName = $request->file('id_proof')->getClientOriginalName();
                    $originalfileName = pathinfo($originalfileName, PATHINFO_FILENAME);
                    $originalfileName = implode('-',explode(' ', $originalfileName));
                    $fileName = $originalfileName."-".time().'.'.$extension;

                    $path = $request->file('id_proof')->storeAs('teacher',$fileName,'public');
                    // $teacherSettingData['id_proof'] = $path;
                    $new_data['id_proof'] = $path;
                    $is_resubmit = true;
                }

                if ($request->file('document_path')) {
                    $extension = $request->file('document_path')->getClientOriginalExtension();
                    $originalfileName = $request->file('document_path')->getClientOriginalName();
                    $originalfileName = pathinfo($originalfileName, PATHINFO_FILENAME);
                    $originalfileName = implode('-',explode(' ', $originalfileName));
                    $fileName = $originalfileName."-".time().'.'.$extension;

                    $path  = $request->file('document_path')->storeAs('teacher',$fileName,'public');
                    // $teacherSettingData['document_path'] = $path;
                    $new_data['document_path'] = $path;
                    $is_resubmit = true;
                }

                if ($request->file('experience_letter')) {
                    $extension = $request->file('experience_letter')->getClientOriginalExtension();
                    $originalfileName = $request->file('experience_letter')->getClientOriginalName();
                    $originalfileName = pathinfo($originalfileName, PATHINFO_FILENAME);
                    $originalfileName = implode('-',explode(' ', $originalfileName));
                    $fileName = $originalfileName."-".time().'.'.$extension;

                    $path = $request->file('experience_letter')->storeAs('teacher',$fileName,'public');
                    $teacherSettingData['experience_letter'] = $path;
                    // $new_data['experience_letter'] = $path;
                    // $is_resubmit = true;
                }

                if(strtolower($request->working_hours) != strtolower($settingsData->working_hours)){
                    $new_data['working_hours'] = $request->working_hours;
                    $is_resubmit = true;
                }
                if(strtolower($request->expected_income) != strtolower($settingsData->expected_income)){
                    $new_data['expected_income'] = $request->expected_income;
                    $is_resubmit = true;
                }
                if(strtolower($request->preferred_currency) != strtolower($settingsData->preferred_currency)){
                    $new_data['preferred_currency'] = $request->preferred_currency;
                    $is_resubmit = true;
                }
                if($request->subject != $settingsData->subject_id){
                    $new_data['subject_id'] = $request->subject;
                    $is_resubmit = true;
                }
                if($request->category != $settingsData->category){
                    $new_data['category'] = $request->category;
                    $is_resubmit = true;
                }
                if(strtolower($request->experience) != strtolower($settingsData->experience)){
                    $new_data['experience'] = $request->experience;
                    $is_resubmit = true;
                }

                $teacherSettingData['resubmit_data'] = json_encode($new_data);

                $teacherSetting = TeacherSetting::where('user_id',$user->id)
                                    ->update($teacherSettingData);

                if($teacherSetting ){
                    if($is_resubmit){
                        $userReq = User::where('id',$user->id)
                            ->update(['teacher_status'=>User::TEACHER_STATUS_RESUBMIT]);

                    // send email to Admin
                    // $adminEmailData=[
                    //     'to'=> env('ADMIN_EMAIL_ADDRESS'),
                    //     'name'=>'Teacher Cool',
                    //     'body' =>"Teacher, ".$user->name." ".$user->last_name." has resubmit the profile for approval" ,
                    //     'url' => env('APP_URL_FRONT').'/viewuser/' . $user->id,
                    //     'subject' => "Regarding Teacher Resubmit Profile For Approval"
                    // ];
                    // dispatch(new AdminEmail($adminEmailData))->afterResponse();
                    }
                    
                    
                }
                return sendResponse([], 'Profile Updated Successfully');
            }
            return sendError('Something went Wrong');
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

            $data = User::find($user->id);
            
            $data->password = Hash::make($request->new_password);
            $data->save();

            return sendResponse($data, 'Password updated successfully');
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }

    }


    public function genrateReaffral(Request $request)
    {
        try{
            
            $user = Auth::user();
            if($user->reffer_code == null){
                $user->reffer_code = getString(10);
                $user->save();
            }
            

            return sendResponse($user->reffer_code);

        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function search(Request $request)
    {
        try{
            $keyword = $request->keyword;
            $page_size = ($request->page_size)? $request->page_size : 10;
            $sort = $request->sort;
            
            $data1 = DB::table('contents')
                    ->where('contents.is_approved','=', Content::CONTENT_APPROVE)
                    ->where('contents.is_published','=', 1)
                    ->where('contents.is_pending', '1')
                    ->leftJoin('users','users.id', '=', 'contents.user_id')
                    ->select('contents.*', 'users.name as teacher_name');
            //Search By Title
            // $result1 =  $data1;
            if($keyword && $keyword != ''){
                $data1 = $data1->where('contents.name', 'like', '%'.$keyword.'%');
            }
            $data1 = $data1->orderBy('contents.name')->get()->toArray();

            //Search By description and keywords
            $data2 = DB::table('contents')
                    ->where('contents.is_approved','=', Content::CONTENT_APPROVE)
                    ->where('contents.is_published','=', 1)
                    ->where('contents.is_pending', '1')
                    ->leftJoin('users','users.id', '=', 'contents.user_id')
                    ->select('contents.*', 'users.name as teacher_name');
            
            if($keyword && $keyword != ''){
                $keyw_arr = explode(" ", $keyword);
                $data2 = $data2->where(function($query) use ($keyword, $keyw_arr){
                    // dd($keyw_arr);
                    $query = $query->where('contents.description', 'like', '%'.$keyword.'%')
                            ->orWhere('contents.keyword','like', '%'.$keyword.'%');
                    foreach($keyw_arr as $val){
                        $query = $query->orWhere('contents.keyword', 'like', '%'.$val.'%');
                    }
                });
                
            }
            $data2 = $data2->orderBy('contents.name')->get()->toArray();
            $finalResult = [];
            $indexArray = [];
            for($i = 0; $i < count($data1); $i++){
                for($j = 0; $j < count($data2); $j++){
                    if($data1[$i] == $data2[$j]){
                        array_push($indexArray, $j);
                        break;
                    }
                }
            }
            $finalResult['content'] = array_merge($finalResult, $data1);
            for($i = 0; $i < count($data2); $i++){
                if(!in_array($i, $indexArray)){
                    array_push($finalResult['content'], $data2[$i]);
                }
            }

            // Search Q&A
            $quesAnsObj = QuestionAnswer::where('status', '1');

            if($keyword && $keyword != ''){
                $quesAnsObj = $quesAnsObj->where('question', 'like', '%'.$keyword.'%');
            }
            
            $qAResult_1 = $quesAnsObj->orderBy('question')
                                    ->limit(5)
                                    ->groupBy('id')
                                    ->get()->toArray();
            $qAResult1 = array_map(function ($result){
                $result['answer']  = substr($result['answer'],0,30);
                return $result;
            },  $qAResult_1 );
            $quesAnsObj2 = QuestionAnswer::where('status', '1');
            if($keyword && $keyword != ''){
                $keyw_arr = explode(" ", $keyword);
                
                $quesAnsObj2 = $quesAnsObj2->orWhere(function($query) use ($keyword, $keyw_arr){
                    $query = $query->where('question', 'like', '%'.$keyword.'%')
                            ->orWhere('answer','like', '%'.$keyword.'%');
                    foreach($keyw_arr as $val){
                        $query = $query->orWhere('question', 'like', '%'.$val.'%');
                    }
                    foreach($keyw_arr as $val){
                        $query = $query->orWhere('answer', 'like', '%'.$val.'%');
                    }
                });
            }

            $qAResult2 = $quesAnsObj2->orderBy('question')
                                    ->limit(5)
                                    ->groupBy('id')
                                    ->get()->toArray();

            $indexArray2 = [];
            for($i = 0; $i < count($qAResult1); $i++){
                for($j = 0; $j < count($qAResult2); $j++){
                    if($qAResult1[$i] == $qAResult2[$j]){
                        array_push($indexArray2, $j);
                        break;
                    }
                }
            }
            $finalResult['ques_answ'] =  $qAResult1;
            $message = 'Success';
            $answerFound = true;
            if(count($finalResult['ques_answ']) < 1){
                $answerFound = false;
                $message = "Unable to find relevant answer, please login and post your order so that expert can assist you.";
            }
            for($i = 0; $i < count($qAResult2); $i++){
                if(!in_array($i, $indexArray2)){
                    $qAResult2[$i]['answer'] = substr($qAResult2[$i]['answer'],0,30);
                    array_push($finalResult['ques_answ'], $qAResult2[$i]);
                }
            }


            

            $response = [
                'success' => true,
                'data'    => $finalResult,
                'answer_found' => $answerFound,
                'message' => $message,
                'content_category' => Content::getContentCategory(),
                'content_type' => ContentType::select('id','name')->get(),
                
            ];
        
            return response()->json($response, 200);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function allContent(Request $request)
    {
        try{
            $page_size = ($request->page_size)? $request->page_size : 10;
            $content = Content::with('user_other_details:user_id,country')->paginate($page_size);
            return response()->json($content, 200);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function searchSingleContent(Request $request)
    {
        try{
            $keyword = strtolower($request->keyword);
            $id = $request->id;
            // $words = array('in', 'at', 'on', 'etc..');
            // $pattern = '/\b(?:' . join('|', $words) . ')\b/i';
            // $article = preg_replace($pattern, '', $article);

            if($id <= 0){
                sendError('Inavlid User', 401);
            }
            $data = DB::table('contents')
                    ->where('id',$id)
                    ->select('contents.id', 'contents.name','contents.path', 'contents.description')
                    ->first();
            // $fileData = File::get(storage_path($data->path));
            $result = [];
            $extension = explode('.', $data->path);
            
            $result['id'] = $data->id;
            $result['name'] = $data->name;
            if($extension[count($extension)-1] == 'pdf'){
                $parser = new Parser();
                $pdf = $parser->parseFile(storage_path($data->path));
                $text = $pdf->getText();
                
                
                
                $result['description'] = $this->findText($text, $keyword);
            }else {
                
                $phpWord = \PhpOffice\PhpWord\IOFactory::load(storage_path($data->path));
                
                $content = '';
                
                foreach($phpWord->getSections() as $section) {
                    foreach($section->getElements() as $element) {
                        if (method_exists($element, 'getElements')) {
                            
                            foreach($element->getElements() as $childElement) {
                                
                                if (method_exists($childElement, 'getText')) {
                                    
                                    $content .= $childElement->getText() . ' ';
                                }
                                else if (method_exists($childElement, 'getContent')) {
                                    
                                    $content .= $childElement->getContent() . ' ';
                                }
                            }
                        }
                        else if (method_exists($element, 'getText')) {
                            if(gettype($element->getText()) == 'object'){
                                break;
                            }
                            $content .= $element->getText() . ' ';
                        }
                    }
                }
                $result['description'] = $this->findText($content, $keyword);
                if($result['description'] == ''){
                    $result['description'] = $data->description;
                }
                
            } 
            
            
            return sendResponse($result);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }

    }

    private function findText($text, $keyword){
        $text = strtolower(trim( $text));
                
        $position = strpos($text, $keyword, 0);
        return substr($text, $position, 80);
    }
    

    public function statsInfo()
    {
        try{
            $user = Auth::user();
            $user_type = 'student';
            if($user->user_type == User::TEACHER_TYPE){
                $user_type = 'teacher';
                $totalAssignment = DB::table('assignments')
                                    ->join('users as teacher', 'teacher.id', '=', 'assignments.teacher_id')
                                    ->where('teacher.id', $user->id)->get()->count();
                $assignmentAnswered = DB::table('assignments')
                                    ->join('users as teacher', 'teacher.id', '=', 'assignments.teacher_id')
                                    ->where('teacher.id', $user->id)
                                    ->where('assignment_status', Assignment::ASSIGNMENT_STATUS_SUBMITTED)
                                    ->get()->count();
                $assignmentApproved = DB::table('assignments')
                                    ->join('users as teacher', 'teacher.id', '=', 'assignments.teacher_id')
                                    ->where('teacher.id', $user->id)->where('assignment_status', Assignment::ASSIGNMENT_STATUS_APPROVED)
                                    ->get()->count();
                $assignmentResubmitRequest = DB::table('assignments')
                                    ->join('users as teacher', 'teacher.id', '=', 'assignments.teacher_id')
                                    ->where('teacher.id', $user->id)->where('assignment_status', Assignment::ASSIGNMENT_STATUS_RESUBMIT_REQUEST)
                                    ->get()->count();
                $assignmentResubmitAnswer = DB::table('assignments')
                                    ->join('users as teacher', 'teacher.id', '=', 'assignments.teacher_id')
                                    ->where('teacher.id', $user->id)->where('assignment_status', Assignment::ASSIGNMENT_STATUS_RESUBMIT_ANSWER)
                                    ->get()->count();

                $data['total_earnings'] = 1000;
                $data['total_assignments'] =  $totalAssignment;
                $data['assignment_answered'] =  $assignmentAnswered;
                $data['assignment_approved'] =  $assignmentApproved;
                $data['assignment_resubmit_request'] =  $assignmentResubmitRequest;
                $data['assignment_resubmit_answer'] =  $assignmentResubmitAnswer;
            }

            $notification = DB::table('notifications')
                    ->where('notifiable_id' , $user->id)
                    ->where('read_at', null)
                    ->get()->count();

            $user = Auth::user();
            $currency = 'INR';
            if($user){
                $currency = $user->user_details->currency;
            }
            $data['currency'] = $currency;

            $data['notification'] =  $notification;
            
            $data['name'] =  $user->name;
            $data['user_type'] = $user_type;
           
            return sendResponse($data);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function buyContent(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'content_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $user = Auth::user();
            $downloadData = UserDownload::where('user_id', $user->id)
                                ->where('content_id', $request->content_id)->first();

            if($downloadData){
                $result['url'] = $downloadData->path;
                return sendResponse($result);
            }

            $subscriptionData = SubscribedUserDetail::where("user_id",$user->id)->first();

            $proceed_to_payment = 0;
            if($subscriptionData == null || $subscriptionData->subscription_expire_date < date("Y-m-d") || $subscriptionData->file_download <= 0){
                $proceed_to_payment = 1;
            }
            
            
            if($proceed_to_payment == 0){
                $data = Content::find($request->content_id);
                if($data == null){
                    return sendError("Data not found", [], 404);
                }

                $obj = SubscribedUserDetail::where("user_id",$user->id);
                $obj->update(['file_download'=> $subscriptionData->file_download -1]);

                $user_download = new UserDownload;
                $user_download->user_id = $user->id;
                $user_download->content_id = $request->content_id;
                $user_download->path = $data->path;
                $user_download->save();

                $result['url'] = $data->path;

                //add rewards/wallet money for seller
                // dd($request->content_id);
                if( $data->paid_to_seller == 0){
                    //Add wallet or rewards for seller
                    $this->addSellerPayment($request->content_id);
                }
            }
            $result['content_id'] = $request->content_id;
            $result['proceed_to_payment'] = $proceed_to_payment;

            return sendResponse($result);

        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }
    public function addSellerPayment($contentID)
    {   
        try{
            //get seller Content
            $sellerContent = Content::with('user_details')->find($contentID);
            if(!$sellerContent){
                return response()->json(['status' => 'error', 'code' => '404', 'meassage' => 'Content Not Found!']);
            }
            //if exchange
            $calculated_words = 0;
            if($sellerContent->is_exchange == 1){  
                $rewardObj = new Reward;    

                $perWordRate = $this->calculatePerWordRate();

                //seller content words according to the exchange ratio
                $settingData = SystemSetting::first();
                $calculated_words = ($settingData->word_conversion_rate / $settingData->actual_word_present * $sellerContent->word_count);
                $points = round($calculated_words * $perWordRate, 2);

                $rewardObj->user_id = $sellerContent->user_id;
                $rewardObj->points = $points;
                $rewardObj->transection_type = Reward::REWARD_CREDIT;
                $rewardObj->reward_type = Reward::CONTENT_REWARD_TYPE;
                $rewardObj->in_words = $calculated_words;
                $rewardObj->save();
            }else{
                //Add expected amount to wallet
                $wallet = new TeacherWallet;

                $wallet->user_id = $sellerContent->user_id;
                $wallet->amount = $sellerContent->expected_amount;
                $wallet->description = "Pay for content Id: ". $sellerContent->id ." to user ".$sellerContent->user_details->name." ".$sellerContent->user_details->email;

                $wallet->save();
            }
            $sellerContent->paid_to_seller = 1;
            $sellerContent->save();
            
        }catch(Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function sosEmail()
    {
        try{
            $user = Auth::user();

            $adminEmailData=[
                'to'=> env('ADMIN_EMAIL_ADDRESS'),
                'name'=>'Teacher Cool',
                'url' => null,
                'body' =>"New user with email id: ".$user->email." need help from Teacher Cool" ,
                'subject' => "SOS Notification"
            ];
            dispatch(new AdminEmail($adminEmailData))->afterResponse();
            return sendResponse("success");
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function careers(Request $request)
    {
        try{
            $keyword = $request->keyword;
            $sort = $request->sort;
            $page_size = ($request->page_size)? $request->page_size : 10;

            
            $data = JobInternship::where('status', 1);

            if($keyword && $keyword != ''){
                $data = $data->where(function($query) use ($keyword){
                            $query->where('title', 'like', '%'.$keyword.'%')
                            ->orWhere('department', 'like', '%'.$keyword.'%');
                        });
            }

            if($sort == 'asc'){
                $data = $data->orderBy('created_at');
            }else{
                $data = $data->orderByDesc('created_at');
            }
            $data = $data->paginate($page_size);
        
            return sendResponse($data);
            
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function careersDetail($id)
    {
        try{
            if($id <= 0){
                return sendError('Inavlid User', 401);
            }

            $data = JobInternship::find($id);
            if(!$data){
                return sendError('Inavlid User', 404);
            }
            return sendResponse($data);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function article(Request $request)
    {
        try{
            $keyword = $request->keyword;
            $page_size = ($request->page_size)? $request->page_size : 10;
            $sort = $request->sort;

            $data = new NewsLetter;

            if($keyword){
                $data = $data->where('title', 'like', '%'.$keyword.'%');
            }

            if($sort == 'asc'){
                $data = $data->orderBy('created_at');
            }else{
                $data = $data->orderByDesc('created_at');
            }
            $data = $data->paginate($page_size);
        
            return sendResponse($data);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function articleSingle($id, Request $request)
    {
        try{
            if($id <= 0){
                sendError('Inavlid Request', 401);
            }

            $data = NewsLetter::find($id);

            if($data == null){
                return sendError('Data not found');
            }
        
            return sendResponse($data);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function applyCareer(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $user = Auth::user();

            if($user->user_type == User::TEACHER_TYPE){
                $userType = 'Teacher';
            }else{
                $userType = 'Student';
            }

            $file = null;
            if ($request->file('resume')) {
                $file = $request->file('resume');
                $extension = $request->file('resume')->getClientOriginalExtension();
                $originalfileName = $request->file('resume')->getClientOriginalName();
                $originalfileName = pathinfo($originalfileName, PATHINFO_FILENAME);
                $originalfileName = implode('-',explode(' ', $originalfileName));
                $fileName = $originalfileName."-".time().'.'.$extension;
                // store CV in resume folder...
                $resume_path = $request->file('resume')->storeAs('resume',$fileName,'public');
            }

            $jobData = JobInternship::find($request->id);

            if(!$jobData){
                return sendError('Inavlid User');
            }
            
            $adminEmailData=[
                'to'=> env('ADMIN_EMAIL_ADDRESS'),
                'cc'=>$jobData->recruiter_email,
                'filename'=> $resume_path,
                'name'=>'Teacher Cool',
                'filename' => $fileName,
                'profile_url' => env('APP_URL_FRONT').'/viewuser/'.$user->id,
                'body' =>"A User with email, '".$user->email."' has applied for the job/internship of '".$jobData->title."'. And user is register as '".$userType."' with Teacher Cool" ,
                'subject' => "Regarding Job & Internship Application"
            ];
            
            dispatch(new AdminEmail($adminEmailData))->afterResponse();
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function rewards(Request $request)
    {
        try{
            $keyword = $request->keyword;
            $page_size = ($request->page_size)? $request->page_size : 10;
            
            $sort = $request->sort;

            $user = Auth::user();

            $currency = $user->user_details->currency;
            $exchangeData = CurrencyExchange::where('currency',$currency)->first();
            $exchange_rate = round($exchangeData->exchange_rate, 2);
            
            $data = Reward::where('user_id', $user->id);
            // if($keyword && $keyword != ''){
            //     $data = $data->where(function($query) use ($keyword){
            //                 $query->where('name', 'like', '%'.$keyword.'%')
            //                 // ->orWhere('users.order_id', 'like', '%'.$keyword.'%')
            //                 ->orWhere('order_id', 'like', '%'.$keyword.'%');
            //             });
            // }
            
            if($sort == 'asc'){
                $data = $data->orderBy('created_at');
            }else{
                $data = $data->orderByDesc('created_at');
            }
            $data = $data->paginate($page_size);
            $response = [
                'success' => true,
                'data'    => $data,
                'message' => 'Success',
                'exchange_rate' => $exchange_rate
            ];

            // return response()->json($response, 200);
    
            return sendResponse($response);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }
    public function isUserExist(Request $request)
    {   
        try{
            $userData = User::where('email', $request->get('email'))->get();
            if($userData->count() < 1){
                return sendResponse($userData->toJson(), 'New User.');
            }
            else{
                return sendResponse($userData, 'User exists.');
            }
        }catch(Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
        
    }
    public function verifyEmail(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'otp' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }
            $data = $request->all();
            $user = User::where('email', $data['email'])->where('email_verify_code', $data['otp'])->first();
            if($user){
                $user->email_verified_at = now();
                $user->email_verify_code = null;
                if($user->save()){
                    return sendResponse($user->toArray(), 'Email Verified.');
                }
            }
            else{
                return response()->json(['status' => 'error', 'code' => '400', 'meassage' => "Invalid OTP."]);
            }
        }catch(Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    private function calculatePerWordRate()
    {
        $settingData = SystemSetting::first();
        return $settingData->rate_per_assignment/$settingData->word_per_assignment;
    }
    
    public function viewAnswer(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'qa_id' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }
            $user_id = Auth::id();
            $data = $request->all();
            $isSubscribed = false;
            $msg = 'You need to purchase a subscription to view answer.';
            $subscribedUser = SubscribedUserDetail::where('user_id',$user_id)->first();
            if($subscribedUser  && $subscribedUser->subscription_expire_date > date_format(now(),'Y-m-d')){
                $isSubscribed = true;
                $msg = 'Data fetched successfully.';
            }
            if($isSubscribed){
                $data['question_answer'] = QuestionAnswer::where('status', '1')->find($data['qa_id']);                
            }
            $data['is_subscribed'] = $isSubscribed;
            return sendResponse($data, $msg);
        }catch(Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }
}
