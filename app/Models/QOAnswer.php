<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QOAnswer extends Model
{
    protected $table = 'qo_answers';

    protected $fillable = [
        'qo_submission_id',
        'qo_question_id',
        'qo_option_id',
        'answer_text',
        'answer_number',
        'answer_json',
        'score_value',
        'category_key',
        'outcome_key',
        'is_correct',
        'attempt_number',
        'is_final_attempt',
    ];

    protected $casts = [
        'answer_json' => 'array',
        'is_correct' => 'boolean',
        'is_final_attempt' => 'boolean',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(QOSubmission::class, 'qo_submission_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QOQuestion::class, 'qo_question_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(QOOption::class, 'qo_option_id');
    }
}
