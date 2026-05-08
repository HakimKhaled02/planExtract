<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Column extends Model
{
    use LogsActivity;

    protected $fillable = ['project_id', 'name', 'type', 'order'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'order'])
            ->logOnlyDirty();
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function cellValues()
    {
        return $this->hasMany(CellValue::class);
    }
}
