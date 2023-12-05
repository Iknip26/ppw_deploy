<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

  /**
    * @OA\Info(
    * description="Contoh API doc menggunakan API Gallery",
    * version="0.0.1",
    * title="API Gallery",
    * termsOfService="http://swagger.io/terms/",
    * @OA\Contact(
    * email="ikhnif26@gmail.com"
    * ),
    * @OA\License(
    * name="Apache 2.0",
    * url="http://www.apache.org/licenses/LICENSE-2.0.html"
    * )
    * )
    */

class GalleryController extends Controller
{

     /**
    * @OA\Get(
    * path="/greet",
    * tags={"greeting"},
    * summary="Coba API",
    * description="A sample greeting to test out the API",
    * operationId="greet",
    * @OA\Parameter(
    * name="firstname",
    * description="nama depan",
    * required=true,
    * in="query",
    * @OA\Schema(
    * type="string"
    * )
    * ),
    * @OA\Parameter(
    * name="lastname",
    * description="nama belakang",
    * required=true,
    * in="query",
    * @OA\Schema(
    * type="string"
    * )
    * ),
    * @OA\Response(
    * response="default",
    * description="successful operation"
    * )
    * )

    * @OA\Get(
    * path="/api/gallery",
    * tags={"gallery"},
    * summary="Tampilkan Gallery",
    * description="Ini adalah dokumentasi untuk menampilkan gallery",
    * operationId="gallery_index",

    * @OA\Response(
    * response="default",
    * description="Proses Berhasil"
    * )
    * )

    * @OA\Post(
    * path="/api/gallery/store",
    * tags={"gallery"},
    * summary="Tambah Gambar",
    * description="Ini adalah dokumentasi untuk menambah gambar pada gallery",
    * operationId="galllery.store",
    * @OA\Parameter(
    * name="title",
    * description="judul gambar",
    * required=true,
    * in="query",
    * @OA\Schema(
    * type="string"
    * )
    * ),
    * @OA\Parameter(
    * name="description",
    * description="deskripsi gambar",
    * required=true,
    * in="query",
    * @OA\Schema(
    * type="string"
    * )
    * ),
    * @OA\Parameter(
    * name="image_url",
    * description="gambar",
    * required=true,
    * in="query",
    * @OA\Schema(
    * type="image"
    * )
    * ),
    * @OA\Response(
    * response="default",
    * description="Proses Berhasil"
    * )
    * )

    */

    public function index()
    {
        $data = Post::where('picture', '!=', '')->whereNotNull('picture')->orderBy('created_at', 'desc')->paginate(30);

        return response()->json([
            'status' => true,
            'message' => 'Data ditemukan',
            'data' => $data
        ], 200);
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
    public function store(Request $request){
        // Validasi input

        $rules = [
            'title' => 'required',
            'description' => 'required',
            'picture' => 'image|max:1999'
        ];
    
        $validator = Validator::make($request->all(), $rules);
    
        if($validator->fails()){
            return response()->json([
                'status'=>false,
                'message'=>'Gagal upload gambar',
                'data'=>$validator->errors()
            ]);
        }
    
        // Proses upload gambar
        if ($request->hasFile('picture')) {
            $filenameWithExt = $request->file('picture')->getClientOriginalName();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('picture')->getClientOriginalExtension();
            $basename = uniqid() . time();
    
            // Nama file untuk berbagai ukuran
            $smallFilename = "small_{$basename}.{$extension}";
            $mediumFilename = "medium_{$basename}.{$extension}";
            $largeFilename = "large_{$basename}.{$extension}";
            $filenameSimpan = "{$basename}.{$extension}";
    
            // Simpan gambar
            $path = $request->file('picture')->storeAs('posts_image', $filenameSimpan);
        } else {
            // Jika tidak ada gambar, gunakan gambar default
            $filenameSimpan = 'noimage.png';
        }
    
        // Simpan data post ke database
        $post = new Post;
        $post->picture = $filenameSimpan;
        $post->title = $request->input('title');
        $post->description = $request->input('description');
        $post->save();
    
        // Redirect ke halaman gallery dengan pesan sukses
        return response()->json([
            'status' => true,
            'message' => 'Berhasil Menambahkan Gambar'
        ]);
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