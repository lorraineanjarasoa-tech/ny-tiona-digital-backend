<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    protected $table = 'likes';

    protected $fillable = ['partage_id', 'user_id', 'reaction_type'];

    public function partage()
    {
        return $this->belongsTo(Partage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}