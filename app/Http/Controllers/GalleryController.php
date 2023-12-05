<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class GalleryController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $client = new Client();
        $url =  "http://localhost:8000/api/gallery";
        $response = $client->request('GET', $url);
        $content = $response->getBody()->getContents();
        $content_array = json_decode($content, true);
        $galleries = $content_array['data']['data'];
        return view('gallery.index', compact('galleries'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('gallery.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
    
        $title = $request->input('title');
        $description = $request->input('description');
        $picture = $request->input('picture');

        $parameters = [
            'title' => $title,
            'description' => $description,
            'picture' => $picture,
        ];

        $client = new Client();
        $url =  "http://localhost:8000/api/gallery/store";

        $response = $client->request('POST', $url,[
            'headers' => ['Content-type' => 'application/json'],
            'body' => json_encode($parameters)
        ]);

        $content = $response->getBody()->getContents();
        $content_array = json_decode($content, true);
        $galleries = $content_array;
        
        // Redirect ke halaman gallery dengan pesan sukses
        return redirect()->route('gallery.index');
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $photos = Post::find($id);
        return view('gallery.edit', compact('photos'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->validate($request, [
            'title' => 'required|max:255',
            'description' => 'required',
            'picture' => 'image|nullable|max:1999'
            ]);

            $photo = Post::find($id);
            if ($request->hasFile('picture')) {
            $filenameWithExt = $request->file('picture')->getClientOriginalName();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('picture')->getClientOriginalExtension();
            $basename = uniqid() . time();
            $smallFilename = "small_{$basename}.{$extension}";
            $mediumFilename = "medium_{$basename}.{$extension}";
            $largeFilename = "large_{$basename}.{$extension}";
            $filenameSimpan = "{$basename}.{$extension}";
            $originalPath = public_path('storage/posts_image/' . $photo->picture);
            if (File::exists($originalPath)) {
                File::delete($originalPath);
            }

            $path = $request->file('picture')->storeAs('posts_image', $filenameSimpan);
            $photo->update([
                'title'   => $request->title,
                'description'   => $request->description,
                'picture' => $filenameSimpan
            ]);
            }else{

            $photo->update([
                'title'   => $request->title,
                'description'   => $request->description
            ]);
            }
            return redirect()->route('gallery.index');
        }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $photo = Post::find($id);
        $originalPath = public_path('storage/posts_image/' . $photo->picture);
        if (File::exists($originalPath)) {
            File::delete($originalPath);
        }
        $photo->delete();
        return  redirect()->route('gallery.index');
    }
}