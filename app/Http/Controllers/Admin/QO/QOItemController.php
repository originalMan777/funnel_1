<?php

namespace App\Http\Controllers\Admin\QO;

use App\Http\Controllers\Controller;
use App\Models\QOItem;
use App\Services\QO\ValidateQOPublishReadinessService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class QOItemController extends Controller
{
    public function index()
    {
        $items = QOItem::query()
            ->withCount(['questions', 'submissions'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/QO/Index', [
            'items' => $items,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/QO/Edit', [
            'item' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request, creating: true);

        $questionCount = (int) ($data['question_count'] ?? 0);
        $defaultQuestionType = $data['default_question_type'] ?? 'single_choice';

        unset($data['question_count'], $data['default_question_type']);

        if (blank($data['slug'] ?? null)) {
            $data['slug'] = Str::slug($data['title']);
        }

        $item = QOItem::create($data);

        for ($i = 1; $i <= $questionCount; $i++) {
            $item->questions()->create([
                'type' => $defaultQuestionType,
                'prompt' => '',
                'sort_order' => $i,
                'is_required' => true,
            ]);
        }

        return redirect()->route('admin.qo.edit', $item->id);
    }

    public function edit($id)
    {
        $item = QOItem::with([
            'questions.options',
            'outcomes',
            'promoRules',
        ])->findOrFail($id);

        return Inertia::render('Admin/QO/Edit', [
            'item' => $item,
            'publishReadiness' => app(\App\Services\QO\ValidateQOPublishReadinessService::class)->validate($item),
        ]);
    }

    public function update(Request $request, $id)
    {
        $item = QOItem::findOrFail($id);

        $data = $this->validateData($request);

        unset($data['question_count'], $data['default_question_type']);

        if (blank($data['slug'] ?? null)) {
            $data['slug'] = Str::slug($data['title']);
        }

        foreach ([
            'allow_back',
            'show_correctness_feedback',
            'allow_second_chance',
            'reveal_correct_answer_after_fail',
            'show_explanations',
        ] as $booleanField) {
            if (array_key_exists($booleanField, $data)) {
                $data[$booleanField] = (bool) $data[$booleanField];
            }
        }

        $item->update($data);

        return back()->with('success', 'Saved.');
    }

    public function publish($id)
    {
        $item = QOItem::findOrFail($id);

        $result = app(ValidateQOPublishReadinessService::class)->validate($item);

        if (! $result['is_publishable']) {
            return back()
                ->with('error', 'Cannot publish this QO item yet.')
                ->with('details', $result);
        }

        $item->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return back()->with('success', 'Published.');
    }

    public function unpublish($id)
    {
        $item = QOItem::findOrFail($id);

        $item->update([
            'status' => 'draft',
            'published_at' => null,
        ]);

        return back()->with('success', 'Unpublished.');
    }

    public function archive($id)
    {
        $item = QOItem::findOrFail($id);

        $item->update([
            'status' => 'archived',
        ]);

        return back()->with('success', 'Archived.');
    }

    public function preview($id)
    {
        $item = QOItem::with([
            'questions.options',
            'outcomes',
            'promoRules',
            'ctaTemplates',
        ])->findOrFail($id);

        return Inertia::render('QO/Show', [
            'item' => $item,
            'isPreview' => true,
        ]);
    }

    protected function validateData(Request $request, bool $creating = false): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'internal_name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:quiz,assessment'],
            'status' => ['nullable', 'in:draft,published,archived'],
            'intro_title' => ['nullable', 'string', 'max:255'],
            'intro_body' => ['nullable', 'string'],
            'start_button_label' => ['nullable', 'string', 'max:255'],
            'interaction_mode' => ['nullable', 'in:evaluative,correctness'],
            'result_mode' => ['nullable', 'in:score_range,category,mixed'],
            'capture_mode' => ['nullable', 'in:open,optional_pre_start,pre_start_gate,optional_pre_result,pre_result_gate,post_result_only,hybrid'],
            'cta_config' => ['nullable', 'array'],
            'allow_back' => ['nullable', 'boolean'],
            'show_correctness_feedback' => ['nullable', 'boolean'],
            'allow_second_chance' => ['nullable', 'boolean'],
            'reveal_correct_answer_after_fail' => ['nullable', 'boolean'],
            'show_explanations' => ['nullable', 'boolean'],

            'question_count' => [$creating ? 'required' : 'nullable', 'integer', 'min:1', 'max:100'],
            'default_question_type' => [$creating ? 'required' : 'nullable', 'in:single_choice,multiple_choice,yes_no,short_text,number'],
        ]);
    }
}
