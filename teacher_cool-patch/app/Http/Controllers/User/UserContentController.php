<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\Validator;

use App\Models\Content;
use App\Models\User;

class UserContentController extends Controller
{
    public function index(Request $request)
    {
        try{
            $keyword = $request->keyword;
            
            $user = Auth::user();

            $data = Content::where('user_id', $user->id)
                        ->where('is_approved','=', Content::CONTENT_APPROVE);
            if($keyword && $keyword != ''){
                $data = $data->where('name', 'like', '%'.$keyword.'%');
            }
            
            $data = $data->paginate(10);

            $response = [
                'success' => true,
                'data'    => $data,
                'message' => 'Success',
                'content_category' => Content::getContentCategory(),
            ];
        
            return response()->json($response, 200);
            
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function uploadContent(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'content_category' => 'required',
                'is_exchange' => 'nullable',
                'expected_amount' => 'required_if:is_exchange,0'
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }
            $user = User::find(Auth::user()->id);

            // if($user->user_type == User::TEACHER_TYPE){
            //     return response()->json(['code' => '302', 'error' => 'Teacher Can not upload Content']);
            // }

            if ($request->file('file')) {
                $allowedExt = ['pdf','docx','doc'];
                // $name = $request->file('comment_attch')->getClientOriginalName();
                $extension = $request->file('file')->getClientOriginalExtension();
                if(!in_array( $extension, $allowedExt )){
                    return sendError('Only PDF, Doc and Docx files are allowed files are allowed');
                }

                $extension = $request->file('file')->getClientOriginalExtension();
                $originalfileName = $request->file('file')->getClientOriginalName();
                $originalfileName = pathinfo($originalfileName, PATHINFO_FILENAME);
                $originalfileName = implode('-',explode(' ', $originalfileName));
                $fileName = $originalfileName."-".time().'.'.$extension;
                $path = 'app/public/'.$request->file('file')->storeAs('content',$fileName,'public');
                
                $attchObj = new Content;
                $attchObj->user_id = $user->id;
                $attchObj->content_types_id = 1; //1 for content
                $attchObj->name =$request->name;
                $attchObj->content_category = $request->content_category;
                $attchObj->path = $path;
                $attchObj->expected_amount = $request->expected_amount ?? 0;
                $attchObj->uploaded_by_admin = 0;
                $attchObj->is_approved = 0;
                $attchObj->is_exchange = $request->is_exchange ? 1 : 0;
                $attchObj->save();

                return sendResponse([], "Content Uploaded Successfully");
            }
            return response()->json(['code' => '302', 'error' => ["File"=>["The File field is required."]]]);

        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function update(Request $request, $id){
        try{
            $content =  Content::find($id);
            if($content){
                $content->update(['expected_amount' => $request->expected_amount, 'is_approved' => Content::CONTENT_PENDING, 'is_pending' => Content::CONTENT_PENDING]);
            }else{
                return sendError('Content not found');
            }
            return sendResponse('Updated Successfully',200);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
       
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{
            $content = Content::find($id);
            if($content){
                $content->delete();
            }else{
                return sendError('No record found for given Id');
            }
            return sendResponse('Deleted Successfully',200);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }


}
