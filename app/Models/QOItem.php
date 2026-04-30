<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class QOItem extends Model
{
    protected $table = 'qo_items';

    protected $fillable = [
        'title',
        'internal_name',
        'slug',
        'type',
        'status',
        'intro_title',
        'intro_body',
        'start_button_label',
        'interaction_mode',
        'result_mode',
        'show_progress_bar',
        'show_question_numbers',
        'allow_back',
        'auto_advance',
        'show_correctness_feedback',
        'allow_second_chance',
        'max_attempts_per_question',
        'reveal_correct_answer_after_fail',
        'show_explanations',
        'capture_mode',
        'cta_config',
        'requires_name',
        'requires_email',
        'requires_phone',
        'intro_lead_box_id',
        'inline_promo_mode',
        'inline_lead_box_id',
        'inline_rotation_interval',
        'pre_outcome_lead_box_id',
        'post_outcome_lead_box_id',
        'thank_you_title',
        'thank_you_body',
        'success_redirect_url',
        'published_at',
    ];

    protected $casts = [
        'cta_config' => 'array',
        'show_progress_bar' => 'boolean',
        'show_question_numbers' => 'boolean',
        'allow_back' => 'boolean',
        'auto_advance' => 'boolean',
        'show_correctness_feedback' => 'boolean',
        'allow_second_chance' => 'boolean',
        'reveal_correct_answer_after_fail' => 'boolean',
        'show_explanations' => 'boolean',
        'requires_name' => 'boolean',
        'requires_email' => 'boolean',
        'requires_phone' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(QOQuestion::class, 'qo_item_id')->orderBy('sort_order');
    }

    public function options(): HasManyThrough
    {
        return $this->hasManyThrough(
            QOOption::class,
            QOQuestion::class,
            'qo_item_id',
            'qo_question_id',
            'id',
            'id'
        );
    }

    public function outcomes(): HasMany
    {
        return $this->hasMany(QOOutcome::class, 'qo_item_id')->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(QOSubmission::class, 'qo_item_id');
    }

    public function captures(): HasMany
    {
        return $this->hasMany(QOCapture::class, 'qo_item_id');
    }

    public function promoRules(): HasMany
    {
        return $this->hasMany(QOPromoRule::class, 'qo_item_id')
            ->orderBy('priority')
            ->orderBy('id');
    }


    public function ctaTemplates(): HasMany
    {
        return $this->hasMany(QOCtaTemplate::class, 'qo_item_id')
            ->orderBy('priority')
            ->orderBy('id');
    }


    public function isQuiz(): bool
    {
        return $this->type === 'quiz';
    }

    public function isAssessment(): bool
    {
        return $this->type === 'assessment';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
