<?php

namespace Modules\Clinical\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_default',
        'hide_header',
        'header_left_ar',
        'header_right_en',
        'logo_path',
        'header_image_path',
        'hide_footer',
        'footer_text_ar',
        'footer_text_en',
        'margin_top',
        'margin_bottom',
        'margin_left',
        'margin_right',
        'primary_color',
        'secondary_color',
        'font_family',
        'show_signature',
        'show_patient_box',
        'show_watermark',
        'watermark_text',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'hide_header' => 'boolean',
        'hide_footer' => 'boolean',
        'show_signature' => 'boolean',
        'show_patient_box' => 'boolean',
        'show_watermark' => 'boolean',
        'margin_top' => 'integer',
        'margin_bottom' => 'integer',
        'margin_left' => 'integer',
        'margin_right' => 'integer',
    ];

    /**
     * The "booted" method of the model.
     * Enforces that only one print template can be the default.
     */
    protected static function booted()
    {
        static::saving(function ($template) {
            if ($template->is_default) {
                // Set all other templates default status to false
                static::where('id', '!=', $template->id)
                    ->update(['is_default' => false]);
            } else {
                // If there's only one template left and it's being unset as default,
                // enforce it to stay default so we always have a fallback.
                if (static::where('id', '!=', $template->id)->where('is_default', true)->count() === 0) {
                    $template->is_default = true;
                }
            }
        });
    }
}
