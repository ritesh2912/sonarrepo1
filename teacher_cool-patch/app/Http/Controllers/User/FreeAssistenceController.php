<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Jobs\AdminEmail;
use Category;
use App\Services\WhatsappService;

class FreeAssistenceController extends Controller
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
    public function index(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'category' => 'required',
                'subject' => 'required_if:category,1,2,3',
                'question' => 'required',
                'word_count' => 'nullable',
                'assignment_attachment' => 'nullable|file|max:2048',
                'due_date' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['code' => '302', 'error' => $validator->errors()]);
            }

            $file = null;
            $filePath = '';
            if ($request->file('assignment_attachment')) {
                $allowedExt = ['pdf','docx','doc'];
                // $name = $request->file('comment_attch')->getClientOriginalName();
                $extension = $request->file('assignment_attachment')->getClientOriginalExtension();
                if(!in_array( $extension, $allowedExt )){
                    return sendError('Only PDF, Doc and Docx files are allowed files are allowed');
                }
                $originalfileName = $request->file('assignment_attachment')->getClientOriginalName();
                $originalfileName = pathinfo($originalfileName, PATHINFO_FILENAME);
                $originalfileName = implode('-',explode(' ', $originalfileName));
                $fileName = $originalfileName."-".time().'.'.$extension;
                // store CV in resume folder...
                $filePath = $request->file('assignment_attachment')->storeAs('free_assistance',$fileName,'public');
            }
            
            $data['category'] = $request['category'];
            $data['subject'] = $request['subject'];
            $data['word_count'] = $request['word_count'];
            $data['email'] = $request['email'];
            $data['contact'] = $request['phone_code'].$request['contact'];
            $data['question'] = $request['question'];
            $data['due_date'] = $request['due_date'] ?? "N/A";
            $allCategory = Category::getCetegoryForOuestions();

            foreach($allCategory as $cat){
                if($cat['value'] == $data['category']){
                    $data['category'] = $cat['name'];
                }
            }

            // Subject::find()
            $adminEmailData=[
                'to'=> env('ADMIN_EMAIL_ADDRESS'),
                'name'=>'Teacher Cool',
                'filename' => $filePath,
                'body' =>"A User has applied for free assistance with details mentioned below: ",
                'reqData'=> $data,
                'subject' => "Free Assistance Request"
            ];  
            // dd($adminEmailData);        
            dispatch(new AdminEmail($adminEmailData))->afterResponse();

            //Whatsapp notify
            $messageBody  = "Dear Admin,\nA User has applied for free assistance with details mentioned below:\nEmail: ".$data['email']."\nContact: ".$data['contact']."\nCategory: ".$data['category']."\nSubject: ".$data['subject']."\nWord Count: ".$data['word_count']."\nDeadline: ".$data['due_date']."\nQuestion: ".$data['question'];
            $data['body'] = $messageBody;
            $this->whatsappService->sendMessage($data);

            return sendResponse([], 'Your assignment is successfully sent');
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){
        //
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
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
