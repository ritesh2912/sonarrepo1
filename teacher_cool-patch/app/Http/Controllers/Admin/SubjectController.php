<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Content;
use App\Models\TeacherSetting;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Http\Request;
use Category;
class SubjectController extends Controller
{
    public function index()
    {
        try{
            $data = Subject::select('id','category_id','subject_name')->orderBy('created_at', 'desc')->get();
            if(!$data){
                return sendError('No record Found');
            }

            $response = [
                'success' => true,
                'data'    => $data,
                'message' => 'Success',
                'category' => Category::subjectCetegory(),
            ];
            return response()->json($response, 200);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function getSubject($id)     
    {
        try {
            $category = Subject::find($id);
            if(!$category){
                return sendError('No record found');
            }
            return sendResponse($category);
        } catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
        
    }

    public function addSubject(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'category_id' => 'required',
                'subject_name' => 'required|unique:subjects',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }
            $category = new Subject;
            $category->category_id = $request->category_id;
            $category->subject_name = $request->subject_name;
            $category->save();

            return sendResponse($category,"Subject Added successfully");
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function editSubject($id,Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'category_id' => 'required',
                'subject_name' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }
            $category = Subject::find($id);
            if(!$category){
                return sendError('No record found');
            }
            $category->subject_name = $request->subject_name;
            $category->category_id = $request->category_id;
            $category->save();

            return sendResponse($category,"Subject Updated successfully");
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function destroy($id)    
    {
        try{
            if(!$id){
                return sendError('Id is required');
            }
            $data = TeacherSetting::where('subject_id', $id)->first();
            
            if($data){
                return sendError('Subject is selected by some teachers');
            }
            $category = Subject::find($id);
            if($category){
                $category->delete();
            }else{
                return sendError('No record found for given Id');
            }
            return sendResponse('Deleted Successfully',200);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
        
    }
    
}
