<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Assignment;
use App\Models\SystemSetting;
use App\Models\TeacherWallet;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Content;
use Illuminate\Support\Facades\Validator;
use Category;
use Carbon\Carbon;

class AssignmentPaymentController extends Controller
{
    public function paymentList(Request $request)
    {
        try {
            $keyword = $request->keyword;
            
            $sort = $request->sort;
            $page_size = ($request->page_size)? $request->page_size : 10;

            $data = DB::table('users')
                ->join('teacher_settings', 'users.id', '=', 'teacher_settings.user_id')
                ->join('assignments', 'users.id', '=', 'assignments.teacher_id')
                ->selectRaw('count(assignments.id) as assignments_count, users.name, users.email, users.teacher_id_number, users.is_payment_block, teacher_settings.category, assignments.teacher_id ');
            if($keyword && $keyword != ''){
                $data = $data->where(function($query) use ($keyword){
                            $query->where('users.name', 'like', '%'.$keyword.'%')
                            ->orWhere('users.email', 'like', '%'.$keyword.'%');
                        });
            }
            

            if($sort == 'asc'){
                $data = $data->orderBy('users.name');
            }else{
                $data = $data->orderByDesc('users.name');
            }
            $data = $data->groupBy('teacher_id')
                        ->paginate($page_size);

            return sendResponse($data);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
        
    }

    public function singlePaymentTeacher(Request $request)       
    {
        try {
            $teacher_id = $request->teacher_id;
            $start_date = ($request->start_date)? date("Y-m-d", strtotime($request->start_date)): null;
            $end_date = ($request->end_date)? date("Y-m-d", strtotime($request->end_date)): null;

            $sort = $request->sort;
            $page_size = ($request->page_size)? $request->page_size : 10;
            
            if($teacher_id < 1 ){
                return sendError('Invalid Request');
            }
            $data = DB::table('assignments')
                ->join('users as teacher', 'teacher.id', '=', 'assignments.teacher_id')
                ->join('users as student', 'student.id', '=', 'assignments.user_id')
                ->select('teacher.name as teacher_name', 'teacher.email as teacher_email','student.name as student_name','assignments.id','assignments.amount', 'assignments.assignment_id', 'assignments.category','assignments.title', 'assignments.teacher_id', 'assignments.is_paid_to_teacher','assignments.assignment_status', 'assignments.due_date','assignments.answered_on_date','assignments.answered_on_time')
                ->where('teacher_id', $teacher_id);

            if($start_date && $start_date != ''){
                $data = $data->where('assignments.answered_on_date','>=', $start_date);
            }

            if($end_date && $end_date != ''){
                $data = $data->where('assignments.answered_on_date','<=', $end_date);
            }

            if($sort == 'asc'){
                $data = $data->orderBy('assignments.answered_on_date');
            }else{
                $data = $data->orderByDesc('assignments.answered_on_date');
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

    public function blockTeacherPayment(Request $request)       
    {
        try {
            $validator = Validator::make($request->all(), [
                'teacher_id' => 'required|integer',
                'block_status' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $teacher_id = $request->teacher_id;
            $status = $request->block_status;

            if($status != 0 && $status !=  1){
                return sendError('Invalid Request');
            }

            if($teacher_id < 1){
                return sendError('Invalid Request');
            }

            $data = User::find($teacher_id);
            if(!$data){
                return sendError('Invalid Request', [], 404);
            }
            $data->is_payment_block = $status;
            $data->save();

            return sendResponse('Status Updated Successfully');
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
        
    }

    public function payToTeacher()
    {
        try{
            $data = DB::table('assignments')
                    ->join('users as teacher', 'teacher.id', '=', 'assignments.teacher_id')
                    ->select('teacher.name as teacher_name', 'teacher.email as teacher_email','assignments.id','assignments.amount', 'assignments.assignment_id', 'assignments.category','assignments.title', 'assignments.teacher_id', 'assignments.assignment_status', 'assignments.due_date','assignments.answered_on_date','assignments.answered_on_time', 'assignments.assignment_hours','teacher.is_payment_block')
                    ->where('teacher.is_payment_block', 0)
                    ->where(function($query){
                        $query->where('assignments.assignment_status', Assignment::ASSIGNMENT_STATUS_APPROVED)
                        ->orWhere('assignments.assignment_status', Assignment::ASSIGNMENT_STATUS_RESUBMIT_ANSWER);
                    })
                    ->get()->toArray();
            
            $systemSetting = SystemSetting::first();
            
            $hourly_rate = $systemSetting->hourly_rate_it_coding;
            $teacher_weightage = 100 - $systemSetting->teacher_cool_weightage;

            foreach($data as $val){
                $ansDateTime = new Carbon($val->answered_on_date.' '.$val->answered_on_time);
                $currentDateTime = Carbon::now();
                
                //Check if the the answer time is passed 24 hours
                if($ansDateTime->addHour(24) < $currentDateTime){
                    $amount = 0;
                    if($val->category == Category::CONTENT_CATEGORY_IT){
                        // Pay on hourly basis
                        
                        $amount = $hourly_rate;
                        
                    }else{
                        // Pay fix rate per assignment
                        $systemSetting = SystemSetting::first();
                        $amount = $systemSetting->rate_per_assignment;
                    }

                    $amount_for_teacher = ($amount/100) * $teacher_weightage;
                    
                    $wallet = new TeacherWallet;
                    $wallet->user_id = $val->teacher_id;
                    $wallet->amount = $amount_for_teacher;
                    $wallet->description = 'Amount transfer for assignment '.$val->assignment_id;
                    $wallet->save();

                    $obj = Assignment::find($val->id);
                    $obj->is_paid_to_teacher = 1;
                    $obj->save();
                        
                }
            }
            return sendResponse("success");
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }
}
