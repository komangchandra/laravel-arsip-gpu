<?php

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('documents');
    Storage::fake('public');
    Storage::fake('signature-assets');
    Storage::fake('legacy-signature-assets');
});

it('streams private documents only to authorized users with private headers', function () {
    $owner = User::factory()->create();
    $owner->assignRole('super-admin');
    $unrelated = User::factory()->create();
    $document = Document::factory()->for($owner, 'creator')->create([
        'title' => 'Confidential Report',
        'file_path' => 'documents/confidential.pdf',
    ]);
    Storage::disk('documents')->put($document->file_path, '%PDF-1.4 private');

    $this->actingAs($unrelated)
        ->get(route('dashboard.documents.preview', $document))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('dashboard.documents.preview', $document))
        ->assertOk()
        ->assertHeader('cache-control', 'max-age=0, no-cache, no-store, private')
        ->assertHeader('x-content-type-options', 'nosniff');

    $this->actingAs($owner)
        ->get(route('dashboard.documents.download', $document))
        ->assertOk()
        ->assertDownload('Confidential_Report.pdf');

    $this->actingAs($unrelated)
        ->get(route('dashboard.documents.download', $document))
        ->assertForbidden();
});

it('allows every assigned signer to preview and download the routed document', function () {
    $owner = User::factory()->create();
    $signer = User::factory()->create();
    $document = Document::factory()->for($owner, 'creator')->create([
        'file_path' => 'documents/routed.pdf',
    ]);
    $document->signRoutes()->create([
        'user_id' => $signer->id,
        'created_by' => $owner->id,
        'sequence' => 1,
        'status' => 'pending',
    ]);
    Storage::disk('documents')->put($document->file_path, '%PDF-1.4 routed');

    $this->actingAs($signer)
        ->get(route('dashboard.documents.preview', $document))
        ->assertOk();

    $this->actingAs($signer)
        ->get(route('dashboard.documents.download', $document))
        ->assertOk();
});

it('allows every authenticated user to preview and download archived documents', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $document = Document::factory()->for($owner, 'creator')->create([
        'title' => 'Public Company Archive',
        'status' => 'archived',
        'file_path' => 'documents/archived.pdf',
    ]);
    Storage::disk('documents')->put($document->file_path, '%PDF-1.4 archived');

    $this->actingAs($viewer)
        ->get(route('dashboard.documents.preview', $document))
        ->assertOk();

    $this->actingAs($viewer)
        ->get(route('dashboard.documents.download', $document))
        ->assertOk()
        ->assertDownload('Public_Company_Archive.pdf');
});

it('supports legacy public files only through the authorized controller', function () {
    $owner = User::factory()->create();
    $document = Document::factory()->for($owner, 'creator')->create([
        'file_path' => 'documents/legacy.pdf',
    ]);
    Storage::disk('public')->put($document->file_path, '%PDF-1.4 legacy');

    $this->actingAs($owner)
        ->get(route('dashboard.documents.preview', $document))
        ->assertOk();
});

it('migrates legacy documents to private storage and removes verified sources', function () {
    $document = Document::factory()->create(['file_path' => 'documents/legacy.pdf']);
    Storage::disk('public')->put($document->file_path, '%PDF-1.4 legacy');

    $this->artisan('documents:migrate-private', ['--delete-source' => true])
        ->assertSuccessful();

    Storage::disk('documents')->assertExists($document->file_path);
    Storage::disk('public')->assertMissing($document->file_path);
});

it('does not expose an arbitrary signature asset name', function () {
    $owner = User::factory()->create();
    $owner->assignRole('super-admin');
    $document = Document::factory()->for($owner, 'creator')->create(['status' => 'signed']);

    $this->actingAs($owner)
        ->get(route('dashboard.documents.signature-assets.show', [$document, 'secret.png']))
        ->assertNotFound();
});
