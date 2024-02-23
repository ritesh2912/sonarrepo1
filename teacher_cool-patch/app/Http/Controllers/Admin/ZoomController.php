<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ZoomMeeting;
use Illuminate\Support\Facades\Validator;
use App\Jobs\ZoomNotification;

class ZoomController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try{
            $keyword = $request->keyword;
            $page_size = ($request->page_size)? $request->page_size : 10;
            $sort = $request->sort;
            $data = new ZoomMeeting();

            if($keyword && $keyword != ''){
                $data = $data->where('topic','like', '%'.$keyword.'%');
            }

            if($sort == 'asc'){
                $data = $data->orderBy('created_at');
            }else{
                $data = $data->orderByDesc('created_at');
            }

            $data = $data->with('student:id,name,email', 'teacher:id,name,email,teacher_id_number')->paginate($page_size);

            return sendResponse($data);
        }catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try{
            $validator = Validator::make($request->all(), [
                'status' => 'required'
            ]);
            if ($validator->fails()) return response()->json( ['error' => $validator->errors(), 'code'=>'401']); 
            //0 pending, 1 approved, 2 rejected
            if($request->status != 0 && $request->status != 1 && $request->status != 2){
                return sendError('Invalid Request');
            }
            $status = $request->get('status');
            $zoomRequest = ZoomMeeting::with('student', 'teacher')->find($id);
            if($zoomRequest){
                if($zoomRequest->update(['status' => $status])){
                    //convert utc to current time zone
                    $arr['topic']=$zoomRequest->topic;
                    $arr['start_date']=$zoomRequest->schedule_time;
                    $arr['duration']=60;
                    $arr['type']='2';
                    $result=createMeeting($arr);
                    if(!isset($result->start_url)){
                        return sendError('Invalid Request');
                    }
                    if($status == 1){
                        //send mail to teacher
                        $teacherEmailData=[
                            'to'=>$zoomRequest->teacher->email,
                            'receiver_name'=>$zoomRequest->teacher->name." ".$zoomRequest->teacher->last_name,
                            'url'=>  $result->start_url,
                            'body' =>"Your video call has been schedule at ". $zoomRequest->schedule_time.'. Please start with '. $result->password.' Password',
                            'subject' => "Regarding Zoom Meeting"
                        ];
                        dispatch(new ZoomNotification($teacherEmailData))->afterResponse();
                    }
                    else{
                        //Send mail to user 
                        $studentEmailData=[
                            'to'=>$zoomRequest->student->email,
                            'receiver_name'=>$zoomRequest->student->name." ".$zoomRequest->student->last_name,
                            'url'=>  null,
                            'body' =>"Your Request for video call at ". $zoomRequest->schedule_time. " has been Cancelled by TeacherCool.",
                            'subject' => "Cancelled Zoom Meeting"
                        ];
                        dispatch(new ZoomNotification($studentEmailData))->afterResponse();
                    }
                    return sendResponse('Status changed successfully!');

                }
            }else{
                return response()->json(['status' => 'error', 'code' => '400', 'meassage' => 'Zoom Meeting not found']);
            }    
        }catch(Exception $e) {
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }
}
