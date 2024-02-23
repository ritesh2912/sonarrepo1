<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\SubscribedUserDetail;
use App\Models\Subscription;
use App\Models\Order;
use App\Models\TeacherWallet;
use App\Models\Assignment;
use App\Models\Reward;
use App\Models\SystemSetting;
use Category;
use App\Models\Content;
use App\Models\UserDownload;
use Carbon\Carbon;
use App\Models\CurrencyExchange;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        try {
            $data = DB::table('subscriptions')
                ->join('subscription_plans', 'subscriptions.id', 'subscription_plans.subscription_id')
                ->select('subscriptions.name as plan_type', 'subscriptions.is_platinum', 'subscription_plans.*', 'subscription_plans.id as subscription_plan_id')
                ->get()->toArray();
            $result = $this->dataArrange($data);
            
            $response = [
                'success' => true,
                'data'    => $result,
                'message' => 'Success',
            ];

            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function loggedInPlans(Request $request)
    {
        try {
            $user = Auth::user();

            $data = DB::table('subscriptions')
                ->leftJoin('subscription_plans', 'subscriptions.id', 'subscription_plans.subscription_id')
                ->select('subscriptions.name as plan_type', 'subscriptions.is_platinum', 'subscription_plans.*', 'subscription_plans.id as subscription_plan_id')
                ->get()->toArray();
            $result = $this->dataArrange($data);

            $buy_new_plan = 0;
            $subscribeUser = SubscribedUserDetail::where('user_id', $user->id)->first();

            if ($subscribeUser == null) {
                $buy_new_plan = 1;
            }
            if ($subscribeUser != null) {
                if ($subscribeUser->subscription_expire_date < date("Y-m-d") || $subscribeUser->file_download < 1 || $subscribeUser->assignment_request < 1) {
                    $buy_new_plan = 1;
                }
            }

            $response = [
                'success' => true,
                'data'    => $result,
                'message' => 'Success',
                'user_subscription_plan_id' => isset($subscribeUser->subscription_plan_id) ? $subscribeUser->subscription_plan_id : null,
                'can_buy_plan' => $buy_new_plan,
            ];
            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function checkout(Request $request)
    {
        
        try {
            $validator = Validator::make($request->all(), [
                'order_type' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }            
            $user = Auth::user();
            // dd($user->user_details->currency);
            $currency = $user->user_details->currency;
            $exchangeData = CurrencyExchange::where('currency',$currency)->first();
            $exchange_rate = (float)$exchangeData->exchange_rate;
            
            $result = [];
            if($request->order_type == Order::SUBSCRIPTION_ORDER_TYPE){
                if($request->subscription_id == null || $request->subscription_id <= 0){
                    return sendError('Subscription Id is required');
                }
                $subscribeUser = SubscribedUserDetail::where('user_id', $user->id)->first();

                if ($subscribeUser != null) {
                    if ($subscribeUser->subscription_expire_date > date("Y-m-d") && $subscribeUser->file_download > 0 && $subscribeUser->assignment_request > 0) {
                        return sendError('Subscription is already active');
                    }
                }
                $data = DB::table('subscriptions')
                    ->join('subscription_plans', 'subscriptions.id', 'subscription_plans.subscription_id')
                    ->select('subscriptions.name as plan_type', 'subscriptions.is_platinum', 'subscription_plans.*', 'subscription_plans.id as subscription_plan_id')
                    ->where('subscription_plans.id', $request->subscription_id)
                    ->get()->toArray();
    
                $result = $this->dataArrange($data)[0];
                $result['total_amount'] = $result['price'];
            }elseif($request->order_type == Order::SINGLE_ASSINGMENT_ORDER_TYPE){
                
                if($request->assignment_id == null || $request->assignment_id <= 0){
                    return sendError('Assingment Id is required');
                }

                $assignmentData = Assignment::find($request->assignment_id);
                if($assignmentData == null){
                    return sendError('Invalid Request');
                }
                
                $result['assignment_id'] = $assignmentData->assignment_id;
                $result['question'] = $assignmentData->question;

                if($assignmentData->category == Category::CONTENT_CATEGORY_IT){
                    $systemSetting = SystemSetting::first();
                    $result['total_amount'] = round($systemSetting->hourly_rate_it_coding * $assignmentData->assignment_hours);
                }else{
                    // $result['per_word_rate'] = round($this->calculatePerWordRate() * $exchange_rate, 2);
                    $result['per_word_rate'] = $this->calculatePerWordRate();
                    $result['word_count'] = $assignmentData->word_count;
                    $result['total_amount'] = round($assignmentData->word_count*$result['per_word_rate'], 2);
                }
                $result['total_amount'] = round($result['total_amount']* $exchange_rate, 2);
                
            }elseif($request->order_type == Order::CONTENT_PURCHASE_ORDER_TYPE){
                $contentData = Content::find($request->content_id);
                if($contentData == null){
                    return sendError('Invalid Request');
                }

                // $result['per_word_rate'] = round($this->calculatePerWordRate() * $exchange_rate, 2);
                $result['per_word_rate'] = $this->calculatePerWordRate();
                $result['word_count'] = $contentData->word_count;
                $result['total_amount'] = round($contentData->word_count * $result['per_word_rate'], 2);
                $result['total_amount'] = round($result['total_amount']* $exchange_rate, 2);
            }
            // $result['total_amount'] = $result['total_amount'];

            if($currency == 'INR'){
                $result['tax'] = round(($result['total_amount']/100)*18, 2);
            }elseif($currency == 'USD'){
                $result['tax'] = round(($result['total_amount']/100)*18, 2);
            }elseif($currency == 'GBP'){
                $result['tax'] = round(($result['total_amount']/100)*18, 2);
            }elseif($currency == 'EUR'){
                $result['tax'] = round(($result['total_amount']/100)*18, 2);
            }
            
            $result['rewards'] = $this->getRewards($user->id, $exchange_rate);
            
            $response = [
                'success' => true,
                'data'    => $result,
                'message' => 'Success',
            ];

            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function proceedPay(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'order_type' => 'required',
                'rewards' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }
            $user = Auth::user();
            $currency = $user->user_details->currency;

            $exchangeData = CurrencyExchange::where('currency',$currency)->first();
            $exchange_rate = (float)$exchangeData->exchange_rate;

            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

            if($request->order_type == Order::SUBSCRIPTION_ORDER_TYPE){   

                if($request->subscription_id == null || $request->subscription_id <= 0){
                    return sendError('Subscription Id is required');
                }
                $data['plan'] = SubscriptionPlan::find($request->subscription_id);

                if ($data['plan'] == null) {
                    return sendError('Inavlid Request');
                }

                if ($data['plan']->price == '' || $data['plan']->price == 0) {
                    return sendError('Something went wrong');
                }

                $subscribeUser = SubscribedUserDetail::where('user_id', $user->id)->first();

                if ($subscribeUser != null) {
                    if ($subscribeUser->subscription_expire_date > date("Y-m-d") && $subscribeUser->file_download > 0 && $subscribeUser->assignment_request > 0) {
                        return sendError('Subscription is already active');
                    }
                }

                
                $product = $stripe->products->create([
                    'name' => $data['plan']->name,
                    'description' => $data['plan']->name,
                ]);

                $itemAmount = $data['plan']->price;
                
            }elseif($request->order_type == Order::SINGLE_ASSINGMENT_ORDER_TYPE){
                if($request->assignment_id == null || $request->assignment_id <= 0){
                    return sendError('Assingment Id is required');
                }

                $assignmentData = Assignment::find($request->assignment_id);
                if($assignmentData == null){
                    return sendError('Invalid Request');
                }

                
                if($assignmentData->category == Category::CONTENT_CATEGORY_IT){
                    $systemSetting = SystemSetting::first();
                    $itemAmount = $systemSetting->hourly_rate_it_coding * $assignmentData->assignment_hours;
                }else{
                    $perWordRate = $this->calculatePerWordRate();
                    $itemAmount = $assignmentData->word_count * $perWordRate;
                }

                $product = $stripe->products->create([
                    'name' => $assignmentData->assignment_id,
                    'description' => $assignmentData->assignment_id,
                ]);

            }elseif($request->order_type == Order::CONTENT_PURCHASE_ORDER_TYPE){

                if($request->content_id == null || $request->content_id <= 0){
                    return sendError('Assingment Id is required');
                }

                $contentData = Content::with('content_paid_orders','is_downloaded')->find($request->content_id);
                if($contentData == null){
                    return sendError('Invalid Request');
                }

                $perWordRate = $this->calculatePerWordRate();
                $itemAmount = $contentData->word_count * $perWordRate;
                $product = $stripe->products->create([
                    'name' => $contentData->name,
                    'description' => $contentData->name,
                ]);
            }
            $original_amount = (float)$itemAmount;
            $itemAmount = round($exchange_rate*$itemAmount, 2);

           
            
            // Apply Taxes
            $tax = 0;
            $original_tax = 0;
            if($currency == 'INR'){
                $tax = round(($itemAmount/100)*18, 2);
                $original_tax = round(($original_amount/100)*18, 2);
            }elseif($currency == 'USD'){
                $tax = round(($itemAmount/100)*18, 2);
                $original_tax = round(($original_amount/100)*18, 2);
            }
            elseif($currency == 'GBP'){
                $tax = round(($itemAmount/100)*18, 2);
                $original_tax = round(($original_amount/100)*18, 2);
            }
            elseif($currency == 'EUR'){
                $tax = round(($itemAmount/100)*18, 2);
                $original_tax = round(($original_amount/100)*18, 2);
            }
            // print_r($tax); die;
            $itemAmount = round($itemAmount + $tax, 2);

            $discount = 0;
            if($request->rewards){
                $rewardAmount = $this->getRewards($user->id, $exchange_rate);
                
                // Amount After Discount
                if($rewardAmount > 0){
                    $discountResult = $this->applyDiscount($rewardAmount, $itemAmount, $user->id);
                    $itemAmount = $discountResult['amount'];
                    $discount = $discountResult['discount'];
                }
            }
            
           
            $price = $stripe->prices->create([
                'unit_amount' => $itemAmount * 100,
                'currency' => $currency,
                'product' => $product['id'],
            ]);
            // print_r($itemAmount); die;
            $checkout_session = \Stripe\Checkout\Session::create([
                'line_items' => [[
                    # Provide the exact Price ID (e.g. pr_1234) of the product you want to sell
                    'price' => $price->id,
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => env('APP_URL_FRONT') . '/payment-callback?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => env('APP_URL_FRONT') . '/payment-callback?canceled=true',
            ]);
            
            if (!$checkout_session) {
                sendError('Inavlid Request', 401);
            }

            $perOrderData = null;
            if(isset($request->order_id)){
                $perOrderData = Order::find($request->order_id);
            }
            

            // Check Existing Order
            if($perOrderData){
                if($perOrderData->payment_status != Order::ORDER_PAYMENT_PENDING){
                    return sendError('Invalid Order');
                }

                $perOrderData->total_amount = $itemAmount;
                $perOrderData->total_amount_inr = round($original_amount + (float)$original_tax, 2);
                $perOrderData->checkout_session_id = $checkout_session->id;
                $perOrderData->save();
                
            }else{
                // Create New Order
                $orderData = new Order;
                $orderData->order_id = 'ORD-' . time();
                $orderData->user_id = $user->id;
                $orderData->checkout_session_id = $checkout_session->id;
                if($request->order_type == Order::SUBSCRIPTION_ORDER_TYPE){
                    $orderData->subscription_plan_id = $request->subscription_id;
                    $orderData->order_type = Order::SUBSCRIPTION_ORDER_TYPE;
                }elseif($request->order_type == Order::SINGLE_ASSINGMENT_ORDER_TYPE){
                    $orderData->order_type = Order::SINGLE_ASSINGMENT_ORDER_TYPE;
                    $orderData->assignment_id = $assignmentData->id;
                }elseif($request->order_type == Order::CONTENT_PURCHASE_ORDER_TYPE){
                    $orderData->order_type = Order::CONTENT_PURCHASE_ORDER_TYPE;
                    $orderData->content_id = $request->content_id;
                }
                $orderData->discount = $discount;
                $orderData->tax = $tax;
                $orderData->payment_status = Order::ORDER_PAYMENT_PENDING;
                $orderData->total_amount = $itemAmount;
                $orderData->total_amount_inr = round($original_amount + (float)$original_tax, 2);
                $orderData->currency = $currency;
                $orderData->net_amount = $itemAmount;
                $orderData->save(); 
            }
                      
            
            return sendResponse(['url' => $checkout_session->url]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function paymentCallback(Request $request)
    {
        $orderData = Order::where('checkout_session_id', $request->session_id)->first();
        
        if (!$orderData) {
            return response()->json(['code' => '302', 'error' => 'Invalid Order']);
            // return view('payment', ['payment' => false]);
        }
        
        if ($orderData->payment_status == Order::ORDER_PAYMENT_PAID) {
            // return response()->json(['code' => '302', 'error' => 'Invalid Order']);
            // return view('payment', ['payment' => true]);
            return sendResponse([]);
        }
        
        if (isset($request->canceled) && $request->canceled && $orderData->payment_status == Order::ORDER_PAYMENT_PENDING) {
            $orderData->payment_status = Order::ORDER_PAYMENT_FAILED;
            $orderData->save();
            // return view('payment', ['payment' => false]);
            return response()->json(['code' => '302', 'error' => '']);
        }
        

        // Condition to Be add
        if ($orderData->payment_status == Order::ORDER_PAYMENT_PENDING) {

            $result = [];
            if ($orderData->order_type == Order::SUBSCRIPTION_ORDER_TYPE) {
                
                // Check Valid Subscription plan;
                $planData = DB::table('subscription_plans')
                ->join('subscriptions', 'subscriptions.id','=','subscription_plans.subscription_id')
                ->where('subscription_plans.id', $orderData->subscription_plan_id)
                ->select('subscription_plans.*', 'subscriptions.name as subscription_plan_name')
                ->first();
                                

                $expireDate = $this->calculateExpireDate($planData->duration_days);

                $userSubscription['subscription_name'] = $planData->subscription_plan_name;
                $userSubscription['order_id'] = $orderData->order_id;
                $userSubscription['subscription_plan_id'] = $orderData->subscription_plan_id;
                $userSubscription['subscription_expire_date'] = $expireDate;
                $userSubscription['file_download'] = $planData->file_download;
                $userSubscription['assignment_request'] = $planData->assignment_request;

                $obj = SubscribedUserDetail::updateOrCreate(
                    ['user_id' => $orderData->user_id],
                    $userSubscription
                );
                $obj->save();

                
            }elseif($orderData->order_type == Order::SINGLE_ASSINGMENT_ORDER_TYPE){
                
                $assignmentData = Assignment::find($orderData->assignment_id);
                // dd($assignmentData);
                $assignmentData->is_paid_for_assignment = 1;
                $assignmentData->save();
                
            }elseif($orderData->order_type == Order::CONTENT_PURCHASE_ORDER_TYPE){
                $contentData = Content::find($orderData->content_id);
                if($contentData == null){
                    return sendError("Data not found", [], 404);
                }

                $user_download = new UserDownload;
                $user_download->user_id = $orderData->user_id;
                $user_download->content_id = $orderData->content_id;
                $user_download->path = $contentData->path;
                $user_download->save();

                $result['url'] = $contentData->path;


                if($contentData->paid_to_seller == 0){
                    //Add wallet or rewards for seller
                    $this->addSellerPayment($orderData->content_id);
                }
                
            }

            $user = User::find($orderData->user_id);
            
            if ($user->reffer_user_id != null && $user->reffer_user_id != 0) {
                $rewardObj = new Reward;
                $rewardObj->user_id = $user->reffer_user_id;
                $rewardObj->points = 1;
                $rewardObj->transection_type = Reward::REWARD_CREDIT;
                $rewardObj->reward_type = Reward::REFFER_REWARD_TYPE;
                $rewardObj->save();

                $user->reffer_user_id = null;
            }
            $user->save();

            $orderData->payment_status = Order::ORDER_PAYMENT_PAID;
            $orderData->save();
            
            // return view('payment', ['payment' => true, 'result' => $result]);
            return sendResponse([]);
        }
        // return view('payment', ['payment' => false]);
        return sendResponse([]);
    }

    public function calculateExpireDate($days)
    {
        // Declare a date
        $date = date("Y-m-d");
        // Display the added date
        return date('Y-m-d', strtotime($date . " +" . $days . " days"));
    }

    public function dataArrange($data)
    {
        $user = Auth::user();
        
        $currency = 'INR';
        if($user){
            $currency = ($user->user_details) ? $user->user_details->currency : 'INR';
        }
        
        $exchangeData = CurrencyExchange::where('currency',$currency)->first();
        $exchange_rate = 1;
        if($exchangeData){
            $exchange_rate = (float)$exchangeData->exchange_rate;
        }
        
        for ($i = 0; $i < count($data); $i++) {    
            $data[$i] = (array) $data[$i];
            $data[$i]['price'] = round((float)$exchange_rate*$data[$i]['price'], 2);
            $data[$i]['services'] = [];
            array_push($data[$i]['services'], 'Unlimited Browsing');
            array_push($data[$i]['services'],  $data[$i]['file_download'] . ' file download free of cost (5000 words per file)');
            array_push($data[$i]['services'], $data[$i]['assignment_request'] . ' assignment request (5000 words per file)');
            if ($data[$i]['is_platinum'] == 1) {
                array_push($data[$i]['services'], 'Direct access to tutor');
                array_push($data[$i]['services'], 'Unlimited assignment correction access');
                array_push($data[$i]['services'], 'No extra charge for urgent help/assignment');
            }
        }
        return $data;
    }

    public function webhookCallback()
    {
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

        // This is your Stripe CLI webhook secret for testing your endpoint locally.
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');


        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sig_header, $endpoint_secret
        );
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            return sendError('', [],400);
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            return sendError('', [],400);
        }


        // Handle the event
        $result = [];
        switch ($event->type) {
            case 'checkout.session.async_payment_failed':
                $session = $event->data->object;
                $result = $this->failedPaymentStatus($session);
                break;
              case 'checkout.session.async_payment_succeeded':
                $session = $event->data->object;
                $result = $this->savePaymentStatus($session);
                break;
              case 'checkout.session.completed':
                $session = $event->data->object;
                $result = $this->savePaymentStatus($session);
                break;
              case 'checkout.session.expired':
                $session = $event->data->object;
                // ... handle other event types
            default:
                echo 'Received unknown event type ' . $event->type;
        }
        if(isset($result['status'])){
            return sendError('', [], $result['status']);
        }

        return sendResponse('');
    }

    public function savePaymentStatus($paymentObject){
        $data = Order::where('checkout_session_id', $paymentObject->id)->first();
        if (!$data) {
            // return response()->json(['code' => '302', 'error' => '']);
            return['status' => 500];
        }

        // Condition to Be add
        if ($data->payment_status == Order::ORDER_PAYMENT_PENDING) {
            $data->payment_status = Order::ORDER_PAYMENT_PAID;
            $data->save();

            if ($data->order_type == Order::SUBSCRIPTION_ORDER_TYPE) {
                $planData = DB::table('subscription_plans')
                                ->join('subscriptions', 'subscriptions.id','=','subscription_plans.subscription_id')
                                ->where('subscription_plans.id', $data->subscription_plan_id)
                                ->select('subscription_plans.*', 'subscriptions.name as subscription_plan_name')
                                ->first();

                $expireDate = $this->calculateExpireDate($planData->duration_days);

                $userSubscription['subscription_name'] = $planData->subscription_plan_name;
                $userSubscription['order_id'] = $data->order_id;
                $userSubscription['subscription_plan_id'] = $data->subscription_plan_id;
                $userSubscription['subscription_expire_date'] = $expireDate;
                $userSubscription['file_download'] = $planData->file_download;
                $userSubscription['assignment_request'] = $planData->assignment_request;

                $obj = SubscribedUserDetail::updateOrCreate(
                    ['user_id' => $data->user_id],
                    $userSubscription
                );
                $obj->save();

                // $user = User::find($data->user_id);
                // if ($user->reffer_user_id != null && $user->reffer_user_id != 0) {
                //     $rewardObj = new Reward;
                //     $rewardObj->user_id = $user->reffer_user_id;
                //     $rewardObj->points = 1;
                //     $rewardObj->transection_type = Reward::REWARD_CREDIT;
                //     $rewardObj->reward_type = Reward::REFFER_REWARD_TYPE;
                //     $rewardObj->save();

                //     $user->reffer_user_id = null;
                // }
                // $user->save();
                
            }elseif($data->order_type == Order::SINGLE_ASSINGMENT_ORDER_TYPE){
                
                $assignmentData = Assignment::find($data->assignment_id);
                // dd($assignmentData);
                $assignmentData->is_paid_for_assignment = 1;
                $assignmentData->save();
                
            }elseif($data->order_type == Order::CONTENT_PURCHASE_ORDER_TYPE){
                $contentData = Content::find($data->content_id);
                if($contentData == null){
                    return sendError("Data not found", [], 404);
                }

                $user_download = new UserDownload;
                $user_download->user_id = $data->user_id;
                $user_download->content_id = $data->content_id;
                $user_download->path = $contentData->path;
                $user_download->save();

                $result['url'] = $contentData->path;


                if($contentData->paid_to_seller == 0){
                    //Add wallet or rewards for seller
                    $this->addSellerPayment($data->content_id);
                }
                
            }

            $user = User::find($data->user_id);
            
            if ($user->reffer_user_id != null && $user->reffer_user_id != 0) {
                $rewardObj = new Reward;
                $rewardObj->user_id = $user->reffer_user_id;
                $rewardObj->points = 1;
                $rewardObj->transection_type = Reward::REWARD_CREDIT;
                $rewardObj->reward_type = Reward::REFFER_REWARD_TYPE;
                $rewardObj->save();

                $user->reffer_user_id = null;
            }
            $user->save();

            $data->payment_status = Order::ORDER_PAYMENT_PAID;
            $data->save();

        }
        return['status' => 200];
    }

    public function failedPaymentStatus($checkoutObject)
    {
        $data = Order::where('checkout_session_id', $checkoutObject->id)->first();
        if (!$data) {
            // return response()->json(['code' => '302', 'error' => '']);
            return['status' => 500];
        }

        if ( $data->payment_status == Order::ORDER_PAYMENT_PENDING) {
            $data->payment_status = Order::ORDER_PAYMENT_FAILED;
            $data->save();
        }
        return['status' => 200];
    }


    public function walletTransection(Request $request)
    {
        try{
            $keyword = $request->keyword;
            $start_date = ($request->start_date)? date("Y-m-d", strtotime($request->start_date)): null;
            $end_date = ($request->end_date)? date("Y-m-d", strtotime($request->end_date)): null;

            $sort = $request->sort;
            $page_size = ($request->page_size)? $request->page_size : 10;
            
            $user = Auth::user();

            $obj = TeacherWallet::where('user_id', $user->id);

            if($keyword && $keyword != ''){
                $obj = $obj->where('description', 'like', '%'.$keyword.'%');
            }

            if($start_date && $start_date != ''){
                $obj = $obj->where('created_at','>=', $start_date);
            }

            if($end_date && $end_date != ''){
                $obj = $obj->where('created_at','<=', $end_date);
            }

            if($sort == 'asc'){
                $obj = $obj->orderBy('created_at');
            }else{
                $obj = $obj->orderByDesc('created_at');
            }
            $data['transection'] = $obj->paginate($page_size);

            $data['wallet'] = TeacherWallet::where('user_id', $user->id)->sum('amount');

            return sendResponse($data);
        }catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    private function getRewards($userId, $exchange_rate)
    {
        $creditPoints =  Reward::where('user_id',$userId)
                    ->where('transection_type',Reward::REWARD_CREDIT)
                    ->sum('points');
        $debitPoints =  Reward::where('user_id',$userId)
                    ->where('transection_type',Reward::REWARD_DEBIT)
                    ->sum('points');
        // print_r($creditPoints);
        // echo "-===";
        // print_r($debitPoints);
        // dd();
        $totalRewards = $creditPoints > $debitPoints ? $creditPoints - $debitPoints : 0;
        return round($totalRewards * $exchange_rate, 2);
    }

    private function calculatePerWordRate()
    {
        $settingData = SystemSetting::first();
        return $settingData->rate_per_assignment/$settingData->word_per_assignment;
    }

    private function applyDiscount($rewardAmount, $itemAmount, $userId)
    {
        if($rewardAmount >= $itemAmount){
            // $remainingRewardAmount = $rewardAmount - $itemAmount;
            $itemAmount = 0;
            $discount = $itemAmount;
        }else{
            $itemAmount = $itemAmount - $rewardAmount;
            $discount = $rewardAmount;
            
        }

        $obj = new Reward;
        $obj->user_id = $userId;
        $obj->points = $discount;
        $obj->transection_type = Reward::REWARD_DEBIT;
        $obj->save();

        return ['amount'=>$itemAmount, 'discount'=> $discount];
    }   


    public function addSellerPayment($contentID)
    {   
        try{
            //get seller Content
            $sellerContent = Content::with('user_details')->find($contentID);
            if(!$sellerContent){
                return response()->json(['status' => 'error', 'code' => '404', 'meassage' => 'Content Not Found!']);
            }
            //if exchange
            $calculated_words = 0;
            if($sellerContent->is_exchange == 1){            
                $rewardObj = new Reward;    

                $perWordRate = $this->calculatePerWordRate();

                //seller content words according to the exchange ratio
                $settingData = SystemSetting::first();
                $calculated_words = ($settingData->word_conversion_rate / $settingData->actual_word_present * $sellerContent->word_count);
                $points = round($calculated_words * $perWordRate, 2);

                $rewardObj->user_id = $sellerContent->user_id;
                $rewardObj->points = $points;
                $rewardObj->transection_type = Reward::REWARD_CREDIT;
                $rewardObj->reward_type = Reward::CONTENT_REWARD_TYPE;
                $rewardObj->in_words = $calculated_words;
                $rewardObj->save();
            }else{
                //Add expected amount to wallet
                $wallet = new TeacherWallet;

                $wallet->user_id = $sellerContent->user_id;
                $wallet->amount = $sellerContent->expected_amount;
                $wallet->description = "Pay for content Id". $sellerContent->id ." to user ".$sellerContent->user_details->name." ".$sellerContent->user_details->email;

                $wallet->save();
            }

            $sellerContent->paid_to_seller = 1;
            $sellerContent->save();
            
        }catch(Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }
    //Pay after 15 days
    public function payAfter15Days()
    {
        try{                 
            $data = TeacherWallet::with('billing_info')->where('remit', '0')->whereDate('created_at','=', date_format(Carbon::now()->subDays(15), 'Y-m-d'))->get();
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));              
            
            // $customer = $stripe->customers->create(
            //     [
            //     'email' => 'testseasia1116@gmail.com',
            //     'payment_method' => 'pm_card_visa',
            //     'invoice_settings' => ['default_payment_method' => 'pm_card_visa'],
            //     'name' => "Diksha Test",
            //     'description' => 'Seller account',
            //     ]
            // );
            // dd($customer);
            // $paymentDetails = $stripe->payouts->create([
            //     'amount' => 100,
            //     'currency' => 'inr',
            // ]);
            // dd($paymentDetails);
            // foreach($data as $user)
            // {
            //     // create a Payout...
            // }
            
        }catch(Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

}

