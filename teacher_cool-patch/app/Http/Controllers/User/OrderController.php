<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use App\Models\Order;
// use App\Models\SubscriptionPlan;
// use App\Models\SubscribedUser;
// use App\Models\User;
// use App\Models\Reward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        try{
            $keyword = $request->keyword;
            $page_size = ($request->page_size)? $request->page_size : 10;
            $payment_status = ($request->payment_status)? $request->payment_status : false;
            $sort = $request->sort;

            $user = Auth::user();
            
            $data = DB::table('users')
                ->join('orders', 'orders.user_id', '=', 'users.id')
                ->leftJoin('subscription_plans', 'orders.subscription_plan_id', '=', 'subscription_plans.id')
                ->leftJoin('subscribed_user_details', 'orders.order_id', '=', 'subscribed_user_details.order_id')
                ->select('orders.*', 'users.email as user_email','subscription_plans.name as subscription_name','subscribed_user_details.subscription_expire_date')
                ->where('users.id', $user->id);
            if($keyword && $keyword != ''){
                $data = $data->where(function($query) use ($keyword){
                            $query->where('subscription_plans.name', 'like', '%'.$keyword.'%')
                            // ->orWhere('users.order_id', 'like', '%'.$keyword.'%')
                            ->orWhere('orders.order_id', 'like', '%'.$keyword.'%');
                        });
            }
            
            if($payment_status){
                $data = $data->where('orders.payment_status', $payment_status);
            }
            
            if($sort == 'asc'){
                $data = $data->orderBy('orders.created_at');
            }else{
                $data = $data->orderByDesc('orders.created_at');
            }
            $data = $data->paginate($page_size);
    
            return sendResponse($data);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }
    
    // public function placeOrder(Request $request)
    // {
    //     try{
    //         $validator = Validator::make($request->all(), [
    //             'total_amount' => 'required',
    //             'net_amount' => 'required',
    //             'discount' => 'required',
    //             'order_type' =>  'required',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json(['code' => '302', 'error' => $validator->errors()]);
    //         }

    //         if($request->order_type == Order::OTHER_ORDER_TYPE && $request->content_id == null){
    //             return response()->json(['code' => '302', 'error' => 'Content ID Plan is Required']);
    //         }

    //         if($request->order_type == Order::SUBSCRIPTION_ORDER_TYPE && $request->subscription_plan_id == null){
    //             return response()->json(['code' => '302', 'error' => 'Subscription Plan is Required']);
    //         }

    //         // Check Subscription Plan
    //         if($request->order_type == Order::SUBSCRIPTION_ORDER_TYPE){
    //             $planData = SubscriptionPlan::find($request->subscription_plan_id);
    //             if($planData == null || $planData->is_active == 0){
    //                 return response()->json(['code' => '302', 'error' => 'Invalid Subscription Plan']);
    //             }
    //         }
            
            
    //         $user = Auth::user();
            
    //         $orderObj = new Order;
    //         $orderObj->order_id = "ORD-".time();
    //         $orderObj->user_id = $user->id;
    //         $orderObj->order_type = $request->order_type;
    //         $orderObj->total_amount =$request->total_amount;
    //         $orderObj->net_amount =$request->net_amount;
    //         $orderObj->discount = $request->discount;
    //         $orderObj->payment_status = Order::ORDER_PAYMENT_PENDING;
    //         if($request->order_type == Order::OTHER_ORDER_TYPE){
    //             $orderObj->content_id = $request->content_id;
    //         }else{
    //             $orderObj->subscription_plan_id = $request->subscription_plan_id;
    //         }
    //         $orderObj->save();

    //         return sendResponse([], "Order Placed Successfully");

    //     }catch (Exception $e){
    //         return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
    //     }
    // }

    

    // public function changeOrderStatus(Request $request)
    // {
    //     $data = Order::find($request->order_id);
    //     if(empty($data) || $data->payment_status == Order::ORDER_PAYMENT_PAID){
    //         return response()->json(['code' => '302', 'error' => 'Invalid Order']);
    //     }

    //     // Condition to Be add
    //     if(true){
    //         $data->payment_status = Order::ORDER_PAYMENT_PAID;
    //         $data->save();

    //         if($data->order_type == Order::SUBSCRIPTION_ORDER_TYPE){
    //             // Check Valid Subscription plan
    //             $planData = SubscriptionPlan::find($data->subscription_plan_id);
    //             $expireDate = $this->calculateExpireDate($planData->duration);
    
    //             $obj = new SubscribedUser;
    //             $obj->name = $planData->name;
    //             $obj->order_id = $data->order_id;
    //             $obj->user_id = $data->user_id;
    //             $obj->subscription_plan_id = $data->subscription_plan_id;
    //             $obj->expire_date = $expireDate;
    //             $obj->save();

    //             $user = User::find($data->user_id);
    //             if($user->reffer_user_id != null && $user->reffer_user_id != 0){
    //                 $rewardObj = new Reward;
    //                 $rewardObj->user_id = $user->reffer_user_id;
    //                 $rewardObj->points = 1;
    //                 $rewardObj->transection_type = Reward::REWARD_CREDIT;
    //                 $rewardObj->reward_type = Reward::REFFER_REWARD_TYPE;
    //                 $rewardObj->save();

    //                 $user->reffer_user_id = null;
    //                 $user->save();
    //             }
    //             return sendResponse([], "Order Successfull");
    //         }
    //     }
    //     return sendResponse([], "Order Not Successfull");
    // }
    
    // public function calculateExpireDate($days)
    // {
    //     // Declare a date
    //     $date = date("Y-m-d");
    //     // Display the added date
    //     return date('Y-m-d', strtotime($date ." +".$days." days"));
    // }


    public function singleOrder($id, Request $request)
    {
        try{
            if($id <= 0){
                return sendError('Invalid Request');
            }

            $user = Auth::user();

            $data = DB::table('users')
                ->join('orders', 'orders.user_id', '=', 'users.id')
                ->leftJoin('subscription_plans', 'orders.subscription_plan_id', '=', 'subscription_plans.id')
                ->leftJoin('subscribed_user_details', 'orders.order_id', '=', 'subscribed_user_details.order_id')
                ->select('orders.*', 'users.email as user_email','subscription_plans.name as subscription_name','subscribed_user_details.subscription_expire_date')
                ->where('users.id', $user->id)
                ->where('orders.id', $id)
                ->get();

            return sendResponse($data);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }
}
