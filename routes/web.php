<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about-us', [PageController::class, 'about'])->name('about');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/services/{slug}', [PageController::class, 'serviceShow'])->name('services.show');
Route::get('/gallery', [PageController::class, 'gallery'])->name('gallery');
Route::get('/contact-us', [PageController::class, 'contact'])->name('contact');
Route::get('/blog', [PageController::class, 'blogIndex'])->name('blog.index');
Route::get('/blog/{slug}', [PageController::class, 'blogShow'])->name('blog.show');
Route::post('/bookings', [HomeController::class, 'storeBooking'])->middleware('throttle:10,1')->name('bookings.store');
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
