<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\CurrencyExchange;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function index()
    {
        try{
            $payment= SystemSetting::first();
            if(!$payment){
                return sendError('No record Found');
            }
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
        return sendResponse($payment,200);
    }

    public function addPayment(Request $request)   
    {
        try {
            $validator = Validator::make($request->all(), [
                'teacher_cool_weightage' => 'required',
                // 'teacher_weightage' => 'required',
                'rate_per_assignment' => 'required',
                'actual_word_present' => 'nullable',
                'word_conversion_rate' => 'nullable'
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }
            $payment = new SystemSetting;
            $payment->teacher_cool_weightage=$request->teacher_cool_weightage;
            // $payment->teacher_weightage = $request->teacher_weightage;
            $payment->rate_per_assignment = $request->rate_per_assignment;
            $payment->discount = ($request->discount)? $request->discount : 0;
            $payment->actual_word_present = $request->actual_word_present ?? 0;
            $payment->word_conversion_rate = $request->word_conversion_rate ?? 0;
            $payment->save();
        } catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
        return sendResponse($payment,'Transaction added successfully');
    }

    public function editPayment(Request $request)
    {
        try{

            $validator = Validator::make($request->all(), [
                'teacher_cool_weightage' => 'required',
                // 'teacher_weightage' => 'required',
                'rate_per_assignment' => 'required',
                'word_per_assignment' => 'required',
                'actual_word_present' => 'nullable',
                'word_conversion_rate' => 'nullable'
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }
            $payment = SystemSetting::find(1);
            if(!$payment){
                return sendError('No record found');
            }
            $payment->teacher_cool_weightage=$request->teacher_cool_weightage;
            $payment->word_per_assignment = $request->word_per_assignment;
            $payment->rate_per_assignment = $request->rate_per_assignment;
            $payment->discount = ($request->discount)? $request->discount : 0;
            $payment->actual_word_present = $request->actual_word_present ?? 0;
            $payment->word_conversion_rate = $request->word_conversion_rate ?? 0;
            $payment->save();
            return sendResponse($payment,'Settings Updated Successfully');
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function currencyExchange(Request $request)
    {
        try{
            $data = CurrencyExchange::get();

            if(!$data){
                return sendError('Invalid Request');
            }

            return sendResponse($data,'Successfull');
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function updateCurrencyExchange(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'currency_data' => 'required',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            if(!is_array($request->currency_data)){
                return sendError('Invalid Request');
            }

            // $data = [];
            foreach($request->currency_data as $val){
                if($val['currency'] != 'INR'){
                    $result = CurrencyExchange::updateOrCreate(
                            ['currency'=> $val['currency']],
                            [
                                'currency'=> $val['currency'],
                                'exchange_rate'=>$val['exchange_rate']
                            ]
                        );
                }
            }

            return sendResponse([],'Added Successfull');
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }
}
