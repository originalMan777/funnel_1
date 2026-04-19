<?php

namespace Tests\Feature\Public\Popups;

use App\Jobs\ProcessCommunicationEventJob;
use App\Models\CommunicationEvent;
use App\Models\Popup;
use App\Models\PopupLead;
use App\Services\Communications\Contracts\TransactionalEmailProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\Communications\FakeTransactionalEmailProvider;
use Tests\TestCase;

class PopupSubmissionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_popup_submission_creates_a_popup_communication_event(): void
    {
        Queue::fake();
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);

        $popup = Popup::factory()->create([
            'target_pages' => ['home'],
            'form_fields' => ['name', 'email'],
            'audience' => 'guests',
        ]);

        $this->from(route('home'))
            ->post(route('popup-leads.store'), [
                'popup_id' => $popup->id,
                'page_key' => 'home',
                'name' => 'Popup Person',
                'email' => 'popup@example.com',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHas('popupLeadSuccess');

        $this->assertDatabaseHas('communication_events', [
            'event_key' => 'popup.submitted',
            'subject_type' => PopupLead::class,
            'status' => CommunicationEvent::STATUS_PENDING,
        ]);

        $this->assertSame(1, CommunicationEvent::query()->count());
        Queue::assertPushed(ProcessCommunicationEventJob::class);
    }
}
