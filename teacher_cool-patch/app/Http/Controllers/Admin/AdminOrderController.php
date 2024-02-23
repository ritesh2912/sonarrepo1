<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Support\Facades\DB;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        try{
            $keyword = $request->keyword;
            $user_type = $request->user_type;
            $page_size = ($request->page_size)? $request->page_size : 10;
            $payment_status = ($request->is_paid)? $request->is_paid : false;
            $sort = $request->sort;
            
            $data = DB::table('orders')
                ->join('users', 'orders.user_id', '=', 'users.id')
                ->leftJoin('subscription_plans', 'orders.subscription_plan_id', '=', 'subscription_plans.id')
                ->select('orders.*', 'users.name as user_name', 'users.email as user_email','users.profile_path',
                'users.profile_path','users.user_type','subscription_plans.name as subscription_name');
            if($keyword && $keyword != ''){
                
                $data = $data->where(function($query) use ($keyword){
                            $query->where('users.name', 'like', '%'.$keyword.'%')
                            ->orWhere('users.email', 'like', '%'.$keyword.'%')
                            // ->orWhere('subscription_name', 'like', '%'.$keyword.'%')
                            ->orWhere('orders.order_id', 'like', '%'.$keyword.'%');
                        });
            }

            if($user_type){
                $data = $data->where('users.user_type', $user_type);
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

    public function orderDetail($id)
    {
        try{
            $data = DB::table('orders')
                ->leftJoin('users', 'orders.user_id', '=', 'users.id')
                ->leftJoin('subscription_plans', 'orders.subscription_plan_id', '=', 'subscription_plans.id')
                ->select('orders.*', 'users.name as user_name', 'users.email as user_email','users.profile_path',
                'users.profile_path','users.user_type','subscription_plans.name as subscription_name')
                ->where('orders.id', '=', $id)
                ->first();
    
            return sendResponse($data);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }
}
