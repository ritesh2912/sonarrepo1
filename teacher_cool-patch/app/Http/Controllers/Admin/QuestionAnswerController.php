<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QuestionAnswer;
use Illuminate\Support\Facades\Validator;
class QuestionAnswerController extends Controller
{
    public function index(Request $request)
    {
        try {
            $page_size = $request->page_size ? $request->page_size : 10;
            $keyword = $request->keyword;
            $status = $request->status;

            $data = new QuestionAnswer;

            if ($keyword && $keyword != '') {
                $data = $data->where(function ($query) use ($keyword) {
                    $query->where('question', 'like', '%' . $keyword . '%')
                        ->orWhere('answer', 'like', '%' . $keyword . '%');
                });
            }
            if ($status == 1) {
                $data = $data->where('status', $status);
            }

            $data = $data->orderByDesc('created_at')
                ->paginate($page_size);

            return sendResponse($data);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function upload(Request $request)
    {
        try {
            foreach($request->file('file') as $val){
                // dd("test");
                $fileObj = fopen($val, "r");
                $importData_arr = array();
                $i = 0;
                while (($filedata = fgetcsv($fileObj, 1000, ",")) !== FALSE) {
                    // $num = count($filedata);
                    $importData_arr[$i]['question'] = $filedata[0];
                    $importData_arr[$i]['answer'] = $filedata[1];
                    $importData_arr[$i]['created_at'] = date('Y-m-d H:i:s');
                    $i++;
                }
                fclose($fileObj);

                $data = QuestionAnswer::insert($importData_arr);
                if(!$data){
                    return sendError("Something went wrong.", [], 500);
                }
            }
            
            return sendResponse('Successfull');
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function getSingleQueAns($id)
    {
        try{
            if($id < 1 ){
                return sendError("Invalid Request");
            }

            $data = QuestionAnswer::find($id);
            
            if($data == null){
                return sendError("Data not found", [], 404);
            }

            return sendResponse($data);
        }catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function addQueAns(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'question' => 'required',
                'answer' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $obj = new QuestionAnswer;
            $obj->question = $request->question;
            $obj->answer = $request->answer;
            $obj->save();

            return sendResponse('Successfull');
        }catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function editQueAns(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'question' => 'required',
                'answer' => 'required',
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $data = QuestionAnswer::find($request->id);
            if($data == null){
                return sendError("Data not found", [], 404);
            }

            $data->question = $request->question;
            $data->answer = $request->answer;
            $data->save();

            return sendResponse('Successfull');
        }catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }
}
