<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'game_date' => 'date',
            'last_flow_date' => 'date',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ProcrastinatorType::class, 'procrastinator_type_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function dayPlans(): HasMany
    {
        return $this->hasMany(DayPlan::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TaskSession::class);
    }
}
