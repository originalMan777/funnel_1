<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QOQuestion extends Model
{
    protected $table = 'qo_questions';

    protected $fillable = [
        'qo_item_id',
        'type',
        'prompt',
        'helper_text',
        'explanation_text',
        'sort_order',
        'is_required',
        'allow_back_override',
        'auto_advance_override',
        'allow_second_chance_override',
        'max_attempts_override',
        'show_correctness_feedback_override',
        'media_path',
        'settings_json',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'allow_back_override' => 'boolean',
        'auto_advance_override' => 'boolean',
        'allow_second_chance_override' => 'boolean',
        'show_correctness_feedback_override' => 'boolean',
        'settings_json' => 'array',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(QOItem::class, 'qo_item_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QOOption::class, 'qo_question_id')->orderBy('sort_order');
    }
}
