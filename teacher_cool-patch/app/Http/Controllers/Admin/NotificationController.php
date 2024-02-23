<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\NotificationModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Exception;
use App\Notifications\SystemNotification;
use App\Notifications\EmailNotification;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        
        try{
            $page_size = $request->page_size ? $request->page_size : 10;
            $keyword = $request->keyword;
            $status = $request->status;

            $data = DB::table('notifications')
                ->join('users', 'users.id', '=', 'notifications.notifiable_id')
                ->selectRaw('notifications.*, users.name as first_name, users.last_name as last_name,users.user_type,(CASE 
                WHEN users.user_type = "1" THEN "Teacher" 
                ELSE "Student" 
                END) AS user_type_name');
            if($keyword && $keyword != ''){
                $data = $data->where(function($query) use ($keyword){
                    $query->where('users.name', 'like', '%'.$keyword.'%')
                    ->orWhere('users.last_name', 'like', '%'.$keyword.'%')
                    ->orWhere('notifications.data->title', 'like', '%'.$keyword.'%');
                });
            }
            if($status == 1){
                $data = $data->where('read_at', null);
            }elseif($status == 2){
                $data = $data->where('read_at', '!=', null);
            }
            $data = $data->orderBy('notifications.created_at')
                ->paginate($page_size);

            if(!$data){
                return sendError('No record Found');
            }

            $response = [
                'success' => true,
                'data'    => $data,
                'message' => 'Success',
            ];
            return response()->json($response, 200);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }
    
    public function addNotification(Request $request)
    {
        try{
         
            $validator = Validator::make($request->all(), [
                'notify_to' => 'required',
                'notification_type' => 'required',
                'title' => 'required',
                'message' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $userData  = User::where('is_active', 1)
                        ->where('email_verified_at', '!=', null);
            
            if($request->notify_to == NotificationModel::NOTIFY_TO_TEACHER){
                $userData = $userData->where('user_type', User::TEACHER_TYPE);
            }else if($request->notify_to == NotificationModel::NOTIFY_TO_STUDENT){
                $userData = $userData->where('user_type', User::STUDENT_TYPE);
            }

            $userData = $userData->get();

            $data = [
                'title' => $request->title,
                'message' => $request->message
            ];

            if($request->notification_type == NotificationModel::PUSH_NOTIFICATION){
                Notification::sendNow($userData, new SystemNotification($data));
            }elseif($request->notification_type == NotificationModel::EMAIL_NOTIFICATION){
                Notification::send($userData, new EmailNotification($data));
            }
            return sendResponse("Notification Sent Successfully");
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }

    }
}
