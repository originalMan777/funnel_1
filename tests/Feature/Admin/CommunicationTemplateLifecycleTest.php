<?php

namespace Tests\Feature\Admin;

use App\Models\CommunicationTemplate;
use App\Models\CommunicationTemplateVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Testing\Fakes\MailFake;
use ReflectionProperty;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CommunicationTemplateLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_create_a_template_and_initial_version(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.communications.templates.store'), [
                'key' => 'campaign-follow-up',
                'name' => 'Campaign Follow Up',
                'status' => CommunicationTemplate::STATUS_DRAFT,
                'description' => 'Draft follow up template',
                'from_name_override' => 'Operations',
                'from_email_override' => 'ops@example.com',
                'reply_to_email' => 'reply@example.com',
                'bindings' => [
                    [
                        'event_key' => 'contact.requested',
                        'action_key' => 'contact.user_confirmation',
                        'is_enabled' => true,
                        'priority' => 50,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.communications.templates.edit', CommunicationTemplate::query()->latest('id')->firstOrFail()));

        $template = CommunicationTemplate::query()->where('key', 'campaign-follow-up')->firstOrFail();

        $this->assertSame(CommunicationTemplate::CHANNEL_EMAIL, $template->channel);
        $this->assertSame(CommunicationTemplate::CATEGORY_TRANSACTIONAL, $template->category);
        $this->assertSame($admin->id, $template->created_by);
        $this->assertDatabaseHas('communication_template_bindings', [
            'communication_template_id' => $template->id,
            'event_key' => 'contact.requested',
            'action_key' => 'contact.user_confirmation',
            'priority' => 50,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.communications.templates.versions.store', $template), [
                'subject' => 'Hello {{ recipient.name }}',
                'preview_text' => 'Follow up for {{ template.name }}',
                'headline' => 'Campaign Follow Up',
                'html_body' => '<p>{{ sample.message }}</p>',
                'text_body' => 'Message: {{ sample.message }}',
                'sample_payload' => [
                    'recipient' => ['name' => 'Taylor'],
                    'sample' => ['message' => 'Need help'],
                ],
                'notes' => 'Initial draft',
            ])
            ->assertRedirect();

        $version = CommunicationTemplateVersion::query()
            ->where('communication_template_id', $template->id)
            ->firstOrFail();

        $this->assertSame(1, $version->version_number);
        $this->assertSame($admin->id, $version->created_by);
        $this->assertSame('Hello {{ recipient.name }}', $version->subject);
        $this->assertSame(['recipient' => ['name' => 'Taylor'], 'sample' => ['message' => 'Need help']], $version->sample_payload);

        $this->actingAs($admin)
            ->get(route('admin.communications.templates.edit', $template))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Communications/Templates/Edit')
                ->where('template.current_version', null)
                ->where('template.editor_version.id', $version->id)
                ->where('template.editor_version.subject', 'Hello {{ recipient.name }}')
                ->where('template.editor_version.sample_payload.recipient.name', 'Taylor')
                ->where('template.versions.0.id', $version->id)
            );
    }

    public function test_publishing_switches_the_current_version_and_unpublishes_previous_versions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $template = $this->createTemplate();

        $firstVersion = CommunicationTemplateVersion::query()->create([
            'communication_template_id' => $template->id,
            'version_number' => 1,
            'subject' => 'Version one',
            'html_body' => '<p>One</p>',
            'text_body' => 'One',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $secondVersion = CommunicationTemplateVersion::query()->create([
            'communication_template_id' => $template->id,
            'version_number' => 2,
            'subject' => 'Version two',
            'html_body' => '<p>Two</p>',
            'text_body' => 'Two',
            'is_published' => false,
        ]);

        $template->forceFill(['current_version_id' => $firstVersion->id])->save();

        $this->actingAs($admin)
            ->from(route('admin.communications.templates.show', $template))
            ->post(route('admin.communications.templates.versions.publish', [
                'template' => $template,
                'version' => $secondVersion,
            ]))
            ->assertRedirect(route('admin.communications.templates.show', $template));

        $template->refresh();
        $firstVersion->refresh();
        $secondVersion->refresh();

        $this->assertSame($secondVersion->id, $template->current_version_id);
        $this->assertFalse($firstVersion->is_published);
        $this->assertTrue($secondVersion->is_published);
        $this->assertNotNull($secondVersion->published_at);

        $this->actingAs($admin)
            ->get(route('admin.communications.templates.show', $template))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Communications/Templates/Show')
                ->where('template.current_version.id', $secondVersion->id)
                ->where('template.current_version.is_published', true)
            );
    }

    public function test_preview_renders_template_variables_and_test_send_delivers_the_rendered_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $template = $this->createTemplate([
            'from_name_override' => 'Operations',
            'from_email_override' => 'ops@example.com',
            'reply_to_email' => 'reply@example.com',
        ]);

        $previewResponse = $this->actingAs($admin)
            ->postJson(route('admin.communications.templates.preview', $template), [
                'subject' => 'Hello {{ recipient.name }}',
                'preview_text' => 'Preview {{ template.name }}',
                'headline' => 'Hi {{ recipient.name }}',
                'html_body' => '<p>{{ sample.message }}</p>',
                'text_body' => 'Plain {{ sample.message }}',
                'sample_payload' => [
                    'recipient' => ['name' => 'Taylor'],
                    'sample' => ['message' => 'Need help'],
                ],
            ]);

        $previewResponse
            ->assertOk()
            ->assertJson([
                'rendered' => [
                    'subject' => 'Hello Taylor',
                    'preview_text' => 'Preview Lifecycle Template',
                    'headline' => 'Hi Taylor',
                    'html_body' => '<p>Need help</p>',
                    'text_body' => 'Plain Need help',
                ],
            ]);

        $this->actingAs($admin)
            ->from(route('admin.communications.templates.show', $template))
            ->post(route('admin.communications.templates.test-send', $template), [
                'to_email' => 'qa@example.com',
                'to_name' => 'QA Team',
                'subject' => 'Hello {{ recipient.name }}',
                'preview_text' => 'Preview {{ template.name }}',
                'headline' => 'Hi {{ recipient.name }}',
                'html_body' => '<p>{{ sample.message }}</p>',
                'text_body' => 'Plain {{ sample.message }}',
                'sample_payload' => [
                    'recipient' => ['name' => 'Taylor'],
                    'sample' => ['message' => 'Need help'],
                ],
            ])
            ->assertRedirect(route('admin.communications.templates.show', $template))
            ->assertSessionHas('success');

        Mail::assertSentCount(1);

        $mail = $this->sentMailables()->first();

        $this->assertNotNull($mail);
        $this->assertTrue($mail->hasTo('qa@example.com'));
        $renderedMail = $mail->render();

        $this->assertSame('Hello Taylor', $mail->subject);
        $this->assertStringContainsString('Preview Lifecycle Template', $renderedMail);
        $this->assertStringContainsString('Hi Taylor', $renderedMail);
        $this->assertStringContainsString('Need help', $renderedMail);
    }

    public function test_invalid_template_states_fail_cleanly(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $template = $this->createTemplate();
        $otherTemplate = $this->createTemplate([
            'key' => 'other-template',
            'name' => 'Other Template',
        ]);
        $foreignVersion = CommunicationTemplateVersion::query()->create([
            'communication_template_id' => $otherTemplate->id,
            'version_number' => 1,
            'subject' => 'Other version',
            'html_body' => '<p>Other</p>',
            'text_body' => 'Other',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.communications.templates.create'))
            ->post(route('admin.communications.templates.store'), [
                'key' => 'invalid-status-template',
                'name' => 'Invalid Status Template',
                'status' => 'paused',
            ])
            ->assertRedirect(route('admin.communications.templates.create'))
            ->assertSessionHasErrors(['status']);

        $this->assertDatabaseMissing('communication_templates', [
            'key' => 'invalid-status-template',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.communications.templates.versions.publish', [
                'template' => $template,
                'version' => $foreignVersion,
            ]))
            ->assertNotFound();
    }

    private function createTemplate(array $overrides = []): CommunicationTemplate
    {
        return CommunicationTemplate::query()->create(array_merge([
            'key' => 'lifecycle-template',
            'name' => 'Lifecycle Template',
            'channel' => CommunicationTemplate::CHANNEL_EMAIL,
            'category' => CommunicationTemplate::CATEGORY_TRANSACTIONAL,
            'status' => CommunicationTemplate::STATUS_ACTIVE,
        ], $overrides));
    }

    private function sentMailables(): \Illuminate\Support\Collection
    {
        $fake = Mail::getFacadeRoot();
        $property = new ReflectionProperty(MailFake::class, 'mailables');
        $property->setAccessible(true);

        return collect($property->getValue($fake));
    }
}
