<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcrastinatorType extends Model
{
    protected $guarded = [];

    protected $casts = ['weak_tags' => 'array'];
}
