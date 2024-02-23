<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;
use App\Models\BillingInfo;

class BillingInfoController extends Controller
{
    public function index()
    {
       try{
            $user = Auth::user();
            
            $data = DB::table('billing_infos')
                ->join('users', 'users.id', '=', 'billing_infos.teacher_id')
                ->select('billing_infos.*')
                ->where('billing_infos.teacher_id', '=', $user->id)
                ->first();
            return sendResponse($data);
       }catch(Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
       }
    }

    public function addBillingInfo(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'account_holder_name' => 'required',
                'bank_name' => 'required',
                'account_number' => 'required',
                'ifsc_code' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $user = Auth::user();
            $billingInfo = BillingInfo::where('teacher_id', $user->id)->first();
            
            $data['account_holder_name'] = $request->account_holder_name;
            $data['bank_name'] = $request->bank_name;
            $data['account_number'] = $request->account_number;
            $data['ifsc_code'] = $request->ifsc_code;
            $data['firm_name'] = $request->firm_name;
            $data['gst_number'] = $request->gst_number;

            if(!$billingInfo){
                $data['teacher_id'] = $user->id;
                $result = BillingInfo::create($data);
            }else{
                $result = BillingInfo::where('teacher_id', $user->id)->update($data);
            }

            return sendResponse($result,"Updated Successfully");
       }catch(Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
       }
    }

    public function addSellerBillingInfo(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'account_holder_name' => 'required',
                'bank_name' => 'required',
                'account_number' => 'required',
                'ifsc_code' => 'required_without:routing_number',
                'routing_number' => 'required_without:ifsc_code'
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }
            $user = Auth::user();
            // $billingInfo = BillingInfo::where('teacher_id', $user->id)->first();
            // if(!$billingInfo){
            //     // Create a Customer...
            //     $customer = $stripe->customers->create(
            //         [
            //         'email' => $user->email,
            //         'payment_method' => 'pm_card_visa',
            //         'invoice_settings' => ['default_payment_method' => 'pm_card_visa'],
            //         'name' => $user->name,
            //         'description' => 'Seller',
            //         ]
            //     );
            //     // add customer id  of stripe into user table for user 
            //     $userReq = User::where('id',$user->id)
            //   ->update(['stripe_custId'=>$customer->id]);
            // }
            $data = $request->all();
            $billingData = BillingInfo::updateOrCreate([
                'teacher_id' => $user->id
            ],$data);

            return sendResponse($billingData,"Billing details added successfully.");
       }catch(Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
       }
    }
}
