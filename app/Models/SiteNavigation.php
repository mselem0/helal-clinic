<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteNavigation extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['items' => 'array', 'draft_data' => 'array'];
}
