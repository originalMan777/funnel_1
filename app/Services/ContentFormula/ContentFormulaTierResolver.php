<?php

namespace App\Services\ContentFormula;

use Illuminate\Contracts\Auth\Authenticatable;

class ContentFormulaTierResolver
{
    public function resolve(?Authenticatable $user): array
    {
        $tier = 'guest';

        if ($user) {
            $tier = data_get($user, 'is_admin') ? 'paid' : 'signed_in';
        }

        $rules = (array) config("content_formula.tiers.{$tier}", []);

        return [
            'name' => $tier,
            'batch_size' => (int) ($rules['batch_size'] ?? 10),
            'reset_limit' => $rules['reset_limit'] ?? null,
            'continue_limit' => $rules['continue_limit'] ?? null,
        ];
    }
}
