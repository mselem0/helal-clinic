<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Service;

class PageController extends Controller
{
    public function about()
    {
        return redirect()->to(route('home').'#about');
    }

    public function gallery()
    {
        return redirect()->to(route('home').'#videos');
    }

    public function contact()
    {
        return redirect()->to(route('home').'#clinics');
    }

    public function services()
    {
        return view('helal.list', ['title' => 'خدمات العيادة', 'items' => Service::where('is_active', true)->orderBy('order')->get(), 'type' => 'service']);
    }

    public function serviceShow(string $slug)
    {
        return view('helal.detail', ['item' => Service::where('is_active', true)->where('slug', $slug)->firstOrFail(), 'type' => 'service']);
    }

    public function blogIndex()
    {
        return view('helal.list', ['title' => 'مقالات الدكتور', 'items' => Article::where('is_published', true)->latest()->paginate(12), 'type' => 'article']);
    }

    public function blogShow(string $slug)
    {
        return view('helal.detail', ['item' => Article::where('is_published', true)->where('slug', $slug)->firstOrFail(), 'type' => 'article']);
    }
}
