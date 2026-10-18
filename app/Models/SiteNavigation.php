<?php

namespace App\Models;

use App\Support\TracksPublishedUpdate;
use Illuminate\Database\Eloquent\Model;

class SiteNavigation extends Model
{
    use TracksPublishedUpdate;

    protected $guarded = ['id'];

    protected $casts = ['public_updated_at' => 'datetime', 'items' => 'array', 'draft_data' => 'array'];
}
