<?php

namespace App\Http\Controllers\Admin\QO;

use App\Http\Controllers\Controller;
use App\Models\QOItem;
use App\Services\QO\DuplicateQOItemService;

class QOItemDuplicateController extends Controller
{
    public function __invoke($id, DuplicateQOItemService $duplicator)
    {
        $item = QOItem::with([
            'questions.options',
            'outcomes',
            'promoRules',
            'ctaTemplates',
        ])->findOrFail($id);

        $duplicate = $duplicator->duplicate($item);

        return redirect()
            ->route('admin.qo.edit', $duplicate->id)
            ->with('success', 'QO item duplicated as draft.');
    }
}
