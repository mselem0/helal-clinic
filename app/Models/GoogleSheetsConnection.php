<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleSheetsConnection extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['retry_after' => 'datetime', 'connected_at' => 'datetime', 'last_synced_at' => 'datetime'];
    }
}
