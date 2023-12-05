<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\registerConfirmation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;

class LoginRegisterController extends Controller
{
    /**
     * Instantiate a new LoginRegisterController instance.
     */
    public function __construct()
    {
        $this->middleware('guest')->except([
            'logout', 'dashboard'
        ]);
    }

    /**
     * Display a registration form.
     *
     * @return \Illuminate\Http\Response
     */
    public function register()
    {
        return view('auth.register');
    }

    /**
     * Store a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:250',
            'email' => 'required|email|max:250|unique:users',
            'password' => 'required|min:8|confirmed',
            'image_profile' => 'image|nullable|max:1999'
        ]);


        if($request->hasFile('image_profile')){
            $image = $request->file('image_profile');

            $folderPathOriginal = public_path('storage/photos/original');
            $folderPathThumbnail = public_path('storage/photos/thumbnail');
            $folderPathSquare = public_path('storage/photos/square');

            if (!File::isDirectory($folderPathOriginal)) {
                File::makeDirectory($folderPathOriginal, 0777, true, true);
            }

            if (!File::isDirectory($folderPathThumbnail)) {
                File::makeDirectory($folderPathThumbnail, 0777, true, true);
            }
            if (!File::isDirectory($folderPathSquare)) {
                File::makeDirectory($folderPathSquare, 0777, true, true);
            }

            $filenameWithExt = $request->file('image_profile')->getClientOriginalName();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('image_profile')->getClientOriginalExtension();
            $filenameSimpan = $filename . '_' . time() . '.' . $extension;

            // Simpan gambar asli
            $path = $request->file('image_profile')->storeAs('photos/original', $filenameSimpan);

            // Buat thumbnail dengan lebar dan tinggi yang diinginkan
            $thumbnailPath = public_path('storage/photos/thumbnail/' . $filenameSimpan);
            Image::make($image)
                ->fit(150, 150)
                ->save($thumbnailPath);

            // Buat versi persegi dengan lebar dan tinggi yang sama
            $squarePath = public_path('storage/photos/square/' . $filenameSimpan);
            Image::make($image)
                ->fit(300, 300)
                ->save($squarePath);

        }
        else {
            $path = null;
        }
            $userAccount = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'image_profile' => $filenameSimpan
            ]);

            $credentials = $request->only('email', 'password');
            Auth::attempt($credentials);
            $request->session()->regenerate();
            // return redirect()->route('dashboard', $userAccount->id)
            //     ->withSuccess('You have successfully registered & logged in!');
            return redirect()->route('dashboard')
                ->withSuccess('You have successfully registered & logged in!');
    }

    /**
     * Display a login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function login()
    {
        return view('auth.login');
    }

    /**
     * Authenticate the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('dashboard')
                ->withSuccess('You have successfully logged in!');
        }

        return back()->withErrors([
            'email' => 'Your provided credentials do not match in our records.',
        ])->onlyInput('email');
    }

    /**
     * Display a dashboard to authenticated users.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard()
    {
        if (Auth::check()) {
            // $accounts = User::latest()->paginate(3);
            return view('dashboard');
        }

        return redirect()->route('login')
            ->withErrors([
                'email' => 'Please login to access the dashboard.',
            ])->onlyInput('email');
    }

    /**
     * Log out the user from application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')
            ->withSuccess('You have logged out successfully!');;
    }

    public function edit(Request $request, String $id){

        $accounts = User::find($id)->first();
        return view('auth.edit', compact('accounts'));

    }

    public function update(Request $request, String $id){

        $accounts = User::findOrFail($id);

        if ($request->hasFile('image_profile')) {

            $filenameWithExt = $request->file('image_profile')->getClientOriginalName();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('image_profile')->getClientOriginalExtension();
            $filenameSimpan = $filename . "_" . time() . "." . $extension;
            $image = Image::make($request->file('image_profile'));
            $image->save('photos/'.$filenameSimpan);

            $pathSquare = ('photos/'.$filenameSimpan);
            $image->fit(250,250)->save($pathSquare);

            $image = Image::make($request->file('image_profile'));

            $pathThumbnail = ('photos/'.$filenameSimpan);
            Storage::delete('photos/'.$accounts->image_profile);
            $image->fit(350,200)->save($pathThumbnail);

            $accounts->update([
                'image_profile'     => $filenameSimpan,
                'name'   => $request->name,
                'email'   => $request->email,
            ]);

            return redirect()->route('dashboards')
                ->withSuccess('Profil berhasil terupdate.');

        } else {

            $accounts->update([
                'name'   => $request->name,
                'email'   => $request->email,
            ]);

            return redirect()->route('dashboards')
                    ->withSuccess('Profil berhasil terupdate.');
            }
    }

    public function delete(String $id){

        $accounts = User::find($id);
        Storage::delete('photos/'.$accounts->image_profile);
        // $accounts->delete();

        $accounts->update([
            'image_profile'     => null,
        ]);

        return redirect()->route('dashboards')
                ->withSuccess('gambar berhasil di hapus.');
    }
}