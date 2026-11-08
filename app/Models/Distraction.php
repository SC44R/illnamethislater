<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Distraction extends Model
{
    protected $guarded = [];

    protected $casts = ['enabled' => 'boolean'];
}
