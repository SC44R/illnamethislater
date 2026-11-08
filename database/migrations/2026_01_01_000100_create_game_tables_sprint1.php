<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 1 tables: types, causes, tasks, planner, sessions, fate rolls, distractions, balance numbers.
 * Also adds the game columns to Laravel's users table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procrastinator_types', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->string('icon');
            $t->unsignedSmallInteger('base_focus');
            $t->unsignedSmallInteger('base_willpower');
            $t->unsignedSmallInteger('base_calm');
            $t->unsignedSmallInteger('base_drive');
            $t->json('weak_tags');                 // distraction tags this type is weak against
            $t->string('description');
            $t->timestamps();
        });

        Schema::create('causes', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();          // C1 .. C11
            $t->string('name');
            $t->string('tag');                     // the distraction tag family
            $t->string('icon');
            $t->string('description');
            $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('player');
            $t->unsignedBigInteger('procrastinator_type_id')->nullable();
            $t->unsignedSmallInteger('level')->default(1);
            $t->unsignedInteger('xp')->default(0);
            $t->unsignedInteger('skill_points')->default(0);
            $t->unsignedInteger('coins')->default(0);
            $t->unsignedSmallInteger('pity_counter')->default(0);
            $t->unsignedSmallInteger('slip_streak')->default(0);
            $t->date('game_date')->nullable();      // the in-game calendar day
            $t->date('last_flow_date')->nullable(); // game day of the last Flow (one per day)
        });

        Schema::create('tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('title', 120);
            $t->string('type');                    // practice, deep, memorize
            $t->string('size');                    // small, medium, large
            $t->unsignedTinyInteger('difficulty'); // 1 to 3
            $t->date('due_date');
            $t->foreignId('cause_id')->constrained();
            $t->string('status')->default('open'); // open, pending, overdue, done, cancelled
            $t->string('minigame')->default('beat_click');
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'status']);
        });

        Schema::create('day_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->date('game_date');
            $t->boolean('ended')->default(false);
            $t->timestamps();
            $t->unique(['user_id', 'game_date']);
        });

        Schema::create('time_blocks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('day_plan_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('block_index');   // 1 to 4
            $t->string('kind')->default('free');       // task or free
            $t->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $t->boolean('used')->default(false);
            $t->timestamps();
            $t->unique(['day_plan_id', 'block_index']);
        });

        Schema::create('task_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('task_id')->constrained()->cascadeOnDelete();
            $t->foreignId('time_block_id')->nullable()->constrained()->nullOnDelete();
            $t->string('minigame');
            $t->unsignedBigInteger('seed');
            $t->date('game_date');
            $t->unsignedTinyInteger('block_index');
            $t->string('state')->default('prep');      // prep, playing, cleared, failed, flow, slip
            $t->string('band')->nullable();
            $t->unsignedTinyInteger('difficulty')->nullable();
            $t->json('config')->nullable();
            $t->integer('focus_max')->default(0);
            $t->integer('focus_current')->default(0);
            $t->integer('points')->default(0);
            $t->integer('target_points')->default(0);
            $t->unsignedSmallInteger('combo')->default(0);
            $t->boolean('bonus_ready')->default(false);
            $t->unsignedTinyInteger('extra_rounds')->default(0);
            $t->unsignedTinyInteger('stars')->default(0);
            $t->unsignedInteger('xp_awarded')->default(0);
            $t->unsignedInteger('coins_awarded')->default(0);
            $t->string('outcome_code')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('ended_at')->nullable();
            $t->timestamps();
        });

        Schema::create('fate_rolls', function (Blueprint $t) {
            $t->id();
            $t->foreignId('task_session_id')->unique()->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('d20');
            $t->json('modifiers');
            $t->integer('total');
            $t->string('band');
            $t->string('outcome_code')->nullable();
            $t->timestamps();
        });

        Schema::create('dice_bands', function (Blueprint $t) {
            $t->id();
            $t->string('band')->unique();
            $t->integer('min_total')->nullable();
            $t->json('effects');
            $t->timestamps();
        });

        Schema::create('distractions', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->string('tag');
            $t->unsignedTinyInteger('tier');
            $t->string('prompt');                     // tap, hold, swipe, triple_tap
            $t->unsignedTinyInteger('prompt_count')->default(1);
            $t->string('lingering_effect_code');
            $t->text('tip');
            $t->string('icon');
            $t->boolean('enabled')->default(false);   // only the distractions built so far are enabled
            $t->timestamps();
        });

        Schema::create('session_distraction_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('task_session_id')->constrained()->cascadeOnDelete();
            $t->foreignId('distraction_id')->constrained();
            $t->decimal('at_progress', 4, 3);
            $t->string('prompt');
            $t->unsignedTinyInteger('prompt_count')->default(1);
            $t->unsignedInteger('window_ms');
            $t->unsignedSmallInteger('damage');
            $t->string('result')->default('pending'); // pending, dismissed, failed
            $t->unsignedSmallInteger('focus_lost')->default(0);
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });

        Schema::create('minigame_configs', function (Blueprint $t) {
            $t->id();
            $t->string('minigame');
            $t->unsignedTinyInteger('difficulty');
            $t->json('params');
            $t->timestamps();
            $t->unique(['minigame', 'difficulty']);
        });

        Schema::create('balance_config', function (Blueprint $t) {
            $t->id();
            $t->string('config_key')->unique();
            $t->string('config_value');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['balance_config', 'minigame_configs', 'session_distraction_events', 'distractions', 'dice_bands',
                  'fate_rolls', 'task_sessions', 'time_blocks', 'day_plans', 'tasks'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['role', 'procrastinator_type_id', 'level', 'xp', 'skill_points', 'coins',
                            'pity_counter', 'slip_streak', 'game_date', 'last_flow_date']);
        });
        Schema::dropIfExists('causes');
        Schema::dropIfExists('procrastinator_types');
    }
};
