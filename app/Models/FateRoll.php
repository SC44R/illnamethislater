<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FateRoll extends Model
{
    protected $guarded = [];

    protected $casts = ['modifiers' => 'array'];
}
