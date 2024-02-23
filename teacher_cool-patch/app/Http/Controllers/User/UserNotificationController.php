<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UserNotificationController extends Controller
{
    public function index(Request $request)
    {
        try{
            $user = Auth::user();
            $notificationData = [];
            $i = 0;
            foreach ($user->notifications as $notification) {
                if($notification->data){
                    $notificationData[$i]["data"] = $notification->data;
                    $notificationData[$i]["id"] = $notification->id;
                    $notificationData[$i]["read_at"] = $notification->read_at;
                    $notificationData[$i]["created_at"] = $notification->created_at;
                    $i++;
                    // $notification->markAsRead();
                }
            }
            $user->unreadNotifications->markAsRead();
            return sendResponse($notificationData);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function notification($id)
    {
        try{
            // $user = Auth::user();
            // $notificationData = [];
            // $i = 0;
            // foreach ($user->notifications as $notification) {
            //     if($notification->data){
            //         // $notification->markAsRead();
            //         $notificationData[$i]["data"] = $notification->data;
            //         $notificationData[$i]["id"] = $notification->id;
            //         $notificationData[$i]["read_at"] = $notification->read_at;
            //         $i++;
            //     }
            // }
            // return sendResponse($notificationData);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }
}
