<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteBookingSheetSync extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'conflicts' => 'array', 'last_synced_at' => 'datetime', 'retry_after' => 'datetime'];
    }
}
