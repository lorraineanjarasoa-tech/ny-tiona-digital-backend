<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [

        'reporter_id',

        'subject',

        'reason',

        'status',

        'reportable_type',

        'reportable_id',
    ];


    public function reporter()
    {
        return $this->belongsTo(
            User::class,
            'reporter_id'
        );
    }


    public function reportable()
    {
        return $this->morphTo();
    }
}