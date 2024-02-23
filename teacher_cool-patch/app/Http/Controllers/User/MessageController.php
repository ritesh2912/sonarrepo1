<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ChatThread;
use App\Models\Assignment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Jobs\ChatMessageEvent;
class MessageController extends Controller
{
    public function index(Request $request)
    {
        try{
            $keyword = $request->keyword;
            $sort = $request->sort;
            $page_size = ($request->page_size)? $request->page_size : 8;
    
            $fromUser = Auth::user();
    
            $data = DB::table('chat_threads')
                    ->where('sender_id', $fromUser->id)
                    ->orWhere('receiver_id', $fromUser->id)
                    ->limit($page_size)
                    ->get()->toArray();
            $chatList = [];
            for($i = 0; $i < count($data); $i++){
               
                $reciverData= null;
                if($data[$i]->sender_id == $fromUser->id){
                    $reciverData = User::find($data[$i]->receiver_id);
                }else{
                    $reciverData = User::find($data[$i]->sender_id);
                }

                if($reciverData != null){
                    $chatList[$i]['receiver_id'] = $reciverData->id;
                    $chatList[$i]['user'] = $reciverData->teacher_id_number;
                    $chatList[$i]['name'] = $reciverData->name;
                }
                $chatList[$i]['thread_id'] = $data[$i]->id;
                
            }

            $response = [
                'success' => true,
                'data'    => $chatList,
                'message' => 'Success',
                'logged_user' => $fromUser->id
            ];

            return sendResponse($response);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
        
    }

    public function allMessage(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'receiver_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }
            $user= Auth::user();

            $sender_id = $user->id;
            $receiver_id = $request->receiver_id;

            $data = DB::table('chat_messages')
               // ->where('thread_id', $request->thread_id)
                ->orWhere(function($query) use($sender_id, $receiver_id){
                    // dd($user->id);
                    $query->where('sender_id', $sender_id)
                    ->where('receiver_id', $receiver_id);
                })
                ->orWhere(function($query) use($sender_id, $receiver_id){
                    // dd($user->id);
                    $query->where('sender_id', $receiver_id)
                    ->where('receiver_id', $sender_id);
                })
                ->get()->toArray();

                $response = [
                    'success' => true,
                    'data'    => $data,
                    'message' => 'Success',
                    'logged_user' => $user->id
                ];

            return sendResponse($response);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function firstMessage(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'receiver_id' => 'required',
                'message' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }
    
            $user= Auth::user();

            $threadId = 0;
            $data = DB::table('chat_threads')
                    ->where('sender_id', $user->id)
                    ->where('receiver_id', $request->receiver_id)
                    ->first();
            // dd($data);
            if($data){
                $threadId = $data->id;
            }else{
                $thradObj = new ChatThread;
                $thradObj->sender_id = $user->id;
                $thradObj->receiver_id = $request->receiver_id;
                $thradObj->save();

                $threadId = $thradObj->id;
            }

            $chatData['thread_id'] = $threadId;
            $chatData['sender_id'] = $user->id;
            $chatData['receiver_id'] = $request->receiver_id;
            $chatData['message'] = $request->message;
            $chatData['created_at'] = date('Y-m-d h:i:s');
            $chatData['updated_at'] = date('Y-m-d h:i:s');
            
            DB::table('chat_messages')->insert($chatData);

            

            return sendResponse("Success");
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
        
    }

    public function findUser(Request $request)
    {
        try{
            // $validator = Validator::make($request->all(), [
            //     'receiver_id' => 'required',
            //     'message' => 'required',
            // ]);

            // if ($validator->fails()) {
            //     return response()->json(['code' => '302', 'error' => $validator->errors()]);
            // }
    
            $user= Auth::user();

            $data = DB::table('assignments')
                    ->join('users as teacher','teacher.id','=','assignments.teacher_id')
                    ->where('assignments.user_id', $user->id)
                    ->select('teacher.id as teacher_id','teacher.name as teacher_name')
                    ->groupBy('assignments.teacher_id')
                    ->get()->toArray();

            return sendResponse($data);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
        
    }
    

    // public function message(Request $request)
    // {
    //     try{
    //         $validator = Validator::make($request->all(), [
    //             'to_id' => 'required',
    //             'thread_id' => 'required',
    //             'message' => 'required',
    //         ]);
    
    //         if ($validator->fails()) {
    //             return response()->json(['code' => '302', 'error' => $validator->errors()]);
    //         }

    //         $data['thread_id'] = $request->thread_id;
    //         $data['to_id'] = $request->to_id;
    //         $data['message'] = $request->message;
            
    //         DB::table('chat_messages')->insert($data);
    //         // dispatch(new ChatMessageEvent($data))->afterResponse();

    //         return sendResponse("Success");
    //     }catch (Exception $e){
    //         return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
    //     }
    // }
}