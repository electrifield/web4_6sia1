<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Faker\Provider\Image;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

// Intervention Library
use Intervention\Image\Laravel\Facades;
use Illuminate\Support\Facades\Response;
use Intervention\Image\Format;
use Intervention\Image\Typography\FontFactory;

class ProfileController extends Controller
{

    // generate id card menggunakan intervention php
    // docs: https://image.intervention.io/v4
    public function downloadIdCard()
    {
        $user = Auth::user();
        $image = \Intervention\Image\Laravel\Facades\Image::createImage(800,450)->fill('#dddddd');

        //foto
        $foto = \Intervention\Image\Laravel\Facades\Image::decode(Storage::disk('public')->path($user->photo_path));
        $foto->resize(300, 300);
        $image->insert($foto, 20, 20);

        // tampil nama user
        $image->text($user->name, 350,40, function (FontFactory $font) {
            $font->size(14);
        });

        // tampil jenis kelamin
        $image->text($user->gender ? 'Laki-laki' : 'Perempuan', 350,60, function () {});

        // tampil alamat
        $image->text($user->address, 350,80, function () {});


        $response = Response::make($image->encodeUsingFormat(Format::JPEG));
        $response->header('Content-Type', 'image/jpeg');
        return $response;
    }


    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        // simpan file foto di storage
        if ($request->hasFile('photo_path')) {
            $request->user()->photo_path = Storage::disk('public')
                        ->put('foto-profil', $request->file('photo_path'));
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
