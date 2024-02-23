<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Models\AssignmentHour;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Content;
use Category;
use App\Notifications\EmailNotification;
use App\Jobs\StudentEmailJob;
class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        try{
            $keyword = $request->keyword;
            $assignment_status = $request->assignment_status;
            $sort = $request->sort;
            $page_size = ($request->page_size)? $request->page_size : 10;

            
            $data = DB::table('assignments')
                ->leftJoin('users as teacher', 'teacher.id', '=', 'assignments.teacher_id')
                ->leftJoin('users as student', 'student.id', '=', 'assignments.user_id')
                ->select('assignments.*', 'teacher.email as teacher_email','teacher.name as teacher_name',
                'student.email as student_email','student.name as student_name');

            if($keyword && $keyword != ''){
                $data = $data->where(function($query) use ($keyword){
                            $query->where('assignments.title', 'like', '%'.$keyword.'%')
                            ->orWhere('assignments.keyword', 'like', '%'.$keyword.'%');
                        });
            }
            if($assignment_status){
                $data = $data->where('assignments.assignment_status','=', $assignment_status);
            }

            if($sort == 'asc'){
                $data = $data->orderBy('assignments.created_at');
            }else{
                $data = $data->orderByDesc('assignments.created_at');
            }
            $data = $data->paginate($page_size);

            $response = [
                'success' => true,
                'data'    => $data,
                'message' => 'Success',
                'all_assignment_status' => Assignment::assignmentStatus(),
                'category_status' => Category::getCetegoryForOuestions(),
            ];
        
            return response()->json($response, 200);
            
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function assignmentDetail($id)
    {
        try{
            $data = DB::table('assignments')
                ->leftJoin('users as teacher', 'teacher.id', '=', 'assignments.teacher_id')
                ->leftJoin('users as student', 'student.id', '=', 'assignments.user_id')
                ->select('assignments.*', 'teacher.email as teacher_email','teacher.name as teacher_name',
                'student.email as student_email','student.name as student_name')
                ->where('assignments.id', '=', $id)
                ->first();
            $response = [
                'success' => true,
                'data'    => $data,
                'message' => 'Success',
                'all_assignment_status' => Assignment::assignmentStatus(),
                'category_status' => Category::getCetegoryForOuestions(),
            ];
            return sendResponse($response);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function updateStatus(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'assignment_status' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }
            $ids=$request->id;
            if($request->assignment_status != Assignment::ASSIGNMENT_STATUS_APPROVED && $request->assignment_status != Assignment::ASSIGNMENT_STATUS_REJECTED){
                return sendError('Invalid Status Request');
            }
            foreach($ids as $id){
                $assignment = Assignment::find($id);
                if(!$assignment){
                    return sendError('No data found for given Id');
                }
                $assignment->status_changed_on = now();
                if($assignment->assignment_status == Assignment::ASSIGNMENT_STATUS_SUBMITTED){
                    $assignment->assignment_status = $request->assignment_status;
                }
                $assignment->save();
            }
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
        return response()->json([
            // 'data'=>$assignment,
            'status' => 200
        ]);

    }
    
    public function assignmentBid(Request $request)
    {
        try{
            $keyword = $request->keyword;
            $sort = ($request->sort)?$request->sort : 'asc';
            $page_size = ($request->page_size)? $request->page_size : 10;

            $data = DB::table('assignments')->where('assignment_status', Assignment::ASSIGNMENT_STATUS_PENDING)
                        ->join('users', 'users.id','=', 'assignments.user_id')
                        ->where('assignments.category', Category::CONTENT_CATEGORY_IT)
                        ->where('assignments.teacher_id', 0)
                        ->where('assignments.first_bid', '!=', null)
                        ->select('assignments.*', 'users.email as student_email');
            if($keyword && $keyword != ''){
                $data = $data->where(function($query) use ($keyword){
                            $query->where('assignments.title', 'like', '%'.$keyword.'%')
                            ->orWhere('assignments.assignment_id', 'like', '%'.$keyword.'%')
                            ->orWhere('assignments.keyword', 'like', '%'.$keyword.'%')
                            ->orWhere('users.email', 'like', '%'.$keyword.'%');
                        });
            }

            if($sort == 'asc'){
                $data = $data->orderBy('assignments.first_bid');
            }else{
                $data = $data->orderByDesc('assignments.first_bid');
            }
            $data = $data->paginate($page_size);
            
            return sendResponse($data);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function singleAssignmentBid($id, Request $request)
    {
        try{
            $page_size = ($request->page_size)? $request->page_size : 10;

            $data = DB::table('assignment_hours')
                        ->join('users', 'users.id','=', 'assignment_hours.teacher_id')
                        ->join('assignments', 'assignments.id','=', 'assignment_hours.assignment_id')
                        ->where('assignment_hours.assignment_id', $id)
                        ->select('assignment_hours.*', 'users.email as teacher_email','assignments.teacher_id as assignment_teacher','assignments.assignment_id as asng_id');
            
            $data = $data->paginate($page_size);
            
            return sendResponse($data);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function assignTeacher(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'teacher_id' => 'required',
                'assignment_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $data = AssignmentHour::where('teacher_id', $request->teacher_id)
                        ->where('assignment_id',  $request->assignment_id)
                        ->first();
            if($data == null){
                return sendError('Invalid Request');
            }

            $assignmentData = Assignment::find($request->assignment_id);
            $assignmentData->teacher_id = $request->teacher_id;
            $assignmentData->assignment_hours = $data->estimated_hours;
            $assignmentData->save();

            // send Email to teacher
            $teacher = User::find($request->teacher_id);
            $message = [
                'title' => 'New Assignment is assigned to you',
                'message' => $assignmentData->question,
                'url' => env('APP_URL_FRONT').'/teacher/manageorder',
            ];
            $teacher->notify(new EmailNotification($message));

            // send Payment checkout email to Student
            $student = User::find($assignmentData->user_id);
            $studentMessage = [
                'to'=> $student->email,
                'receiver_name'=> $student->name." ".$student->last_name,
                'url'=> env('APP_URL_FRONT').'/checkout?order_type=2&assignment_id='.$assignmentData->id,
                'body' => "Pay for the assignment you have posted",
                'subject' => "Pay for the assignment you have posted",
            ];
            dispatch(new StudentEmailJob($studentMessage))->afterResponse();

            return sendResponse($assignmentData);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }
}
