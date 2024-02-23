<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\SendWelcomeEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\UserDetails;
use App\Models\Content;
use Currency;
use App\Services\WhatsappService;

class SellerController extends Controller
{
    private WhatsappService $whatsappService;
 
    public function __construct(WhatsappService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $data['user']= User::with('user_details')->find($user->id); 
            if(!$data['user']){
                sendError('Inavlid User', 401);
            }
            return sendResponse($data);
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users',
                'password' => 'required|min:4',
                'name' => 'required|min:3',
                'phone_code' => 'nullable',
                'contact' => 'required',
                'country' => 'required',
                'currency' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $profile_path = '';
            if ($request->file('profile')) {
                // $name = $request->file('profile')->getClientOriginalName();
                $extension = $request->file('profile')->getClientOriginalExtension();
                $originalfileName = $request->file('profile')->getClientOriginalName();
                $originalfileName = pathinfo($originalfileName, PATHINFO_FILENAME);
                $originalfileName = implode('-',explode(' ', $originalfileName));
                $fileName = $originalfileName."-".time().'.'.$extension;
                $profile_path = $request->file('profile')->storeAs('profile',$fileName,'public');
            }  
            $verifyCode = random_int(100000, 999999);
            $data = [
                'email' => $request->get('email'),
                'name' => $request->get('name'),
                'password' => Hash::make($request->get('password')),
                'user_type' =>  User::SELLER_TYPE,
                'profile_path' => $profile_path,
                'email_verify_code' => $verifyCode,
                'is_active' => 1,
            ];
            $user = User::create($data);
            if(!$user){
                return response()->json(['status' => 'error', 'code' => '500', 'meassage' => "Something went wrong"]);
            }        
            $userDetails = new UserDetails;
            $userDetails->phone_code = $request->phone_code;
            $userDetails->contact = $request->contact;
            $userDetails->country = $request->country;
            $userDetails->currency = $request->currency;
            
            $user->user_details()->save($userDetails);
            $url = env('APP_URL_FRONT').'/verify-email/' . $verifyCode;
            $welcomedata=[
                'to'=> $user->email,
                'receiver_name'=> $user->name,
                'url'=> $url,
                'data' => "Hope, You will have wonderful experience here.Please verify your email from the link below",
                'subject' => "Welcome"
            ];
            //whatsapp 
            $smsUserData['body'] = 'Dear Admin, A new seller with email: '.$request->email.' is registered successfully with Teacher Cool.';
            $this->whatsappService->sendMessage($smsUserData);

            dispatch(new SendWelcomeEmail($welcomedata))->afterResponse();
            return sendResponse([], 'Verify email with the link sent to your email.');
        }catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'    => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) return sendError('Validation Error.', $validator->errors(), 422);

            $credentials = $request->only('email', 'password');
            if (Auth::attempt($credentials)) {
                $user= Auth::user();
                if($user->is_active && $user->email_verified_at != null){
                    $success['user']  = $user;
                    $success['token'] = $user->createToken('accessToken', ['user'])->accessToken;
                    return sendResponse($success, 'You are successfully logged in.');
                }
                else{
                    return response()->json( ['error' => 'User is not active or verified.', 'code'=>'403']);
                }
            }else {
                return response()->json( ['error' => 'invalid credentials', 'code'=>'401']);
            }
        }catch (\Exception $e) {
            return response()->json(['status' => 'error', 'code' => '401', 'msg' => 'You  are not authorised']);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'name' => 'required|min:3',
                'phone_code' => 'nullable',
                'contact' => 'required',
                'country' => 'required',
                'profile' => 'nullable',
                'currency' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }
            $seller = User::with('user_details')->find(Auth::id());
            $profile_path = $seller->profile_path;
            if ($request->file('profile')) {
                // $name = $request->file('profile')->getClientOriginalName();
                $extension = $request->file('profile')->getClientOriginalExtension();
                $originalfileName = $request->file('profile')->getClientOriginalName();
                $originalfileName = pathinfo($originalfileName, PATHINFO_FILENAME);
                $originalfileName = implode('-',explode(' ', $originalfileName));
                $fileName = $originalfileName."-".time().'.'.$extension;
                $profile_path = $request->file('profile')->storeAs('profile',$fileName,'public');
            }  
            $data = $request->all();
            $data['profile_path'] = $profile_path; 
            if($seller->update($data)){ 
                $details = UserDetails::where('user_id', $seller->id)->first();  
                $detailsData['phone_code'] = $request->phone_code;
                $detailsData['contact'] = $request->contact;
                $detailsData['gender'] = $request->gender;
                $detailsData['age'] = $request->age;
                $detailsData['city'] = $request->city;
                $detailsData['state'] = $request->state;
                $detailsData['country'] = $request->country;
                $detailsData['currency'] = $request->currency;
                $detailsData['qualification'] = $request->qualification;
                $detailsData['university'] = $request->university;
                $userDetails = UserDetails::where('user_id',$seller->id)
                            ->update($detailsData);
                if($userDetails){
                    $updateSeller = User::with('user_details')->find(Auth::id());
                    return sendResponse($updateSeller, 'Profile Updated Successfully');
                }
                else{
                    return response()->json( ['error' => 'Somthing went wrong. Try again']);
                }
            }else{
                return response()->json( ['error' => 'Somthing went wrong. Try again']);
            }
        }catch(\Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'msg' => $e->getMessage()]);
        }
    }
    public function contentList(Request $request){
        try{
            $page_size = ($request->page_size)? $request->page_size : 10;
            $contents = Content::where('user_id', Auth::id())->orderBy('id', 'DESC')->paginate($page_size);
            if($contents){
                return sendResponse($contents, 'All Content list fetched successfully!');
            }else{
                return response()->json(['status' => 'error', 'code' => '404', 'msg' => "Content Not found."]);
            }
        }catch(\Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'msg' => $e->getMessage()]);
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
