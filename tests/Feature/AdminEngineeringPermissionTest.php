<?php

use App\Enums\DocumentStatus;
use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentSigningService;
use Database\Seeders\AdminEngineeringPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

    expect($adminEngineering->fresh()->can('documents.view'))->toBeTrue()
        ->and($adminEngineering->fresh()->can('documents.update'))->toBeTrue();

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
