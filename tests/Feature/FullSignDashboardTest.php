<?php

use App\Models\Document;
use App\Models\User;
use App\Services\DocumentSigningService;

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 10, 15)->setTime(12, 0));
    $this->owner = User::factory()->create();
    $this->actingAs($this->owner);
    $this->completeDocument = function (string $completedAt, string $status = 'signed'): Document {
        $document = Document::factory()->for($this->owner, 'creator')->create();
        $signers = User::factory()->count(2)->create();
        $workflow = app(DocumentSigningService::class);
        $workflow->updateRouting($document, $this->owner, $signers->modelKeys());
        $workflow->start($document);
        foreach ($signers as $signer) {
            $workflow->recordSignature($document, $signer, 'documents/signed.pdf');
        }
        $document->refresh()->update(['status' => $status, 'signing_completed_at' => $completedAt]);

        return $document->fresh();
    };
});

it('counts completions once through metadata edits stamping and archiving', function () {
    $document = ($this->completeDocument)('2026-10-10 12:00:00');
    $this->get(route('dashboard'))->assertOk()->assertViewHas('fullThisMonth', 1);

    $document->update(['title' => 'Judul diperbarui']);
    foreach (['stamped', 'archived'] as $status) {
        $document->update(['status' => $status]);
        $this->get(route('dashboard'))->assertOk()->assertViewHas('fullThisMonth', 1);
        $this->get(route('dashboard.full-sign.index'))->assertOk()
            ->assertViewHas('documents', fn ($documents) => $documents->isEmpty());
    }
});

it('uses the completion month rather than upload update or archive dates', function () {
    ($this->completeDocument)('2026-09-30 23:59:59', 'archived');
    ($this->completeDocument)('2025-10-10 12:00:00', 'signed');
    $current = ($this->completeDocument)('2026-10-01 00:00:00', 'stamped');
    $current->update(['created_at' => '2026-08-01 00:00:00']);
    ($this->completeDocument)('2026-10-31 23:59:59', 'archived');
    ($this->completeDocument)('2026-11-01 00:00:00');
    Document::factory()->for($this->owner, 'creator')->create(['status' => 'archived']);

    expect(config('app.timezone'))->toBe('Asia/Jakarta');
    $this->get(route('dashboard'))->assertOk()->assertViewHas('fullThisMonth', 2);
});

it('excludes invalid lifecycle states even when completion evidence remains', function (string $status) {
    ($this->completeDocument)('2026-10-10 12:00:00', $status);
    $this->get(route('dashboard'))->assertOk()->assertViewHas('fullThisMonth', 0);
})->with(['uploaded', 'routing', 'waiting_for_signatures', 'ready_to_sign', 'needs_revision', 'cancelled']);

it('counts a revision record only after its own routing completes', function () {
    $original = ($this->completeDocument)('2026-09-10 12:00:00', 'archived');
    $revision = Document::factory()->for($this->owner, 'creator')->create([
        'revision_of_document_id' => $original->id,
    ]);
    $this->get(route('dashboard'))->assertOk()->assertViewHas('fullThisMonth', 0);

    $signer = User::factory()->create();
    $workflow = app(DocumentSigningService::class);
    $workflow->updateRouting($revision, $this->owner, [$signer->id]);
    $workflow->start($revision);
    $workflow->recordSignature($revision, $signer, 'documents/revision.pdf');
    $this->get(route('dashboard'))->assertOk()->assertViewHas('fullThisMonth', 1);
});
