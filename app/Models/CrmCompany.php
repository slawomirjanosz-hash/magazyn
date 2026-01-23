<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmCompany extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'nip', 'email', 'phone', 'website', 'address', 
        'city', 'postal_code', 'country', 'type', 'status', 
        'notes', 'owner_id', 'source', 'supplier_id', 'added_by'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function deals()
    {
        return $this->hasMany(CrmDeal::class, 'company_id');
    }

    public function tasks()
    {
        return $this->hasMany(CrmTask::class, 'company_id');
    }

    public function activities()
    {
        return $this->hasMany(CrmActivity::class, 'company_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
