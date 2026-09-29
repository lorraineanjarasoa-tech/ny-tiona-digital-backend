<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartageLike extends Model
{
    protected $table = 'partage_likes';

    protected $fillable = ['partage_id', 'user_id'];

    public function partage()
    {
        return $this->belongsTo(Partage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}