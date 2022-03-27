<?php

namespace App\Http\Controllers;
use App\Models\Upload;
use Illuminate\Http\Request;
use Image;

class UploadImagesController extends Controller
{
    private $photos_path;
 
    public function __construct()
    {
        $this->photos_path = public_path('/images');
    }
 
    /**
     * Display all of the images.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $photos = Upload::all();
        return view('upload.uploaded-images', compact('photos'));
    }
 
    /**
     * Show the form for creating uploading new images.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('upload.upload');
    }
 
    /**
     * Saving images uploaded through XHR Request.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $photos = $request->file('file');
 
        if (!is_array($photos)) {
            $photos = [$photos];
        }
 
        if (!is_dir($this->photos_path)) {
            mkdir($this->photos_path, 0777);
        }
 
        for ($i = 0; $i < count($photos); $i++) {
            $photo = $photos[$i];
            $name = sha1(date('YmdHis') . time());
            $save_name = $name . '.' . $photo->getClientOriginalExtension();
            $resize_name = $name . time() . '.' . $photo->getClientOriginalExtension();
 
            Image::make($photo)
                ->resize(250, null, function ($constraints) {
                    $constraints->aspectRatio();
                })
                ->save($this->photos_path . '/' . $resize_name);
 
            $photo->move($this->photos_path, $save_name);
 
            $upload = new Upload();
            $upload->filename = $save_name;
            $upload->resized_name = $resize_name;
            $upload->original_name = basename($photo->getClientOriginalName());
            $upload->save();
        }
        /*return Response::json([
            'message' => 'Image saved Successfully'
        ], 200);*/
        return response()->json([
            'alert' => 'success',
            'img_name' => $save_name,
            'img_id' => $upload->id,
            'message' => 'Image saved Successfully'            
        ]);
    }
 
    /**
     * Remove the images from the storage.
     *
     * @param Request $request
     */
    public function destroy(Request $request)
    {
        $filename = $request->id;
        $uploaded_image = Upload::where('original_name', basename($filename))->first();
 
        if (empty($uploaded_image)) {
            //return Response::json(['message' => 'Sorry file does not exist'], 400);
            return response()->json([
                'alert' => 'success',
                'img_name' => $filename,                
                'message' => 'Sorry file does not exist'            
            ]);
        }
 
        $file_path = $this->photos_path . '/' . $uploaded_image->filename;
        $resized_file = $this->photos_path . '/' . $uploaded_image->resized_name;
 
        if (file_exists($file_path)) {
            unlink($file_path);
        }
 
        if (file_exists($resized_file)) {
            unlink($resized_file);
        }
 
        if (!empty($uploaded_image)) {
            $uploaded_image->delete();
        }
 
        //return Response::json(['message' => 'File successfully delete'], 200);
        return response()->json([
            'alert' => 'success',
            'img_name' => $filename,            
            'message' => 'File successfully delete'            
        ]);
    }
}
