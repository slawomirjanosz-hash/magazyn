<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = [
        'offer_number',
        'offer_title',
        'offer_date',
        'services',
        'works',
        'materials',
        'custom_sections',
        'total_price',
        'status'
    ];

    protected $casts = [
        'services' => 'array',
        'works' => 'array',
        'materials' => 'array',
        'custom_sections' => 'array',
        'offer_date' => 'date'
    ];
}
