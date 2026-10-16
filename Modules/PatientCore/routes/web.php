<?php

use Illuminate\Support\Facades\Route;
use Modules\PatientCore\Livewire\PatientAuth;

Route::get('/patient-auth', PatientAuth::class)->middleware('throttle:20,1')->name('patient.auth');

Route::post('/patient/logout',function () { auth()->logout();request()->session()->invalidate();request()->session()->regenerateToken();return redirect()->route('home'); })->middleware('auth')->name('patient.logout');

Route::get('/patient',function () {
    abort_unless(auth()->user()->isPatient(),403);
    $bookings=\Modules\Booking\Models\Booking::with(['service','clinicBranch'])->where('user_id',auth()->id())->latest()->get();
    return response()->view('helal.patient',compact('bookings'))->header('Cache-Control','private, no-store')->header('X-Robots-Tag','noindex, nofollow');
})->middleware('auth')->name('patient.portal');
