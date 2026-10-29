<?php

use App\Enums\DocumentStatus;
use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentSigningService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->document = Document::factory()->for($this->owner, 'creator')->create([
        'category_id' => Category::create(['name' => 'Full sign', 'description' => '-'])->id,
    ]);
    $this->workflow = app(DocumentSigningService::class);
    $this->actingAs($this->owner);
});

it('moves a completed routing to Full Sign regardless of signer identity or count', function (int $count, bool $ferryLast) {
    $signers = User::factory()->count($count)->create();
    if ($ferryLast) {
        $signers->last()->update(['email' => 'ferry.juanda@gorbyputrautama.com']);
    }
    $this->workflow->updateRouting($this->document, $this->owner, $signers->modelKeys());
    $this->workflow->start($this->document);

    foreach ($signers as $signer) {
        $this->workflow->recordSignature($this->document, $signer, 'documents/signed.pdf');
    }

    $this->get(route('dashboard.full-sign.index'))->assertOk()
        ->assertViewHas('documents', fn ($documents) => $documents->modelKeys() === [$this->document->id]);
    $this->get(route('dashboard.documents.index'))->assertOk()
        ->assertViewHas('documents', fn ($documents) => $documents->isEmpty());

    expect($this->document->fresh()->current_sign_route_id)->toBeNull()
        ->and($this->document->fresh()->signing_completed_at)->not->toBeNull();

    $viewer = User::factory()->create();
    $this->actingAs($viewer)->get(route('dashboard.full-sign.index'))->assertOk()
        ->assertSee($this->document->title)
        ->assertDontSee(route('dashboard.documents.download', $this->document), false);
    $this->get(route('dashboard.documents.preview', $this->document))->assertForbidden();
    $this->get(route('dashboard.documents.download', $this->document))->assertForbidden();
})->with([[1, false], [3, false], [3, true]]);

it('keeps unfinished routing visible after Ferry signs in an earlier turn', function (int $ferryPosition) {
    $signers = User::factory()->count(3)->create();
    $signers[$ferryPosition]->update(['email' => 'ferry.juanda@gorbyputrautama.com']);
    $this->workflow->updateRouting($this->document, $this->owner, $signers->modelKeys());
    $this->workflow->start($this->document);
    foreach ($signers->take(2) as $signer) {
        $this->workflow->recordSignature($this->document, $signer, 'documents/partial.pdf');
    }

    $this->get(route('dashboard.documents.index'))->assertOk()
        ->assertViewHas('documents', fn ($documents) => $documents->modelKeys() === [$this->document->id]);
    $this->get(route('dashboard.full-sign.index'))->assertOk()
        ->assertViewHas('documents', fn ($documents) => $documents->isEmpty());
    expect($this->document->fresh()->signing_completed_at)->toBeNull();
})->with([0, 1]);

it('keeps inconsistent signed documents in the main list without treating them as completed', function (string $anomaly) {
    $signer = User::factory()->create();
    $this->workflow->updateRouting($this->document, $this->owner, [$signer->id]);
    $this->workflow->start($this->document);
    $this->workflow->recordSignature($this->document, $signer, 'documents/signed.pdf');
    $this->document->refresh();

    match ($anomaly) {
        'empty' => $this->document->signRoutes()->delete(),
        'pending', 'active', 'revision_requested', 'cancelled' => $this->document->signRoutes()->update(['status' => $anomaly]),
        'started_at' => $this->document->update(['routing_started_at' => null]),
        'completed_at' => $this->document->update(['signing_completed_at' => null]),
        'pointer' => $this->document->update(['current_sign_route_id' => $this->document->signRoutes()->first()->id]),
    };

    $this->get(route('dashboard.full-sign.index'))->assertOk()
        ->assertViewHas('documents', fn ($documents) => $documents->isEmpty());
    $this->get(route('dashboard.documents.index'))->assertOk()
        ->assertViewHas('documents', fn ($documents) => $documents->modelKeys() === [$this->document->id]);
    $this->get(route('dashboard'))->assertOk()->assertViewHas('fullThisMonth', 0);
})->with(['empty', 'pending', 'active', 'revision_requested', 'cancelled', 'started_at', 'completed_at', 'pointer']);

it('does not classify stopped routing as Full Sign', function (bool $revision) {
    $signers = User::factory()->count(2)->create();
    $this->workflow->updateRouting($this->document, $this->owner, $signers->modelKeys());
    $this->workflow->start($this->document);
    if ($revision) {
        $this->workflow->requestRevision($this->document, $signers->first(), 'Perbaiki dokumen.');
    } else {
        $this->workflow->cancel($this->document);
    }

    $this->get(route('dashboard.full-sign.index'))->assertOk()
        ->assertViewHas('documents', fn ($documents) => $documents->isEmpty());
    $this->get(route('dashboard'))->assertOk()->assertViewHas('fullThisMonth', 0);
    expect($this->document->fresh()->signing_completed_at)->toBeNull();
})->with([true, false]);

it('rejects an out of turn signature without completing the routing', function () {
    $signers = User::factory()->count(2)->create();
    $this->workflow->updateRouting($this->document, $this->owner, $signers->modelKeys());
    $this->workflow->start($this->document);

    $this->actingAs($signers->last())->post(route('dashboard.documents.sign.store', $this->document), [
        'signed_pages' => ['data:image/png;base64,aW52YWxpZA=='],
    ])->assertForbidden();

    expect($this->document->fresh()->status)->toBe(DocumentStatus::WaitingForSignatures)
        ->and($this->document->fresh()->signing_completed_at)->toBeNull()
        ->and($this->document->signedBy()->count())->toBe(0);
});

it('moves a document to Full Sign after the final ordinary signature is saved', function () {
    Storage::fake('documents');
    Storage::disk('documents')->makeDirectory('documents');
    $pdf = new FPDF;
    $pdf->AddPage();
    $pdf->Output('F', Storage::disk('documents')->path($this->document->file_path));
    $signer = User::factory()->create();
    $this->workflow->updateRouting($this->document, $this->owner, [$signer->id]);
    $this->workflow->start($this->document);

    $this->actingAs($signer)->post(route('dashboard.documents.sign.store', $this->document), [
        'signed_pages' => [1 => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='],
    ])->assertRedirect(route('dashboard.signing-inbox.index'));

    $this->get(route('dashboard.full-sign.index'))->assertOk()
        ->assertViewHas('documents', fn ($documents) => $documents->modelKeys() === [$this->document->id]);
    $this->get(route('dashboard.documents.index'))->assertOk()
        ->assertViewHas('documents', fn ($documents) => $documents->isEmpty());
    Storage::disk('documents')->assertExists($this->document->fresh()->file_path);
});

it('keeps process status and date filters grouped correctly in the main list', function (string $status) {
    $this->document->update(['status' => $status, 'created_at' => '2026-09-01 12:00:00']);
    $this->get(route('dashboard.documents.index'))->assertOk()
        ->assertViewHas('documents', fn ($documents) => $documents->modelKeys() === [$this->document->id]);
    $this->get(route('dashboard.documents.index', ['start_date' => '2026-10-01']))->assertOk()
        ->assertViewHas('documents', fn ($documents) => $documents->isEmpty());
})->with(['routing', 'waiting_for_signatures', 'ready_to_sign', 'signed']);
