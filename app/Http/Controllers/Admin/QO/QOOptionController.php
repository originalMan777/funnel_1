<?php

namespace App\Http\Controllers\Admin\QO;

use App\Http\Controllers\Controller;
use App\Models\QOItem;
use Illuminate\Http\Request;

class QOOptionController extends Controller
{
    public function store(Request $request, $itemId, $questionId)
    {
        $question = QOItem::findOrFail($itemId)
            ->questions()
            ->where('id', $questionId)
            ->firstOrFail();

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'score_value' => ['nullable', 'numeric'],
            'is_correct' => ['nullable', 'boolean'],
            'category_key' => ['nullable', 'string', 'max:255'],
        ]);

        $nextOrder = ((int) $question->options()->max('sort_order')) + 1;

        $question->options()->create([
            'label' => $data['label'] ?? 'Option ' . $nextOrder,
            'sort_order' => $nextOrder,
            'score_value' => $data['score_value'] ?? 0,
            'is_correct' => $data['is_correct'] ?? false,
            'category_key' => $data['category_key'] ?? null,
        ]);

        return back()->with('success', 'Answer slot added.');
    }

    public function update(Request $request, $itemId, $questionId, $optionId)
    {
        $option = QOItem::findOrFail($itemId)
            ->questions()
            ->where('id', $questionId)
            ->firstOrFail()
            ->options()
            ->where('id', $optionId)
            ->firstOrFail();

        $data = $request->validate([
            'label' => ['required', 'string'],
            'score_value' => ['nullable', 'numeric'],
            'is_correct' => ['boolean'],
            'category_key' => ['nullable', 'string'],
        ]);

        $option->update($data);

        return back()->with('success', 'Answer slot saved.');
    }

    public function ensureYesNo($itemId, $questionId)
    {
        $question = QOItem::findOrFail($itemId)
            ->questions()
            ->where('id', $questionId)
            ->firstOrFail();

        $question->update(['type' => 'yes_no']);
        $question->options()->delete();

        $question->options()->create([
            'label' => 'Yes',
            'sort_order' => 1,
            'score_value' => 1,
            'is_correct' => false,
        ]);

        $question->options()->create([
            'label' => 'No',
            'sort_order' => 2,
            'score_value' => 0,
            'is_correct' => false,
        ]);

        return back()->with('success', 'Yes/No answer slots created.');
    }

    public function resetForType(Request $request, $itemId, $questionId)
    {
        $question = QOItem::findOrFail($itemId)
            ->questions()
            ->where('id', $questionId)
            ->firstOrFail();

        $data = $request->validate([
            'type' => ['required', 'in:single_choice,multiple_choice,yes_no,short_text,number'],
        ]);

        $question->update([
            'type' => $data['type'],
        ]);

        $question->refresh();
        $question->options()->delete();

        if ($question->type === 'yes_no') {
            $question->options()->create([
                'label' => 'Yes',
                'sort_order' => 1,
                'score_value' => 1,
                'is_correct' => false,
            ]);

            $question->options()->create([
                'label' => 'No',
                'sort_order' => 2,
                'score_value' => 0,
                'is_correct' => false,
            ]);
        }

        if (in_array($question->type, ['single_choice', 'multiple_choice'], true)) {
            for ($i = 1; $i <= 4; $i++) {
                $question->options()->create([
                    'label' => 'Option ' . $i,
                    'sort_order' => $i,
                    'score_value' => 0,
                    'is_correct' => false,
                ]);
            }
        }

        return back()->with('success', 'Answer slots reset.');
    }

    public function destroy($itemId, $questionId, $optionId)
    {
        $option = QOItem::findOrFail($itemId)
            ->questions()
            ->where('id', $questionId)
            ->firstOrFail()
            ->options()
            ->where('id', $optionId)
            ->firstOrFail();

        $option->delete();

        return back()->with('success', 'Answer slot deleted.');
    }
}
