<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmDeal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'company_id', 'supplier_id', 'value', 'currency', 
        'stage', 'probability', 'expected_close_date', 'actual_close_date',
        'owner_id', 'description', 'lost_reason', 'user_id'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'probability' => 'integer',
        'expected_close_date' => 'date',
        'actual_close_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(CrmCompany::class, 'company_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tasks()
    {
        return $this->hasMany(CrmTask::class, 'deal_id');
    }

    public function activities()
    {
        return $this->hasMany(CrmActivity::class, 'deal_id');
    }

    public function offers()
    {
        return $this->hasMany(Offer::class, 'crm_deal_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'crm_deal_user');
    }

    public function getWeightedValueAttribute()
    {
        return $this->value * ($this->probability / 100);
    }
}
