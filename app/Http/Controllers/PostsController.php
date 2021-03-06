<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Post; //for using model functions
use Carbon\Carbon;
Use App\Helpers\DB\CustomDB;

class PostsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct()
    {
        //From Evram:
            //awl wa7d bs ely bya5od authentication
            //$this->middleware('auth'); //redirect to login page if not logged in 
            //$this->middleware('auth:admin');
            //$this->middleware('auth:moderator');
    }

    public function index()
    {
        if(Auth::guard('web')->check())
        {   
             $sql =  CustomDB::getInstance()->query("SELECT 
                admins.announcement as body
                FROM (`admins`) Where  admins.announcement is not null ");
           
            $events = $sql->results();
            $posts = CustomDB::getInstance()->get(array("*"),"posts")->order("created_at DESC")->e()->results();
            return view('posts.index')->with('posts', $posts)->with('events', $events);
        }
        else
            return redirect('/');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
       if(Auth::guard('web')->check())
        { 
        return view('posts.create');
        }
        else
            return redirect('/');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(Auth::guard('web')->check())
        {
          //validation
        $this->Validate($request, [
            'title' => 'required',
            'body' => 'required',
            'min_cost' => 'required',
            'max_cost' => 'required',
            'period' => 'required',
            'category' => 'required',
            'description_file' => 'nullable|max:1999', //< 2mb as apache doesn't allow         
        ]);
        //Handle File Upload
        if($request->hasFile('description_file'))
        {
            $file = $request->file('description_file');
            //get file name with ext
            $fileNameWithExt = $file->getClientOriginalName();
            //get just filename
            $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
            //get just ext
            $extension = $file->guessClientExtension();
            if($extension == 'pdf')
            {
                $fileNameToStore = $fileName.'_'.time().'.'.$extension;
                $fileNameToStore = urlencode($fileNameToStore);
                //upload file
                $path = $file->storeAs('public/ProjectDescriptions', $fileNameToStore);
            }
            else
            {
                $fileNameToStore = 'nofile.pdf';
            }
        }
        else    
        {
            $fileNameToStore = 'nofile.pdf';
        }
        $title = $request->input('title');
        $body = $request->input('body');
        $min_cost = (int)$request->input('min_cost');
        $max_cost = (int)$request->input('max_cost');
        $period = (int)$request->input('period');
        $user_id = Auth::id();
        $category = htmlspecialchars($request->input('category'),ENT_NOQUOTES, 'UTF-8'); //to avoid xxs attacks
        $created_at = Carbon::now()->toDateTimeString();
        
        $check = CustomDB::getInstance()->insert("posts", array(
            'title' => $title,
            'body' => $body,
            'min_cost' => $min_cost,
            'max_cost' => $max_cost,
            'description_file' => $fileNameToStore,
            'period' => $period,
            'user_id' => $user_id,
            'category' => $category,
            'created_at' => $created_at
        ))->e();
        return redirect('/posts')->with('success', 'Post Created');

        if($check) {
            return redirect('/posts')->with('success', 'Post Created');
        }
        return redirect('/posts')->with('error', 'Post Failed'); 
        }
        else     
            return redirect('/');
}

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if(Auth::guard('web')->check()||Auth::guard('moderator')->check()||Auth::guard('admin')->check())
        {
            $sql = CustomDB::getInstance()->get(array("*"), "posts")->where("id = ?",[$id])->e();
            $post = $sql->results();
            $user_id = Auth::id();
            $alreadyProposed = CustomDB::getInstance()->query("SELECT count(*) as count FROM proposals WHERE user_id = ? and post_id = ?",[$user_id, $id])->results();
            $alreadyProposed = $alreadyProposed[0]->count;
            return view('posts.show')->with('post', $post)->with("user_id",$user_id)->with("alreadyProposed",$alreadyProposed);
        }
        else     
            return redirect('/');       
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $post = CustomDB::getInstance()->query("SELECT * FROM posts WHERE id = ?",[$id])->results();
        $post = $post[0];
        return view('posts.edit')->with('post',$post);
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
        //validation
        $this->Validate($request, [
            'title' => 'required',
            'body' => 'required',
            'min_cost' => 'required',
            'max_cost' => 'required',
            'period' => 'required',
            'category' => 'required',        
        ]);
        
        $title = $request->input('title');
        $body = $request->input('body');
        $min_cost = (int)$request->input('min_cost');
        $max_cost = (int)$request->input('max_cost');
        $period = (int)$request->input('period');
        $category = htmlspecialchars($request->input('category'),ENT_NOQUOTES, 'UTF-8'); //to avoid xxs attacks
        
        $check = CustomDB::getInstance()->query("UPDATE posts SET title = ?, body = ?, min_cost = ?, max_cost = ?, period = ?, category = ? WHERE id = ?",[$title,$body,$min_cost,$max_cost,$period,$category,$id])->results();

            return redirect()->route('posts.show', $id)->with('success', 'Post updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(Auth::guard('web')->check()||Auth::guard('moderator')->check()||Auth::guard('admin')->check())
           {
            $id = (int)$id;
            $check = CustomDB::getInstance()->delete("posts")->where("id = ?", [$id])->e();
            if($check)
            {
                //Evram: I change the redirecting path, 34an lma ymsa7 feedback
                return redirect('/')->with('success', 'Post Removed');
            } 
            else
            {
                return redirect('/')->with('error', 'Post Not Removed');
            }
        }
        else
            return redirect('/');  
    }
       
      
    
}
