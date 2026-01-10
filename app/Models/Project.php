<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_number',
        'name',
        'budget',
        'responsible_user_id',
        'status',
        'warranty_period',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function parts()
    {
        return $this->belongsToMany(Part::class, 'project_parts')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    public function removals()
    {
        return $this->hasMany(\App\Models\ProjectRemoval::class);
    }
}
