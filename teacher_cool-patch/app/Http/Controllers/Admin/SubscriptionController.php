<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\SystemSetting;
use Exception;
use App\Models\Subscription;


class SubscriptionController extends Controller
{

    public function index(Request $request)
    {
        try{
            $data = SubscriptionPlan::get();
            
            $response = [
                'success' => true,
                'data'    => $data,
                'message' => 'Success',
                'subscriptions_category' => Subscription::select('name', 'id')->get(),
            ];
        
            return response()->json($response, 200);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    // public function addSubscription(Request $request)
    // {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'subscription_id' => 'required',
    //             'name' => 'required|min:3',
    //             'duration' => 'required',
    //             'assignment_request' => 'required',
    //             'file_download' => 'required',
    //             'price' => 'required',
    //         ]);
    //         if ($validator->fails()) {
    //             return response()->json(['code' => '302', 'error' => $validator->errors()]);
    //         }

    //         $data = new SubscriptionPlan;
    //         $data->subscription_id = $request->subscription_id;
    //         $data->name = $request->name;
    //         $data->duration = $request->duration*30;
    //         $data->assignment_request = $request->assignment_request;
    //         $data->file_download =$request->file_download;
    //         $data->price =$request->price;
    //         $data->is_active = Admin::IS_ACTIVE;
    //         $data->save();
           
    //         return sendResponse($data, 'Subscription Plan Created');
    //     } catch (Exception $e) {
    //         return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
    //     }
    // }

    public function editSubscription(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'name' => 'required|min:3',
                'duration' => 'required',
                'assignment_request' => 'required',
                'file_download' => 'required',
                'price' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $data = SubscriptionPlan::find($request->id);
            $data->name = $request->name;
            $data->duration = $request->duration;
            $data->duration_days = $request->duration*30;
            $data->assignment_request = $request->assignment_request;
            $data->file_download = $request->file_download;
            $data->price =$request->price;
            $data->is_active = $request->is_active;
            $data->save();
           
            return sendResponse($data, 'Subscription Plan Updated');
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function subscriptionDetail($id)
    {
        try{
            $data = SubscriptionPlan::find($id);

            if(!$data){
                return sendError("Data Not Found", [], 404);
            }
            
            $response = [
                'success' => true,
                'data'    => $data,
                'message' => 'Success',
                'setting' => SystemSetting::select('word_per_assignment')->first(),
                'subscriptions_category' => Subscription::get(),
            ];
        
            return response()->json($response, 200);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }
}
