<?php

namespace App\Http\Controllers\QO;

use App\Http\Controllers\Controller;
use App\Models\QOCapture;
use App\Models\QOItem;
use App\Models\QOSubmission;
use App\Services\QO\AdvanceQOSubmissionService;
use App\Services\QO\CompleteQOSubmissionService;
use App\Services\QO\StartQOSubmissionService;
use Illuminate\Http\Request;

class QORuntimeController extends Controller
{
    public function start(Request $request, string $slug)
    {
        $data = $request->validate([
            'is_preview' => ['nullable', 'boolean'],
        ]);

        $item = QOItem::query()
            ->with(['questions.options'])
            ->where('slug', $slug)
            ->firstOrFail();

        $result = app(StartQOSubmissionService::class)->start($item, [
            'source_url' => $request->headers->get('referer'),
            'referrer_url' => $request->headers->get('referer'),
        ]);

        if (! empty($data['is_preview'])) {
            $result['submission']->update([
                'is_preview' => true,
            ]);
        }

        return response()->json([
            'submission' => [
                'id' => $result['submission']->id,
                'session_uuid' => $result['submission']->session_uuid,
                'status' => $result['submission']->fresh()->status,
                'is_preview' => $result['submission']->fresh()->is_preview,
                'current_question_id' => $result['submission']->fresh()->current_question_id,
            ],
            'current_question_id' => $result['current_question']?->id,
            'state' => $result['state'],
        ]);
    }

    public function answer(Request $request, string $slug)
    {
        $data = $request->validate([
            'submission_id' => ['required', 'integer'],
            'qo_question_id' => ['required', 'integer'],
            'qo_option_id' => ['nullable', 'integer'],
            'qo_option_ids' => ['nullable', 'array'],
            'qo_option_ids.*' => ['integer'],
            'answer_text' => ['nullable', 'string'],
            'answer_number' => ['nullable', 'numeric'],
        ]);

        $submission = QOSubmission::query()
            ->where('id', $data['submission_id'])
            ->whereHas('item', fn ($query) => $query->where('slug', $slug))
            ->firstOrFail();

        $result = app(AdvanceQOSubmissionService::class)->advance($submission, $data);

        return response()->json([
            'submission' => [
                'id' => $result['submission']->id,
                'status' => $result['submission']->fresh()->status,
                'is_preview' => $result['submission']->fresh()->is_preview,
                'current_question_id' => $result['submission']->fresh()->current_question_id,
            ],
            'state' => $result['state'],
            'current_question_id' => $result['current_question']?->id,
            'next_question_id' => $result['next_question']?->id,
            'feedback' => $result['feedback'],
            'is_complete_ready' => $result['is_complete_ready'],
            'answers_count' => $result['answers']->count(),
        ]);
    }

    public function capture(Request $request, string $slug)
    {
        $data = $request->validate([
            'submission_id' => ['nullable', 'integer'],
            'stage' => ['required', 'in:pre_start,mid_assessment,pre_result,post_result'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'payload_json' => ['nullable', 'array'],
            'is_preview' => ['nullable', 'boolean'],
        ]);

        $item = QOItem::query()
            ->where('slug', $slug)
            ->firstOrFail();

        $submission = null;

        if (! empty($data['submission_id'])) {
            $submission = QOSubmission::query()
                ->where('id', $data['submission_id'])
                ->where('qo_item_id', $item->id)
                ->firstOrFail();
        }

        $capture = QOCapture::create([
            'qo_item_id' => $item->id,
            'qo_submission_id' => $submission?->id,
            'stage' => $data['stage'],
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'payload_json' => $data['payload_json'] ?? null,
            'is_preview' => ! empty($data['is_preview']) || $submission?->is_preview === true,
        ]);

        return response()->json([
            'id' => $capture->id,
            'stage' => $capture->stage,
            'is_preview' => $capture->is_preview,
            'message' => 'Information saved.',
        ]);
    }

    public function complete(Request $request, string $slug)
    {
        $data = $request->validate([
            'submission_id' => ['required', 'integer'],
        ]);

        $submission = QOSubmission::query()
            ->where('id', $data['submission_id'])
            ->whereHas('item', fn ($query) => $query->where('slug', $slug))
            ->firstOrFail();

        $result = app(CompleteQOSubmissionService::class)->complete($submission);

        return response()->json([
            'submission' => [
                'id' => $result['submission']->id,
                'status' => $result['submission']->status,
                'final_score' => $result['submission']->final_score,
                'final_category_key' => $result['submission']->final_category_key,
                'final_outcome_key' => $result['submission']->final_outcome_key,
            ],
            'outcome' => $result['outcome'],
            'final_score' => $result['final_score'],
            'final_category_key' => $result['final_category_key'],
            'final_outcome_key' => $result['final_outcome_key'],
        ]);
    }
}
