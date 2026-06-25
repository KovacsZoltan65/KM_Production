<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Item;
use App\Models\ProductionOrder;
use App\Models\ProductionTask;
use App\Models\QualityCheck;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_documents_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('documents'));
    }

    public function test_document_can_belong_to_item_polymorphically(): void
    {
        $item = Item::factory()->create();
        $document = Document::factory()->create([
            'documentable_type' => $item::class,
            'documentable_id' => $item->id,
        ]);

        $this->assertTrue($document->documentable->is($item));
    }

    public function test_document_can_belong_to_production_order_polymorphically(): void
    {
        $productionOrder = ProductionOrder::factory()->create();
        $document = Document::factory()->create([
            'documentable_type' => $productionOrder::class,
            'documentable_id' => $productionOrder->id,
        ]);

        $this->assertTrue($document->documentable->is($productionOrder));
    }

    public function test_document_can_belong_to_production_task_polymorphically(): void
    {
        $productionTask = ProductionTask::factory()->create();
        $document = Document::factory()->create([
            'documentable_type' => $productionTask::class,
            'documentable_id' => $productionTask->id,
        ]);

        $this->assertTrue($document->documentable->is($productionTask));
    }

    public function test_document_can_belong_to_quality_check_polymorphically(): void
    {
        $qualityCheck = QualityCheck::factory()->create();
        $document = Document::factory()->create([
            'documentable_type' => $qualityCheck::class,
            'documentable_id' => $qualityCheck->id,
        ]);

        $this->assertTrue($document->documentable->is($qualityCheck));
    }

    public function test_document_uploader_can_be_user(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->create([
            'uploaded_by' => $user->id,
        ]);

        $this->assertTrue($document->uploader->is($user));
    }

    public function test_document_approver_can_be_user(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->create([
            'approved_by' => $user->id,
        ]);

        $this->assertTrue($document->approver->is($user));
    }

    public function test_document_type_enum_stores_correct_value(): void
    {
        $document = Document::factory()->create([
            'document_type' => DocumentType::QualityReport,
        ]);

        $this->assertSame(DocumentType::QualityReport, $document->document_type);
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'document_type' => DocumentType::QualityReport->value,
        ]);
    }

    public function test_document_version_and_is_current_fields_work(): void
    {
        $document = Document::factory()->create([
            'version' => 2,
            'is_current' => false,
        ]);

        $this->assertSame(2, $document->version);
        $this->assertFalse($document->is_current);
    }

    public function test_audit_log_service_can_create_activity_log_record(): void
    {
        app(AuditLogService::class)->log('document.test.logged', properties: [
            'source' => 'test',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'event' => 'document.test.logged',
            'description' => 'document.test.logged',
        ]);
    }

    public function test_activity_log_record_contains_event_name(): void
    {
        app(AuditLogService::class)->log('document.test.event');

        $activity = Activity::query()->firstOrFail();

        $this->assertSame('document.test.event', $activity->event);
    }

    public function test_activity_log_record_can_belong_to_subject_model(): void
    {
        $document = Document::factory()->create();

        app(AuditLogService::class)->log('document.test.subject', $document);

        $activity = Activity::query()->firstOrFail();

        $this->assertTrue($activity->subject->is($document));
    }
}
