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
    ];

    protected $casts = [
        'budget' => 'decimal:2',
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
