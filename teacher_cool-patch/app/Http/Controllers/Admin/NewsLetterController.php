<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use App\Models\User;
use App\Models\EmailTemplate;
use App\Models\EmailHistory;
// use App\Jobs\NewsLetterJob;
use App\Models\NewsLetter;

class NewsLetterController extends Controller
{
    public function index(Request $request)
    {
        try{

            $keyword = $request->keyword;
            $page_size = ($request->page_size)? $request->page_size : 12;
            
            $data = new NewsLetter();

            if($keyword && $keyword != ''){
                $data = $data->where('title', 'like', '%'.$keyword.'%');
            }

            $data = $data->orderByDesc('created_at')
                        ->paginate($page_size);
        
            return sendResponse($data);
            
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function singleNewsLetter($id)
    {
        try{
            if($id <= 0){
                return sendError('Invalid Request');
            }
            
            $data = NewsLetter::find($id);

            if($data){
                return sendResponse($data);
            }
            return sendError('Invalid Request',[], 404);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    // public function newsletterHistory(Request $request)
    // {
    //     try{
    //         $start = $request->start;
    //         $end = $request->end;

    //         $data = EmailHistory::where('email_type', EmailTemplate::NEWSLETTER_EMAIL);
    //         if($start){
    //             $data = $data->where('created_at', '>=', $start);
    //         }
    //         if($end){
    //             $data = $data->where('created_at', '<=', $end);
    //         }

    //         $data = $data->get();
        
    //         return sendResponse($data);
            
    //     }catch (Exception $e){
    //         return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
    //     }
    // }

    public function sendNewsletterNotification(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'message' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            // $users = User::where('is_newsletter_subscriber', '=', 1)
            //             ->select(['email'])->get()->toarray();

            $cover_image = '';
            if ($request->file('cover_image')) {
                // $name = $request->file('cover_image')->getClientOriginalName();
                $extension = $request->file('cover_image')->getClientOriginalExtension();
                $originalfileName = $request->file('cover_image')->getClientOriginalName();
                $originalfileName = pathinfo($originalfileName, PATHINFO_FILENAME);
                $originalfileName = implode('-',explode(' ', $originalfileName));
                $fileName = $originalfileName."-".time().'.'.$extension;
                $cover_image = $request->file('cover_image')->storeAs('news-letter',$fileName,'public');
            }

            $data = new NewsLetter;
           
            $data->type = NewsLetter::NEWSLETTER_TYPE_SUBSCRIBED;
            $data->title = $request->title;
            $data->cover_image_path = $cover_image;
            $data->message = $request->message;
            $data->save();

            // $newsLetterData = [
            //     'users' => $users,
            //     'body' => $request->message,
            //     'subject' => $request->subject,
            // ];
            
            // dispatch(new NewsLetter($newsLetterData));

            return sendResponse([], 'Success');
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function updateNewsLetter($id, Request $request)
    {
        try{
            if($id <= 0){
                return sendError('Invalid Request');
            }
            
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'message' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $newsLetterData = NewsLetter::find($id);

            if(!$newsLetterData){
                return sendError('Invalid Request');
            }

            $cover_image = '';
            
            if ($request->file('cover_image')) {
                // $name = $request->file('cover_image')->getClientOriginalName();
                $extension = $request->file('cover_image')->getClientOriginalExtension();
                $originalfileName = $request->file('cover_image')->getClientOriginalName();
                $originalfileName = pathinfo($originalfileName, PATHINFO_FILENAME);
                $originalfileName = implode('-',explode(' ', $originalfileName));
                $fileName = $originalfileName."-".time().'.'.$extension;
                $cover_image = $request->file('cover_image')->storeAs('news-letter',$fileName,'public');

                $newsLetterData->cover_image_path = $cover_image;
            }
           
            $newsLetterData->type = NewsLetter::NEWSLETTER_TYPE_SUBSCRIBED;
            $newsLetterData->title = $request->title;
            $newsLetterData->message = $request->message;
            $newsLetterData->save();

            return sendResponse([], 'NewsLetter updated successfully');
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
            $data = NewsLetter::find($id);
            
            if($data){
                $data->delete();
            }else{
                return sendError('No record found for given Id');
            }
            return sendResponse('Deleted successfully',200);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
        
    }
}
