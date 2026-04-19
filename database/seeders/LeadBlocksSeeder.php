<?php

namespace Database\Seeders;

use App\Models\LeadBox;
use App\Models\LeadSlot;
use Illuminate\Database\Seeder;

class LeadBlocksSeeder extends Seeder
{
    public function run(): void
    {
        $slotKeys = config('lead_blocks.slot_keys', ['home_intro', 'home_mid', 'home_bottom']);

        foreach ($slotKeys as $slotKey) {
            if (! is_string($slotKey) || $slotKey === '') {
                continue;
            }

            LeadSlot::query()->updateOrCreate(
                ['key' => $slotKey],
                ['is_enabled' => true],
            );
        }

        LeadBox::query()->updateOrCreate(
            ['internal_name' => 'Default Resource Lead Box'],
            [
                'type' => LeadBox::TYPE_RESOURCE,
                'status' => LeadBox::STATUS_ACTIVE,
                'title' => 'Download Our Free Resource',
                'short_text' => 'Get a helpful resource with practical information you can use right away.',
                'button_text' => 'Get Resource',
                'icon_key' => 'book-open',
                'content' => [
                    'visual_preset' => 'default',
                ],
                'settings' => [],
            ],
        );

        LeadBox::query()->updateOrCreate(
            ['internal_name' => 'Default Service Lead Box'],
            [
                'type' => LeadBox::TYPE_SERVICE,
                'status' => LeadBox::STATUS_ACTIVE,
                'title' => 'Request a Service Consultation',
                'short_text' => 'Tell us what you need help with and we will guide you to the next step.',
                'button_text' => 'Request Service',
                'icon_key' => null,
                'content' => [
                    'cta_line' => 'Need help? Let’s talk through it.',
                    'reassurance_text' => 'Simple, clear, and no pressure.',
                    'value_points' => [
                        ['icon_key' => 'shield-check', 'line' => 'Clear guidance'],
                        ['icon_key' => 'clock', 'line' => 'Fast response'],
                        ['icon_key' => 'message-square', 'line' => 'Helpful next steps'],
                    ],
                ],
                'settings' => [],
            ],
        );

        LeadBox::query()->updateOrCreate(
            ['internal_name' => 'Default Offer Lead Box'],
            [
                'type' => LeadBox::TYPE_OFFER,
                'status' => LeadBox::STATUS_ACTIVE,
                'title' => 'Claim This Special Offer',
                'short_text' => 'Take the next step with a simple offer designed to get you started quickly.',
                'button_text' => 'Claim Offer',
                'icon_key' => null,
                'content' => [
                    'breakdown_line_2' => 'A simple starting point if you are ready to move forward.',
                    'cta_line' => 'Ready to take the next step?',
                    'reassurance_text' => 'No pressure. Just a clear offer.',
                    'value_points' => [
                        ['icon_key' => 'sparkles', 'line' => 'Easy to start'],
                        ['icon_key' => 'check-circle-2', 'line' => 'Clear value'],
                        ['icon_key' => 'clock', 'line' => 'Quick action'],
                    ],
                ],
                'settings' => [],
            ],
        );
    }
}
