<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = [
        'offer_number',
        'offer_title',
        'offer_date',
        'offer_description',
        'services',
        'works',
        'materials',
        'custom_sections',
        'total_price',
        'status',
        'crm_deal_id',
        'customer_name',
        'customer_nip',
        'customer_address',
        'customer_city',
        'customer_postal_code',
        'customer_phone',
        'customer_email'
    ];

    protected $casts = [
        'services' => 'array',
        'works' => 'array',
        'materials' => 'array',
        'custom_sections' => 'array',
        'offer_date' => 'date'
    ];

    public function crmDeal()
    {
        // Check if CrmDeal model exists before defining relationship
        if (class_exists('\App\Models\CrmDeal')) {
            return $this->belongsTo(CrmDeal::class, 'crm_deal_id');
        }
        // Return empty relationship if CrmDeal doesn't exist
        return $this->belongsTo(self::class, 'crm_deal_id')->where('id', 0);
    }
}
