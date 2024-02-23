<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\ContentType;
use App\Models\NewsLetter;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardContentController extends Controller
{
    public function index(Request $request)
    {
        try{
            $keyword = $request->keyword;
            $sort = $request->sort;
            $page_size = ($request->page_size)? $request->page_size : 8;
            
            $data = DB::table('contents')
                    ->where('contents.is_approved','=', Content::CONTENT_APPROVE)
                    ->leftJoin('users','users.id', '=', 'contents.user_id')
                    ->select('contents.id','contents.content_category','contents.name','contents.is_approved','contents.created_at', 'contents.description','users.name as teacher_name');
            if($keyword && $keyword != ''){
                $data = $data->where('contents.name', 'like', '%'.$keyword.'%');
            }
            if($sort == 'asc'){
                $data = $data->orderBy('contents.created_at');
            }else{
                $data = $data->orderByDesc('contents.created_at');
            }
            
            $data = $data->limit(12)->get();

            $newsLetter = NewsLetter::limit(4)->get();

            $response = [
                'success' => true,
                'data'    => $data,
                'message' => 'Success',
                'content_category' => Content::getContentCategory(),
                'content_type' => ContentType::select('id','name')->get(),
                'news_letters' => $newsLetter,
                
            ];
        
            return response()->json($response, 200);
            
        }catch (Exception $e){
            return response()->json(['status' => 'error', 'code' => '500', 'meassage' => $e->getmessage()]);
        }
    }
}
