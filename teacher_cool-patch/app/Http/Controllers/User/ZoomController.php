<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;
use App\Models\ZoomMeeting;
use App\Models\User;
use App\Models\Admin;
use App\Jobs\ZoomNotification;

class ZoomController extends Controller
{

    public function index(Request $request)
    {
        try{
            $keyword = $request->keyword;
            $page_size = ($request->page_size)? $request->page_size : 10;
            $sort = $request->sort;

            $user = Auth::user();

            $data = ZoomMeeting::where('student_id', $user->id);

            if($keyword && $keyword != ''){
                $data = $data->where('topic','like', '%'.$keyword.'%');
            }

            if($sort == 'asc'){
                $data = $data->orderBy('created_at');
            }else{
                $data = $data->orderByDesc('created_at');
            }

            $data = $data->paginate($page_size);

            return sendResponse($data);
        }catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function zoomRequestDetail($id, Request $request)
    {
        try{
            if($id <= 0){
                return sendError('Inavlid User', 401);
            }

            $data = ZoomMeeting::find($id);

            if($data == null){
                return sendError('Invalid Request');
            }

            return sendResponse($data);
        }catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    public function zoomRequest(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                // 'code' => 'required',
                'schedule_time' => 'required',
                'topic' => 'required',
                'time_zone' => 'required',
                'teacher_id' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }
            
            $student = Auth::user();

            $teacher = User::find($request->teacher_id);

            if($teacher == null){
                return sendError('Invalid teacher!');
            }
            $date = ($request->schedule_time)? date("Y-m-d h:i:s", strtotime($request->schedule_time)): null;
            $date = convertToUTCtime($date, $request->time_zone);
            
            if($date == null){
                return sendError('Invalid Schedule Data');
            }
            

            $arr['topic']=$request->topic;
            $arr['start_date']=$date;
            $arr['duration']=60;
            // $arr['password']='vishal';
            $arr['type']='2';
            $result=createMeeting($arr);
            if(!isset($result->start_url)){
                return sendError('Invalid Request');
            }
            
            // $response = $this->createTokken($request->code);
            // if(!isset($response->access_token)){
            //     return sendError('Invalid Request');
            // }

            // $access_token = $response->access_token;
            // // Create Zoom Meeting
            // $postParameter = [
            //     'start_time'=> $date,
            //     'topic'=>$request->topic,
            //     'agenda'=> $request->topic,
            //     'duration'=>60
            // ];
            
            // $email = env('ZOOM_EMAIL_ID');
            
            // $meetingResult = $this->createMeeting($postParameter, $email, $access_token);

            $zoomObj = new ZoomMeeting;
            $zoomObj->topic = $request->topic;
            // $zoomObj->category = $request->category;
            // $zoomObj->subject_id = $request->subject_id;
            $zoomObj->teacher_id = $teacher->id;
            $zoomObj->student_id = $student->id;
            $zoomObj->join_link = $result->join_url;
            $zoomObj->start_link = $result->start_url;
            $zoomObj->pass_code = $result->password;
            $zoomObj->schedule_time = $date;
            $zoomObj->meta_data = json_encode($result);
            $zoomObj->save();

            $studentEmailData=[
                'to'=>$student->email,
                'receiver_name'=>$student->name." ".$student->last_name,
                'url'=>  $result->join_url,
                'body' =>"Your Request for video call has been schedule at ". $request->schedule_time,
                'subject' => "Regarding Zoom Meeting"
            ];
            dispatch(new ZoomNotification($studentEmailData))->afterResponse();

            //Shoot mail to super admin
            $adminEmailData=[
                'to'=>env('ADMIN_EMAIL_ADDRESS'),
                'receiver_name'=>"TeacherCool",
                'url'=>  $result->start_url,
                'body' =>"Your video call has been schedule at ". $request->schedule_time.'. Please start with '. $result->password.' Password',
                'subject' => "Regarding Zoom Meeting"
            ];
            dispatch(new ZoomNotification($adminEmailData))->afterResponse();


            return sendResponse('Successfull');
        }catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }

    }

    // public function createTokken($code)
    // {
    //     $clientId = env('ZOOM_CLIENT_ID');
    //     $clientSecret = env('ZOOM_CLIENT_SECRET');

    //     $curl = curl_init();

    //     curl_setopt_array($curl, array(
    //     CURLOPT_URL => 'https://zoom.us/oauth/token?grant_type=authorization_code&code='.$code.'&redirect_uri=https://stgps.appsndevs.com/teachercool/api/v1/zoom-callback&client_id='.$clientId.'&client_secret='.$clientSecret,
    //     CURLOPT_RETURNTRANSFER => true,
    //     CURLOPT_ENCODING => '',
    //     CURLOPT_MAXREDIRS => 10,
    //     CURLOPT_TIMEOUT => 0,
    //     CURLOPT_FOLLOWLOCATION => true,
    //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //     CURLOPT_CUSTOMREQUEST => 'POST',
        
    //     ));

    //     $response = json_decode(curl_exec($curl));
    //     curl_close($curl);

    //     return $response;
    // }

    // private function createMeeting($postParameter, $email, $access_token)
    // {
        
    //     $curlHandle = curl_init('https://api.zoom.us/v2/users/'.$email.'/meetings');
    //     curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array(
    //         'Authorization: Bearer '.$access_token,
    //         'Content-Type: application/json'
    //     ));
    //     curl_setopt($curlHandle, CURLOPT_POSTFIELDS, json_encode($postParameter));
    //     curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        
    //     $curlResponse = json_decode(curl_exec($curlHandle));
    //     curl_close($curlHandle);
    //     return $curlResponse;
    // }
}
