<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Follow;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Cache\Store;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function storeAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:3000'
        ]);

        $user = auth()->user();
        $filename = $user->id . '-' . uniqid() . '.jpg';

        $imgData = Image::make($request->file('avatar'))->fit(120)->encode('jpg');
        Storage::put('public/avatars/' . $filename, $imgData);

        $oldAvatar = $user->avatar;

        $user->avatar = $filename;
        $user->save();
        if ($oldAvatar != '/fallback-avatar.jpg') {
            $dir = str_replace('/storage/', 'public/', $oldAvatar);
            Storage::delete($dir);
        }
        return back()->with('success', 'Congrats on the new avatar!');
    }

    public function showAvatarForm()
    {
        return view('avatar-form');
    }

    public function profile(User $user)
    {
        $currentlyFollowing = 0;
        if (auth()->check()) {
            $currentlyFollowing = Follow::where([['user_id', '=', auth()->user()->id], ['followeduser', '=', $user->id]])->count();
        }
        // return $user->posts()->get();
        return view('profile-posts', ['currentlyFollowing' => $currentlyFollowing, 'username' => $user['username'], 'posts' => $user->posts()->latest()->get(), 'postCount' => $user->posts()->count(), 'avatar' => $user->avatar]);
    }

    public function profileFollowers(User $user)
    {
        $currentlyFollowing = 0;
        if (auth()->check()) {
            $currentlyFollowing = Follow::where([['user_id', '=', auth()->user()->id], ['followeduser', '=', $user->id]])->count();
        }
        // return $user->posts()->get();
        return view('profile-followers', ['currentlyFollowing' => $currentlyFollowing, 'username' => $user['username'], 'posts' => $user->posts()->latest()->get(), 'postCount' => $user->posts()->count(), 'avatar' => $user->avatar]);
    }

    public function profileFollowing(User $user)
    {
        $currentlyFollowing = 0;
        if (auth()->check()) {
            $currentlyFollowing = Follow::where([['user_id', '=', auth()->user()->id], ['followeduser', '=', $user->id]])->count();
        }
        // return $user->posts()->get();
        return view('profile-following', ['currentlyFollowing' => $currentlyFollowing, 'username' => $user['username'], 'posts' => $user->posts()->latest()->get(), 'postCount' => $user->posts()->count(), 'avatar' => $user->avatar]);
    }

    public function logout()
    {
        auth()->logout();
        return redirect('/')->with('success', 'You are now logged out');
    }


    public function showCorrectHomePage()
    {
        if (auth()->check()) {
            return view('homePage-feed');
        } else {
            return view('homePage');
        }
    }


    public function login(Request $request)
    {
        $incomingFields = $request->validate([
            'loginusername' => 'required',
            'loginpassword' => 'required'
        ]);

        if (auth()->attempt(['username' => $incomingFields['loginusername'], 'password' => $incomingFields['loginpassword']])) {
            $request->session()->regenerate();
            return redirect('/')->with('success', 'You have successfully lgged in');
        } else {
            return redirect('/')->with('failure', 'Invalid login');
        }
    }


    public function register(Request $request)
    {
        $incomingFields = $request->validate([
            'username' => ['required', 'min:3', 'max:20', Rule::unique('users', 'username')],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'min:8', 'confirmed']
        ]);

        // Para encriptar la contrasenha, sin embargo laravel lo encripta por defecto
        // $incomingFields['password'] = bcrypt($incomingFields['password']);

        $user = User::create($incomingFields);
        auth()->login($user);

        return redirect('/')->with('success', 'Thank you for creating an account');
    }
}
