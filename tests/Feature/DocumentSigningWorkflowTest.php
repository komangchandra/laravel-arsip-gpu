<?php

use App\Enums\DocumentStatus;
use App\Enums\SignRouteStatus;
use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentSigningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('runs sequential signing and only activates the next signer', function () {
    $uploader = User::factory()->create();
    $first = User::factory()->create();
    $second = User::factory()->create();
    $document = Document::factory()->for($uploader, 'creator')->create();
    $service = app(DocumentSigningService::class);

    $service->updateRouting($document, $uploader, [$first->id, $second->id]);
    $service->start($document);

    expect($document->fresh()->status)->toBe(DocumentStatus::WaitingForSignatures)
        ->and($document->signRoutes()->first()->status)->toBe(SignRouteStatus::Active)
        ->and($document->signRoutes()->reorder('sequence', 'desc')->first()->status)->toBe(SignRouteStatus::Pending);

    $service->recordSignature($document, $first, 'documents/first-signed.pdf');
    expect($document->fresh()->isAwaitingSignatureFrom($second))->toBeTrue()
        ->and($document->fresh()->status)->toBe(DocumentStatus::WaitingForSignatures);

    $service->recordSignature($document, $second, 'documents/final-signed.pdf');
    expect($document->fresh()->status)->toBe(DocumentStatus::Signed)
        ->and($document->fresh()->signing_completed_at)->not->toBeNull()
        ->and($document->signedBy()->count())->toBe(2);
});

it('permanently stops pending routes when revision is requested', function () {
    $uploader = User::factory()->create();
    $first = User::factory()->create(['name' => 'Revision Requester']);
    $second = User::factory()->create();
    $category = Category::create(['name' => 'Revision notes', 'description' => '-']);
    $document = Document::factory()->for($uploader, 'creator')->create([
        'title' => 'Document With Revision Note',
        'category_id' => $category->id,
    ]);
    $service = app(DocumentSigningService::class);
    $service->updateRouting($document, $uploader, [$first->id, $second->id]);
    $service->start($document);

    $service->requestRevision($document, $first, 'Mohon perbaiki nominal kontrak.');

    expect($document->fresh()->status)->toBe(DocumentStatus::NeedsRevision)
        ->and($document->signRoutes()->first()->status)->toBe(SignRouteStatus::RevisionRequested)
        ->and($document->signRoutes()->reorder('sequence', 'desc')->first()->status)->toBe(SignRouteStatus::Cancelled);

    $this->actingAs($uploader)
        ->get(route('dashboard.revisions.index'))
        ->assertOk()
        ->assertSee('Mohon perbaiki nominal kontrak.')
        ->assertSee('Revision Requester');

    $this->actingAs($uploader)
        ->get(route('dashboard.documents.edit', $document))
        ->assertOk()
        ->assertSee('Catatan revisi dari Revision Requester')
        ->assertSee('Mohon perbaiki nominal kontrak.');
});

it('forbids a signer whose turn has not started', function () {
    $uploader = User::factory()->create();
    $first = User::factory()->create();
    $second = User::factory()->create();
    $document = Document::factory()->for($uploader, 'creator')->create();
    $service = app(DocumentSigningService::class);
    $service->updateRouting($document, $uploader, [$first->id, $second->id]);
    $service->start($document);

    $this->actingAs($second)->get(route('dashboard.documents.sign', $document))->assertForbidden();
    $this->actingAs($first)->get(route('dashboard.documents.sign', $document))->assertOk();
});

it('offers sign tempel only to the active ktt and records it in the routing workflow', function () {
    Storage::fake('documents');
    Storage::fake('signature-assets');
    Role::findOrCreate('ktt', 'web');

    $uploader = User::factory()->create();
    $ktt = User::factory()->create();
    $ktt->assignRole('ktt');
    $category = Category::create(['name' => 'Sign tempel', 'description' => '-']);
    $document = Document::factory()->for($uploader, 'creator')->create([
        'title' => 'KTT Sign Tempel Document',
        'category_id' => $category->id,
        'file_path' => 'documents/original.pdf',
    ]);

    Storage::disk('documents')->makeDirectory('documents');
    $pdf = new FPDF;
    $pdf->AddPage();
    $pdf->Output('F', Storage::disk('documents')->path($document->file_path));
    Storage::disk('signature-assets')->put('sign-wahyu.png', base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
    ));

    $service = app(DocumentSigningService::class);
    $service->updateRouting($document, $uploader, [$ktt->id]);
    $service->start($document);

    $this->actingAs($ktt)
        ->get(route('dashboard.documents.index'))
        ->assertOk()
        ->assertSee(route('dashboard.documents.sign-tempel', $document), false);

    $this->actingAs($ktt)
        ->post(route('dashboard.documents.sign-tempel.store', $document), [
            'stamps' => json_encode([
                1 => [[
                    'type' => 'gpu',
                    'x_ratio' => 0.1,
                    'y_ratio' => 0.1,
                    'width_ratio' => 0.2,
                    'height_ratio' => 0.1,
                    'rotation' => 15,
                ]],
            ]),
        ])
        ->assertRedirect(route('dashboard.signing-inbox.index'));

    $document->refresh();
    expect($document->status)->toBe(DocumentStatus::Signed)
        ->and($document->signRoutes()->first()->status)->toBe(SignRouteStatus::Signed)
        ->and($document->signedBy()->whereKey($ktt->id)->exists())->toBeTrue();
    Storage::disk('documents')->assertExists($document->file_path);
    Storage::disk('documents')->assertMissing('documents/original.pdf');
});

