<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Testing\Fakes\MailFake;
use Inertia\Testing\AssertableInertia as Assert;
use ReflectionProperty;
use Tests\TestCase;

class CommunicationComposerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_open_preview_and_send_manual_emails(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.communications.composer.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Communications/EmailComposer')
                ->where('defaults.from_email', (string) config('mail.from.address', ''))
                ->where('defaults.from_name', (string) config('mail.from.name', ''))
            );

        $this->actingAs($admin)
            ->postJson(route('admin.communications.composer.preview'), [
                'subject' => 'Hello {{ recipient.name }}',
                'message' => "First line for {{ recipient.name }}\n\nSecond paragraph",
                'preview_text' => 'Preview {{ recipient.name }}',
                'headline' => 'Headline {{ recipient.name }}',
                'sample_payload' => [
                    'recipient' => ['name' => 'Taylor'],
                ],
            ])
            ->assertOk()
            ->assertJson([
                'rendered' => [
                    'subject' => 'Hello Taylor',
                    'preview_text' => 'Preview Taylor',
                    'headline' => 'Headline Taylor',
                    'text_body' => "First line for Taylor\n\nSecond paragraph",
                ],
            ]);

        $this->actingAs($admin)
            ->from(route('admin.communications.composer.index'))
            ->post(route('admin.communications.composer.send'), [
                'to_email' => 'qa@example.com',
                'to_name' => 'QA Team',
                'from_email' => 'sender@example.com',
                'from_name' => 'Sender Name',
                'subject' => 'Hello {{ recipient.name }}',
                'message' => "First line for {{ recipient.name }}\n\nSecond paragraph",
                'preview_text' => 'Preview {{ recipient.name }}',
                'headline' => 'Headline {{ recipient.name }}',
                'sample_payload' => [
                    'recipient' => ['name' => 'Taylor'],
                ],
            ])
            ->assertRedirect(route('admin.communications.composer.index'))
            ->assertSessionHas('success');

        Mail::assertSentCount(1);

        $mail = $this->sentMailables()->first();

        $this->assertNotNull($mail);
        $mail->build();
        $this->assertTrue($mail->hasTo('qa@example.com'));
        $this->assertSame('sender@example.com', data_get($mail->from, '0.address'));
        $this->assertSame('Sender Name', data_get($mail->from, '0.name'));

        $renderedMail = $mail->render();

        $this->assertSame('Hello Taylor', $mail->subject);
        $this->assertStringContainsString('Preview Taylor', $renderedMail);
        $this->assertStringContainsString('Headline Taylor', $renderedMail);
        $this->assertStringContainsString('First line for Taylor', $renderedMail);
        $this->assertStringContainsString('Second paragraph', $renderedMail);
    }

    public function test_non_admin_users_cannot_access_manual_email_composer_routes(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.communications.composer.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('admin.communications.composer.preview'), [
                'subject' => 'Test',
                'message' => 'Preview body',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.communications.composer.send'), [
                'to_email' => 'qa@example.com',
                'from_email' => 'sender@example.com',
                'subject' => 'Test',
                'message' => 'Send body',
            ])
            ->assertForbidden();
    }

    private function sentMailables(): \Illuminate\Support\Collection
    {
        $fake = Mail::getFacadeRoot();
        $property = new ReflectionProperty(MailFake::class, 'mailables');
        $property->setAccessible(true);

        return collect($property->getValue($fake));
    }
}
