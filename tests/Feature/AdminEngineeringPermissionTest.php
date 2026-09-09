<?php

use App\Enums\DocumentStatus;
use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentSigningService;
use Database\Seeders\AdminEngineeringPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

it('allows admin engineering to view edit and mark routed documents for revision', function () {
    $uploader = User::factory()->create();
    $signer = User::factory()->create();
    $adminEngineering = User::factory()->create([
        'email' => AdminEngineeringPermissionSeeder::EMAIL,
    ]);
    $category = Category::create(['name' => 'Engineering', 'description' => '-']);
    $document = Document::factory()->for($uploader, 'creator')->create(['category_id' => $category->id]);
    $workflow = app(DocumentSigningService::class);
    $workflow->updateRouting($document, $uploader, [$signer->id]);
    $workflow->start($document);

    $this->seed(AdminEngineeringPermissionSeeder::class);
    $this->seed(AdminEngineeringPermissionSeeder::class);

    expect($adminEngineering->fresh()->can('documents.view'))->toBeTrue()
        ->and($adminEngineering->fresh()->can('documents.update'))->toBeTrue()
        ->and($adminEngineering->fresh()->can('documents.stamp'))->toBeTrue();

    $this->actingAs($adminEngineering)
        ->get(route('dashboard.documents.edit', $document))
        ->assertOk()
        ->assertSee('Tandai Perlu Revisi');

    $this->actingAs($adminEngineering)
        ->put(route('dashboard.documents.update', $document), [
            'title' => $document->title,
            'category_id' => $category->id,
            'action' => 'request_revision',
        ])
        ->assertRedirect(route('dashboard.documents.index'));

    expect($document->fresh()->status)->toBe(DocumentStatus::NeedsRevision);
});

it('allows admin engineering to stamp eligible documents owned by another user', function (DocumentStatus $status) {
    $owner = User::factory()->create();
    $adminEngineering = User::factory()->create([
        'email' => strtoupper(AdminEngineeringPermissionSeeder::EMAIL),
    ]);
    $document = Document::factory()->for($owner, 'creator')->create([
        'status' => $status,
    ]);

    expect(Gate::forUser($adminEngineering)->allows('stamp', $document))->toBeTrue();

    $this->actingAs($adminEngineering)
        ->get(route('dashboard.documents.stamp', $document))
        ->assertOk();

    $this->actingAs($adminEngineering)
        ->from(route('dashboard.documents.stamp', $document))
        ->post(route('dashboard.documents.stamp.store', $document), ['stamps' => ''])
        ->assertRedirect(route('dashboard.documents.stamp', $document))
        ->assertSessionHas('error', 'Tidak ada stampel untuk disimpan.');
})->with([
    DocumentStatus::Signed,
    DocumentStatus::Stamped,
]);

it('does not allow admin engineering to stamp documents before signing is complete', function (DocumentStatus $status) {
    $owner = User::factory()->create();
    $adminEngineering = User::factory()->create([
        'email' => AdminEngineeringPermissionSeeder::EMAIL,
    ]);
    $document = Document::factory()->for($owner, 'creator')->create([
        'status' => $status,
    ]);

    expect(Gate::forUser($adminEngineering)->allows('stamp', $document))->toBeFalse();

    $this->actingAs($adminEngineering)
        ->get(route('dashboard.documents.stamp', $document))
        ->assertForbidden();
})->with([
    DocumentStatus::Uploaded,
    DocumentStatus::WaitingForSignatures,
]);
