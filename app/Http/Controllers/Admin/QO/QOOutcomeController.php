<?php

namespace App\Http\Controllers\Admin\QO;

use App\Http\Controllers\Controller;
use App\Models\QOItem;
use Illuminate\Http\Request;

class QOOutcomeController extends Controller
{
    public function store(Request $request, $itemId)
    {
        $item = QOItem::findOrFail($itemId);

        $nextOrder = ((int) $item->outcomes()->max('sort_order')) + 1;

        $item->outcomes()->create([
            'outcome_key' => 'outcome_' . $nextOrder,
            'title' => 'Outcome ' . $nextOrder,
            'summary' => '',
            'sort_order' => $nextOrder,
            'min_score' => null,
            'max_score' => null,
            'category_key' => null,
        ]);

        return back()->with('success', 'Outcome added.');
    }

    public function update(Request $request, $itemId, $outcomeId)
    {
        $outcome = QOItem::findOrFail($itemId)
            ->outcomes()
            ->where('id', $outcomeId)
            ->firstOrFail();

        $data = $request->validate([
            'outcome_key' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'result_headline' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'interpretation' => ['nullable', 'string'],
            'breakdown_points' => ['nullable', 'array'],
            'breakdown_points.*' => ['nullable', 'string', 'max:255'],
            'next_steps' => ['nullable', 'array'],
            'next_steps.*' => ['nullable', 'string', 'max:255'],
            'min_score' => ['nullable', 'numeric'],
            'max_score' => ['nullable', 'numeric'],
            'category_key' => ['nullable', 'string', 'max:255'],
            'cta_label' => ['nullable', 'string', 'max:255'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'lead_box_id' => ['nullable', 'integer'],
        ]);

        $data['summary'] = $data['summary'] ?? '';
        $data['breakdown_points'] = $this->cleanTextList($data['breakdown_points'] ?? []);
        $data['next_steps'] = $this->cleanTextList($data['next_steps'] ?? []);

        $outcome->update($data);

        return back()->with('success', 'Outcome saved.');
    }

    /**
     * @param array<int, string|null> $values
     * @return array<int, string>
     */
    private function cleanTextList(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => is_string($value) ? trim($value) : '')
            ->filter()
            ->values()
            ->all();
    }

    public function destroy($itemId, $outcomeId)
    {
        $outcome = QOItem::findOrFail($itemId)
            ->outcomes()
            ->where('id', $outcomeId)
            ->firstOrFail();

        $outcome->delete();

        return back()->with('success', 'Outcome deleted.');
    }
}
