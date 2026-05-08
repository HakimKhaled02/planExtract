<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Row extends Model
{
    protected $fillable = ['project_id', 'order'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function cellValues()
    {
        return $this->hasMany(CellValue::class);
    }
}
