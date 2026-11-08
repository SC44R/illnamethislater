<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DayPlan extends Model
{
    protected $guarded = [];

    protected $casts = ['game_date' => 'date', 'ended' => 'boolean'];

    public function blocks(): HasMany
    {
        return $this->hasMany(TimeBlock::class)->orderBy('block_index');
    }
}
