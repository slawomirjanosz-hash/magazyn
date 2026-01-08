<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectRemoval extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'part_id',
        'user_id',
        'quantity',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
