<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QOOption extends Model
{
    protected $table = 'qo_options';

    protected $fillable = [
        'qo_question_id',
        'label',
        'description',
        'value',
        'sort_order',
        'is_correct',
        'score_value',
        'category_key',
        'outcome_key',
        'correct_feedback_text',
        'incorrect_feedback_text',
        'media_path',
        'icon_key',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(QOQuestion::class, 'qo_question_id');
    }
}
