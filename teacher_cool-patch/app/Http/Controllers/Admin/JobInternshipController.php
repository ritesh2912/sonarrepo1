<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobInternship;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class JobInternshipController extends Controller
{
    public function index(Request $request)
    {
        try{
            $keyword = $request->keyword;
            $status = $request->status;
            $sort = $request->sort;
            $page_size = ($request->page_size)? $request->page_size : 10;

            
            $data = new JobInternship;

            if($keyword && $keyword != ''){
                $data = $data->where(function($query) use ($keyword){
                            $query->where('title', 'like', '%'.$keyword.'%')
                            ->orWhere('department', 'like', '%'.$keyword.'%');
                        });
            }
            if($status != null){
                $data = $data->where('status','=', $status);
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

    public function singleRecord($id)
    {
        try{
            if($id <= 0){
                return sendError("Invalid Request");
            }

            
            $data = JobInternship::find($id);

            return sendResponse($data);
            
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function addRecord(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'positions_count' => 'required',
                'recruiter_email' => 'required|email',
                'job_category' => 'required',
                'type' => 'required',
                'status' => 'required',
                'skills' => 'required',
                'department' => 'required',
                'experience' => 'required',
                'currency' => 'required',
                'salary' => 'required',
                'description' => 'required',
            ]);
    
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }
    
            $data = new JobInternship;
            $data->title = $request->title;
            $data->positions_count = $request->positions_count;
            $data->recruiter_email = $request->recruiter_email;
            $data->job_category = $request->job_category;
            $data->type = $request->type;
            $data->status = $request->status;
            $data->skills = $request->skills;
            $data->department = $request->department;
            $data->experience = $request->experience;
            $data->currency = $request->currency;
            $data->salary = $request->salary;
            $data->description = $request->description;
            $data->save();
            return sendResponse("", "Submitted successfully");
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
        
    }

    public function editRecord($id, Request $request)
    {
        try{

            if($id < 1){
                return sendError("Invalid Request");
            }
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'positions_count' => 'required',
                'recruiter_email' => 'required|email',
                'job_category' => 'required',
                'type' => 'required',
                'status' => 'required',
                'skills' => 'required',
                'department' => 'required',
                'experience' => 'required',
                'currency' => 'required',
                'salary' => 'required',
                'description' => 'required',
            ]);
    
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }
    
            $data = JobInternship::find($id);
            if($data == null){
                return sendError("Data not found");
            }
            $data->title = $request->title;
            $data->positions_count = $request->positions_count;
            $data->recruiter_email = $request->recruiter_email;
            $data->job_category = $request->job_category;
            $data->type = $request->type;
            $data->status = $request->status;
            $data->skills = $request->skills;
            $data->department = $request->department;
            $data->experience = $request->experience;
            $data->currency = $request->currency;
            $data->salary = $request->salary;
            $data->description = $request->description;
            $data->save();
            return sendResponse("", "Updated successfully");
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
        
    }

    public function jobInternshipStatus(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'status' => 'required',
                'id' => 'required',
            ]);
    
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }
    
            $data = JobInternship::find($request->id);
            if($data == null){
                return sendError("Data not found");
            }
            $data->description = $request->status;
            $data->save();
            return sendResponse("", "Updated successfully");
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }
}
