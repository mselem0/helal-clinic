<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteBookingRequest extends Model
{
    public const STATUSES = ['new' => 'جديد', 'contacted' => 'تم التواصل', 'confirmed' => 'مؤكد', 'cancelled' => 'ملغى'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['booking_date' => 'date'];
    }
}