it('lets the uploader monitor an active signing route', function () {
    $uploader = User::factory()->create();
    $signer = User::factory()->create(['name' => 'Current Signer']);
    $category = Category::create(['name' => 'Monitoring', 'description' => '-']);
    $document = Document::factory()->for($uploader, 'creator')->create([
        'title' => 'Monitored Document',
        'category_id' => $category->id,
    ]);
    $service = app(DocumentSigningService::class);
    $service->updateRouting($document, $uploader, [$signer->id]);
    $service->start($document);

    $this->actingAs($uploader)
        ->get(route('dashboard.documents.index'))
        ->assertOk()
        ->assertSee('Monitored Document')
        ->assertSee('Current Signer')
        ->assertSee('Sedang menunggu tanda tangan');
});

it('lets every authenticated user monitor another users active signing progress', function () {
    $uploader = User::factory()->create();
    $signer = User::factory()->create(['name' => 'Visible Active Signer']);
    $viewer = User::factory()->create();
    $category = Category::create(['name' => 'Shared Monitoring', 'description' => '-']);
    $document = Document::factory()->for($uploader, 'creator')->create([
        'title' => 'Shared Progress Document',
        'category_id' => $category->id,
    ]);
    $service = app(DocumentSigningService::class);
    $service->updateRouting($document, $uploader, [$signer->id]);
    $service->start($document);

    $this->actingAs($viewer)
        ->get(route('dashboard.documents.index'))
        ->assertOk()
        ->assertSee('Shared Progress Document')
        ->assertSee('Visible Active Signer')
        ->assertSee('Sedang menunggu tanda tangan');

    $this->actingAs($viewer)
        ->get(route('dashboard.documents.preview', $document))
        ->assertForbidden();
});

it('lets the uploader edit metadata without changing file or workflow after routing starts', function () {
    $uploader = User::factory()->create();
    $signer = User::factory()->create();
    $oldCategory = Category::create(['name' => 'Old category', 'description' => '-']);
    $newCategory = Category::create(['name' => 'New category', 'description' => '-']);
    $document = Document::factory()->for($uploader, 'creator')->create([
        'title' => 'Wrong title',
        'category_id' => $oldCategory->id,
        'file_path' => 'documents/original.pdf',
    ]);
    $service = app(DocumentSigningService::class);
    $service->updateRouting($document, $uploader, [$signer->id]);
    $service->start($document);

    $this->actingAs($uploader)
        ->get(route('dashboard.documents.edit', $document))
        ->assertOk()
        ->assertSee('file dan status dokumen dikunci')
        ->assertDontSee('Ganti File');

    $this->actingAs($uploader)
        ->put(route('dashboard.documents.update', $document), [
            'title' => 'Correct title',
            'category_id' => $newCategory->id,
        ])
        ->assertRedirect(route('dashboard.documents.index'));

    $document->refresh();
    expect($document->title)->toBe('Correct title')
        ->and($document->category_id)->toBe($newCategory->id)
        ->and($document->file_path)->toBe('documents/original.pdf')
        ->and($document->status)->toBe(DocumentStatus::WaitingForSignatures);
});

it('lets the uploader mark an active routed document for revision from edit', function () {
    $uploader = User::factory()->create();
    $signer = User::factory()->create();
    $category = Category::create(['name' => 'Revision action', 'description' => '-']);
    $document = Document::factory()->for($uploader, 'creator')->create(['category_id' => $category->id]);
    $service = app(DocumentSigningService::class);
    $service->updateRouting($document, $uploader, [$signer->id]);
    $service->start($document);

    $this->actingAs($uploader)
        ->put(route('dashboard.documents.update', $document), [
            'title' => $document->title,
            'category_id' => $category->id,
            'action' => 'request_revision',
        ])
        ->assertRedirect(route('dashboard.documents.index'));

    expect($document->fresh()->status)->toBe(DocumentStatus::NeedsRevision)
        ->and($document->fresh()->current_sign_route_id)->toBeNull()
        ->and($document->signRoutes()->first()->status)->toBe(SignRouteStatus::Cancelled);
});
