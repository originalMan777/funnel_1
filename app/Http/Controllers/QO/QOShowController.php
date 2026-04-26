<?php

namespace App\Http\Controllers\QO;

use App\Http\Controllers\Controller;
use App\Models\QOItem;
use Inertia\Inertia;

class QOShowController extends Controller
{
    public function show(string $slug)
    {
        $item = QOItem::query()
            ->with([
                'questions.options',
                'outcomes',
                'promoRules',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        return Inertia::render('QO/Show', [
            'item' => $item,
        ]);
    }
}
