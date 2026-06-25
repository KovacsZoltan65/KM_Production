<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DocumentManagementUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_document_can_be_uploaded(): void
    {
        [$user, $item] = [$this->verifiedUser('production-manager'), Item::factory()->create()];

        $this->actingAs($user)
            ->post(route('admin.documents.store'), $this->uploadPayload($item, 'drawing-a.pdf', 'A'))
            ->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'documentable_type' => Item::class,
            'documentable_id' => $item->id,
            'document_type' => DocumentType::Drawing->value,
            'original_filename' => 'drawing-a.pdf',
            'version' => 1,
            'is_current' => true,
            'approved' => false,
            'uploaded_by' => $user->id,
        ]);
    }

    public function test_checksum_is_saved(): void
    {
        $item = Item::factory()->create();
        $content = 'checksum source';

        $this->actingAs($this->verifiedUser('production-manager'))
            ->post(route('admin.documents.store'), $this->uploadPayload($item, 'checksum.pdf', $content))
            ->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'original_filename' => 'checksum.pdf',
            'checksum' => hash('sha256', $content),
        ]);
    }

    public function test_version_is_incremented_automatically(): void
    {
        $item = Item::factory()->create();
        $user = $this->verifiedUser('production-manager');

        $this->uploadDocument($user, $item, 'first.pdf', 'one');
        $this->uploadDocument($user, $item, 'second.pdf', 'two');

        $this->assertDatabaseHas('documents', ['original_filename' => 'second.pdf', 'version' => 2]);
    }

    public function test_new_version_is_current(): void
    {
        [$first, $second] = $this->twoVersionFixture();

        $this->assertFalse($first->refresh()->is_current);
        $this->assertTrue($second->refresh()->is_current);
    }

    public function test_old_current_is_set_false_when_new_version_is_uploaded(): void
    {
        [$first] = $this->twoVersionFixture();

        $this->assertDatabaseHas('documents', [
            'id' => $first->id,
            'is_current' => false,
        ]);
    }

    public function test_document_can_be_approved(): void
    {
        $user = $this->verifiedUser('quality-manager');
        $document = $this->uploadedDocument();

        $this->actingAs($user)
            ->patch(route('admin.documents.approve', $document))
            ->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'approved' => true,
            'approved_by' => $user->id,
        ]);
        $this->assertNotNull($document->refresh()->approved_at);
    }

    public function test_approved_document_cannot_be_approved_again(): void
    {
        $document = $this->uploadedDocument();
        $user = $this->verifiedUser('quality-manager');

        $this->actingAs($user)->patch(route('admin.documents.approve', $document))->assertRedirect();
        $this->actingAs($user)
            ->patch(route('admin.documents.approve', $document))
            ->assertSessionHasErrors('document');
    }

    public function test_make_current_works(): void
    {
        [$first, $second] = $this->twoVersionFixture();

        $this->actingAs($this->verifiedUser('production-manager'))
            ->patch(route('admin.documents.make-current', $first))
            ->assertRedirect();

        $this->assertTrue($first->refresh()->is_current);
        $this->assertFalse($second->refresh()->is_current);
    }

    public function test_only_one_version_is_current(): void
    {
        [$first] = $this->twoVersionFixture();

        $this->actingAs($this->verifiedUser('production-manager'))
            ->patch(route('admin.documents.make-current', $first))
            ->assertRedirect();

        $this->assertSame(1, Document::query()->where('is_current', true)->count());
    }

    public function test_document_download_works(): void
    {
        $document = $this->uploadedDocument('download.pdf', 'download content');

        $this->actingAs($this->verifiedUser('worker'))
            ->get(route('admin.documents.download', $document))
            ->assertOk();
    }

    public function test_download_is_forbidden_without_permission(): void
    {
        $document = $this->uploadedDocument();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get(route('admin.documents.download', $document))
            ->assertForbidden();
    }

    public function test_version_history_is_returned_on_show_page(): void
    {
        [, $second] = $this->twoVersionFixture();

        $this->actingAs($this->verifiedUser('production-manager'))
            ->get(route('admin.documents.show', $second))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Documents/Show')
                ->has('versions', 2)
                ->where('versions.0.version', 2));
    }

    public function test_audit_log_is_created_on_upload(): void
    {
        $document = $this->uploadedDocument();

        $activity = Activity::query()->where('event', 'document_uploaded')->firstOrFail();
        $this->assertTrue($activity->subject->is($document));
    }

    public function test_audit_log_is_created_on_approve(): void
    {
        $document = $this->uploadedDocument();
        $user = $this->verifiedUser('quality-manager');

        $this->actingAs($user)->patch(route('admin.documents.approve', $document))->assertRedirect();

        $this->assertTrue(Activity::query()->where('event', 'document_approved')->exists());
    }

    public function test_audit_log_is_created_on_make_current(): void
    {
        [$first] = $this->twoVersionFixture();

        $this->actingAs($this->verifiedUser('production-manager'))
            ->patch(route('admin.documents.make-current', $first))
            ->assertRedirect();

        $this->assertTrue(Activity::query()->where('event', 'document_current_changed')->exists());
    }

    public function test_document_search_works(): void
    {
        Document::factory()->create(['title' => 'Needle drawing', 'original_filename' => 'needle.pdf']);
        Document::factory()->create(['title' => 'Other drawing', 'original_filename' => 'other.pdf']);

        $this->actingAs($this->verifiedUser('production-manager'))
            ->get(route('admin.documents.index', ['search' => 'Needle']))
            ->assertOk()
            ->assertSee('Needle drawing')
            ->assertDontSee('Other drawing');
    }

    public function test_document_filters_work(): void
    {
        Document::factory()->create([
            'title' => 'Approved report',
            'document_type' => DocumentType::QualityReport,
            'approved' => true,
            'is_current' => true,
        ]);
        Document::factory()->create([
            'title' => 'Pending drawing',
            'document_type' => DocumentType::Drawing,
            'approved' => false,
            'is_current' => false,
        ]);

        $this->actingAs($this->verifiedUser('production-manager'))
            ->get(route('admin.documents.index', [
                'document_type' => DocumentType::QualityReport->value,
                'approved' => '1',
                'is_current' => '1',
            ]))
            ->assertOk()
            ->assertSee('Approved report')
            ->assertDontSee('Pending drawing');
    }

    private function verifiedUser(?string $role = null): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function uploadPayload(Item $item, string $filename, string $content): array
    {
        return [
            'file' => UploadedFile::fake()->createWithContent($filename, $content),
            'document_type' => DocumentType::Drawing->value,
            'documentable_type' => 'item',
            'documentable_id' => $item->id,
            'title' => $filename,
            'notes' => 'Controlled document.',
        ];
    }

    private function uploadDocument(User $user, Item $item, string $filename, string $content): Document
    {
        $this->actingAs($user)
            ->post(route('admin.documents.store'), $this->uploadPayload($item, $filename, $content))
            ->assertRedirect();

        return Document::query()->where('original_filename', $filename)->firstOrFail();
    }

    private function uploadedDocument(string $filename = 'document.pdf', string $content = 'document content'): Document
    {
        return $this->uploadDocument($this->verifiedUser('production-manager'), Item::factory()->create(), $filename, $content);
    }

    /**
     * @return array{0: Document, 1: Document}
     */
    private function twoVersionFixture(): array
    {
        $item = Item::factory()->create();
        $user = $this->verifiedUser('production-manager');

        return [
            $this->uploadDocument($user, $item, 'version-one.pdf', 'one'),
            $this->uploadDocument($user, $item, 'version-two.pdf', 'two'),
        ];
    }
}
