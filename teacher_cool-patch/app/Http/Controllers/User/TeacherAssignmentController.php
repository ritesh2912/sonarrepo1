<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Models\TeacherSetting;
use App\Models\AssignmentHour;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Category;
use Exception;
use Carbon\Carbon;
use App\Models\Order;


class TeacherAssignmentController extends Controller
{
    public function assignmentRequest(Request $request)
    {
        try{
            // $keyword = $request->keyword;
            // $assignment_status = $request->assignment_status;
            // $sort = $request->sort;
            $page_size = ($request->page_size)? $request->page_size : 10;

            $user = Auth::user();
            $teacher = TeacherSetting::where('user_id', $user->id)->first();
            
            $data = Assignment::where('assignment_status',Assignment::ASSIGNMENT_STATUS_PENDING);
            
            if($teacher->category == Category::CONTENT_CATEGORY_IT){
                $data = $data->where(function($query){
                    $query->where('category', Category::CONTENT_CATEGORY_IT)
                            ->orWhere('category', Category::CONTENT_CATEGORY_IT_WITHOUT_CODING)
                            ->orWhere('category', Category::CONTENT_OTHERS);
                });
                
            }elseif($teacher->category == Category::CONTENT_CATEGORY_NON_IT){
                $data = $data->where(function($query){
                    $query->where('category', Category::CONTENT_CATEGORY_NON_IT)
                    ->orWhere('category', Category::CONTENT_OTHERS);
                }); 
            }      
            $data = $data->orderByDesc('created_at')->paginate($page_size);

            $answered_assignment = Assignment::where('teacher_id', $user->id)
                                    ->where('assignment_status', Assignment::ASSIGNMENT_STATUS_SUBMITTED)
                                    ->get()->count();
            $approved_assignment = Assignment::where('teacher_id', $user->id)
                                    ->where('assignment_status', Assignment::ASSIGNMENT_STATUS_APPROVED)
                                    ->get()->count();
            $response = [
                'success' => true,
                'data'    => $data,
                'message' => 'Success',
                'all_assignment_status' => Assignment::assignmentStatus(),
                'category_status' => Category::getCetegoryForOuestions(),
                'teacher' => $user->id,
                'answered_assignment' => $answered_assignment,
                'approved_assignment' => $approved_assignment,
                'total_assignment' => $answered_assignment + $approved_assignment,
            ];
            
            return sendResponse($response, "Successfully");
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function myAnswerAssignment(Request $request)
    {
        try{
            $keyword = $request->keyword;
            $category = $request->category;
            // $sort = $request->sort;
            $page_size = ($request->page_size)? $request->page_size : 10;

            $user = Auth::user();
            
            $data = Assignment::where('teacher_id',$user->id);

            if($keyword && $keyword != ''){
                $data = $data->where(function($query) use ($keyword){
                            $query->where('assignments.question', 'like', '%'.$keyword.'%')
                            ->orWhere('assignments.question_description', 'like', '%'.$keyword.'%');
                        });
            }

            if($category){
                $data = $data->where('assignments.category','=', $category);
            }
            
            $data = $data->orderByDesc('answered_on_date')
                    ->paginate($page_size);;
            

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

    public function acceptAssignment(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'assignment_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $user = Auth::user();

            $data = Assignment::find( $request->assignment_id);
            
            if(!$data){
                return sendError("Invalid Request");
            }

            if($data->is_paid_for_assignment != 1){
                return sendError("Assignment payment is pending.");
            }

            if($data->assignment_status != Assignment::ASSIGNMENT_STATUS_PENDING || $data->teacher_id != 0){
                return sendError("Answer is already submitted by other Teacher.");
            }

            $data->teacher_id = $user->id;
            // $data->assignment_status = Assignment::ASSIGNMENT_STATUS_SUBMITTED;
            $data->save();

            return sendResponse("Accepted Successfully");
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function answerAssignment(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'assignment_id' => 'required',
                'assignment_answer' => 'required',
                // 'time_zone' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $dateTime = explode(" ", date('Y-m-d H:i:s'));

            $user = Auth::user();

            $data = Assignment::find( $request->assignment_id);
            
            if(!$data){
                return sendError("Invalid Request");
            }

            if($data->assignment_status != Assignment::ASSIGNMENT_STATUS_PENDING || $data->teacher_id != $user->id){
                return sendError("Answer is already submitted by other Teacher.");
            }

            // $assignment_path = null;
            // if ($request->file('assignment_attachment')) {
            //     // $name = $request->file('assignment_attachment')->getClientOriginalName();
            //     $extension = $request->file('assignment_attachment')->getClientOriginalExtension();
            //     $originalfileName = $request->file('assignment_attachment')->getClientOriginalName();
            //     $originalfileName = pathinfo($originalfileName, PATHINFO_FILENAME);
            //     $originalfileName = implode('-',explode(' ', $originalfileName));
            //     $fileName = $originalfileName."-".time().'.'.$extension;
            //     $assignment_path = $request->file('assignment_attachment')->storeAs('assignment',$fileName,'public');
            // }

            $data->teacher_id = $user->id;
            $data->assignment_answer = $request->assignment_answer;
            $data->assignment_status = Assignment::ASSIGNMENT_STATUS_SUBMITTED;
            $data->answered_on_date = $dateTime[0];
            $data->answered_on_time = $dateTime[1];
            $data->save();

            return sendResponse("Submitted Successfully");
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function resubmitRequests(Request $request)
    {
        try{
            $keyword = $request->keyword;
            $category = $request->category;
            $page_size = ($request->page_size)? $request->page_size : 10;

            $user = Auth::user();
            
            $data = Assignment::where('teacher_id',$user->id)
                            ->where(function($query) {
                                $query->where('assignment_status',Assignment::ASSIGNMENT_STATUS_RESUBMIT_REQUEST)
                                    ->orWhere('assignment_status',Assignment::ASSIGNMENT_STATUS_REJECTED);
                            });
            
            if($keyword && $keyword != ''){
                $data = $data->where(function($query) use ($keyword){
                    $query->where('question', 'like', '%'.$keyword.'%')
                    ->orWhere('question_description', 'like', '%'.$keyword.'%');
                });
            }

            if($category){
                $data = $data->where('assignments.category','=', $category);
            }
                  
            $data = $data->orderByDesc('created_at')
                        ->paginate($page_size);
            
            $resubmit_request = Assignment::where('teacher_id', $user->id)
                                    ->where('assignment_status', Assignment::ASSIGNMENT_STATUS_RESUBMIT_REQUEST)
                                    ->get()->count();
            $resubmit_answered = Assignment::where('teacher_id', $user->id)
                                    ->where('assignment_status', Assignment::ASSIGNMENT_STATUS_RESUBMIT_ANSWER)
                                    ->get()->count();

            $response = [
                'success' => true,
                'data'    => $data,
                'message' => 'Success',
                'all_assignment_status' => Assignment::assignmentStatus(),
                'category_status' => Category::getCetegoryForOuestions(),
                'resubmit_request' => $resubmit_request,
                'resubmit_answered' => $resubmit_answered,
            ];
            
            return sendResponse($response, "Successfully");
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function answerResubmit(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'assignment_id' => 'required',
                'assignment_answer' => 'required',
                // 'time_zone' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $dateTime = explode(" ", date('Y-m-d H:i:s'));

            $user = Auth::user();

            $data = Assignment::find( $request->assignment_id);
            
            if(!$data || $data->teacher_id != $user->id){
                return sendError("Invalid Request");
            }
            if($data->assignment_status != Assignment::ASSIGNMENT_STATUS_REJECTED && $data->assignment_status != Assignment::ASSIGNMENT_STATUS_RESUBMIT_REQUEST){
                return sendError("Invalid Request");
            }

            $data->teacher_id = $user->id;
            $data->assignment_answer = $request->assignment_answer;
            $data->assignment_status = Assignment::ASSIGNMENT_STATUS_RESUBMIT_ANSWER;
            $data->answered_on_date = $dateTime[0];
            $data->answered_on_time = $dateTime[1];
            // $data->resubmit_request = $data->resubmit_request + 1;
            $data->save();

            return sendResponse("Submitted Successfully");
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function bidAssignment(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'assignment_id' => 'required',
                'hours' => 'required',
                // 'time_zone' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $assignment = Assignment::where('id',$request->assignment_id)
                                ->where('assignment_status',Assignment::ASSIGNMENT_STATUS_PENDING)
                                ->where('category', Category::CONTENT_CATEGORY_IT)->first();
                                // dd($assignment);
            if($assignment == null){
                return sendError("Invalid Assignment id");
            }

            
            $user = Auth::user();
            $bidData = AssignmentHour::where('assignment_id', $request->assignment_id)
                            ->where('teacher_id', $user->id)
                            ->first();
                            
            if($bidData == null){
                $result = Assignment::find($request->assignment_id);
                $result->first_bid = date('Y-m-d h:i:s');
                $result->save();
                
            }

            $assignmentData['estimated_hours'] = $request->hours;
            $obj = AssignmentHour::updateOrCreate(
                ['assignment_id' => $request->assignment_id, 'teacher_id' => $user->id],
                $assignmentData
            );
            $obj->save();
            return sendResponse('', "Successfull");
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function myAssignmentSingle($id, Request $request)
    {
        try{
            if($id < 1 ){
                return sendError("Invalid Request");
            }

            $user = Auth::user();
            
            $data = Assignment::where('teacher_id',$user->id)
                        ->where('assignments.id',$id)
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

    // public function assignTeacher()
    // {
    //     try{
    //         $assignmentData = Assignment::where('assignment_status', Assignment::ASSIGNMENT_STATUS_PENDING)
    //                     ->where('category', Category::CONTENT_CATEGORY_IT)
    //                     ->where('teacher_id', 0)
    //                     ->where('first_bid', '!=', null)
    //                     ->get();
    //         // dd($assignmentData);
    //         foreach($assignmentData as $data){
                
    //             $dateTime = new Carbon($data->first_bid);
    //             $currentDate = Carbon::now();
                
    //             if($dateTime->addMinutes(30) <= $currentDate){
    //                 $bidData = AssignmentHour::where('assignment_id', $data->id)
    //                                 ->orderBy('estimated_hours')
    //                                 ->first();

    //                 $assignment = Assignment::find($data->id);
    //                 $assignment->teacher_id = $bidData->teacher_id;
    //                 $assignment->save();
    //             }
                
    //         }
    //     }catch (Exception $e){
    //         return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
    //     }
    // }
}
