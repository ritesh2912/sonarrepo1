<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Jobs\AdminEmail;

class Copyright extends Controller
{
    public function takeDownEmail(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'url' => 'required',
                'more_url' => 'nullable',
                'another_url' => 'required_if:more_url,true',
                'first_name' => 'required',
                'last_name' => 'required',
                'title' => 'required',
                'university_email' => 'required|email',
                'university_name' => 'required',
                'phone' => 'required',
                'address_line_1' => 'required',
                'address_line_2' => 'required',
                'city' => 'required',
                'state' => 'required',
                'zip' => 'required',
                'country' => 'required',
                'property_rights' => 'required',
                'offer_materials' => 'required',
                'accurate_information' => 'required',
                'fax' => 'nullable',
                'country' => 'required'

            ]);
            if ($validator->fails()){
                return response()->json( ['error' => $validator->errors(), 'code'=>'401']); 
            } 
            $data = $request->all();
            $adminEmailData=[
                'to'=> env('ADMIN_EMAIL_ADDRESS'),
                'name'=>'Teacher Cool',
                'is_copyright'=>true,
                'body' =>'',
                'reqData'=> $data,
                'subject' => "Copyright Takedown Request"
            ];    
            dispatch(new AdminEmail($adminEmailData))->afterResponse();
            return sendResponse([], 'Request sent successfully.');
        }catch(\Exception $ex){
            return response()->json( ['error' => $ex->getmessage(), 'code'=>'500']); 
        }    
    }
}
