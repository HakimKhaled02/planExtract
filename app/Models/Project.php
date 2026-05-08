<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Project extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity;

    protected $fillable = ['user_id', 'name', 'status'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status'])
            ->logOnlyDirty();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function columns()
    {
        return $this->hasMany(Column::class)->orderBy('order');
    }

    public function rows()
    {
        return $this->hasMany(Row::class)->orderBy('order');
    }
}
