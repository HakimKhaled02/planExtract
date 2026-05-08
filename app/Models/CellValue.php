<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class CellValue extends Model
{
    use LogsActivity;

    protected $fillable = ['row_id', 'column_id', 'value'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['value'])
            ->logOnlyDirty();
    }

    public function row()
    {
        return $this->belongsTo(Row::class);
    }

    public function column()
    {
        return $this->belongsTo(Column::class);
    }
}
