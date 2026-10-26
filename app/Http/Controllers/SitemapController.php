<?php

namespace App\Http\Controllers;

class SitemapController extends Controller
{
    public function index(?string $part = null)
    {
        // This dedicated ads branch never publishes a sitemap, regardless of CMS settings.
        abort(404);
    }
}
