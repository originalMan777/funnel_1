<?php

namespace App\Http\Controllers\Admin\QO;

use App\Http\Controllers\Controller;
use App\Models\QOItem;
use Illuminate\Http\Request;

class QOQuestionController extends Controller
{
    public function update(Request $request, $itemId, $questionId)
    {
        $item = QOItem::findOrFail($itemId);

        $question = $item->questions()
            ->where('id', $questionId)
            ->firstOrFail();

        $data = $request->validate([
            'type' => ['required', 'in:single_choice,multiple_choice,yes_no,short_text,number'],
            'prompt' => ['nullable', 'string'],
            'helper_text' => ['nullable', 'string'],
            'explanation_text' => ['nullable', 'string'],
            'is_required' => ['boolean'],

            'settings_json' => ['nullable', 'array'],
            'settings_json.placeholder' => ['nullable', 'string', 'max:255'],
            'settings_json.min_length' => ['nullable', 'integer', 'min:0'],
            'settings_json.max_length' => ['nullable', 'integer', 'min:0'],
            'settings_json.min' => ['nullable', 'numeric'],
            'settings_json.max' => ['nullable', 'numeric'],
            'settings_json.step' => ['nullable', 'numeric'],
        ]);

        $data['prompt'] = $data['prompt'] ?? '';
        $data['helper_text'] = $data['helper_text'] ?? null;
        $data['explanation_text'] = $data['explanation_text'] ?? null;

        $question->update($data);

        return back()->with('success', 'Question saved.');
    }

    public function store(Request $request, $itemId)
    {
        $item = QOItem::findOrFail($itemId);

        $data = $request->validate([
            'type' => ['required', 'in:single_choice,multiple_choice,yes_no,short_text,number'],
        ]);

        $nextOrder = ((int) $item->questions()->max('sort_order')) + 1;

        $item->questions()->create([
            'type' => $data['type'],
            'prompt' => '',
            'sort_order' => $nextOrder,
            'is_required' => true,
            'settings_json' => $this->defaultSettingsForType($data['type']),
        ]);

        return back()->with('success', 'Question added.');
    }

    public function destroy($itemId, $questionId)
    {
        $item = QOItem::findOrFail($itemId);

        $question = $item->questions()
            ->where('id', $questionId)
            ->firstOrFail();

        $question->delete();

        return back()->with('success', 'Question deleted.');
    }

    protected function defaultSettingsForType(string $type): array
    {
        return match ($type) {
            'short_text' => [
                'placeholder' => 'Type your answer here...',
                'min_length' => null,
                'max_length' => 255,
            ],
            'number' => [
                'placeholder' => 'Enter a number...',
                'min' => null,
                'max' => null,
                'step' => 1,
            ],
            default => [],
        };
    }
}
