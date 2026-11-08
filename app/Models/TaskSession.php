<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TaskSession extends Model
{
    protected $guarded = [];

    protected $casts = [
        'config' => 'array',
        'game_date' => 'date',
        'bonus_ready' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(TimeBlock::class, 'time_block_id');
    }

    public function roll(): HasOne
    {
        return $this->hasOne(FateRoll::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SessionDistractionEvent::class)->orderBy('at_progress');
    }

    public function isFinished(): bool
    {
        return in_array($this->state, ['cleared', 'failed', 'flow', 'slip'], true);
    }
}
