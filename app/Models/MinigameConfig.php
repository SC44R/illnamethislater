<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MinigameConfig extends Model
{
    protected $guarded = [];

    protected $casts = ['params' => 'array'];
}
