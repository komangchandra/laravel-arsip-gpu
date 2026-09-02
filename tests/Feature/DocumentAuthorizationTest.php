<?php

use App\Enums\DocumentStatus;
use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentSignRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['super-admin', 'staff', 'staff-haul'] as $role) {
        Role::findOrCreate($role, 'web');
    }
});

it('redirects guests from every sensitive document endpoint', function (string $method, string $route, array $parameters = []) {
    $document = Document::factory()->create();
    $parameters = array_merge(['document' => $document], $parameters);

    $this->call($method, route($route, $parameters))->assertRedirect(route('login'));
})->with([
    ['GET', 'dashboard.documents.edit'],
    ['PUT', 'dashboard.documents.update'],
    ['DELETE', 'dashboard.documents.destroy'],
    ['GET', 'dashboard.documents.download'],
    ['GET', 'dashboard.documents.annotate'],
    ['POST', 'dashboard.documents.annotateUpload'],
    ['GET', 'dashboard.documents.stamp'],
    ['POST', 'dashboard.documents.stamp.store'],
    ['GET', 'dashboard.documents.sign'],
    ['GET', 'dashboard.documents.sign-tempel'],
    ['POST', 'dashboard.documents.sign-tempel.store'],
    ['GET', 'dashboard.documents.sign-routing.edit'],
]);

it('denies unrelated users from sensitive document endpoints', function (string $method, string $route) {
    $document = Document::factory()->create();
    $unrelated = User::factory()->create();

    $this->actingAs($unrelated)->call($method, route($route, $document))->assertForbidden();
})->with([
    ['GET', 'dashboard.documents.edit'],
    ['PUT', 'dashboard.documents.update'],
    ['DELETE', 'dashboard.documents.destroy'],
    ['GET', 'dashboard.documents.annotate'],
    ['POST', 'dashboard.documents.annotateUpload'],
    ['GET', 'dashboard.documents.stamp'],
    ['POST', 'dashboard.documents.stamp.store'],
]);

it('uses the uploader signer and administrator access matrix', function () {
    $uploader = User::factory()->create();
    $signer = User::factory()->create();
    $unrelated = User::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    $document = Document::factory()->for($uploader, 'creator')->create();
    DocumentSignRoute::create([
        'document_id' => $document->id,
        'user_id' => $signer->id,
        'created_by' => $uploader->id,
        'sequence' => 1,
        'status' => 'pending',
    ]);

    expect(Gate::forUser($uploader)->allows('view', $document))->toBeTrue()
        ->and(Gate::forUser($uploader)->allows('download', $document))->toBeTrue()
        ->and(Gate::forUser($uploader)->allows('update', $document))->toBeTrue()
        ->and(Gate::forUser($uploader)->allows('delete', $document))->toBeTrue()
        ->and(Gate::forUser($signer)->allows('view', $document))->toBeTrue()
        ->and(Gate::forUser($signer)->allows('download', $document))->toBeTrue()
        ->and(Gate::forUser($signer)->allows('update', $document))->toBeFalse()
        ->and(Gate::forUser($unrelated)->allows('view', $document))->toBeFalse()
        ->and(Gate::forUser($unrelated)->allows('download', $document))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('view', $document))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('annotate', $document))->toBeTrue();
});

it('allows admin engineering to access document files owned by another user', function () {
    $owner = User::factory()->create();
    $adminEngineering = User::factory()->create([
        'email' => 'admin.engineering@gorbyputrautama.com',
    ]);
    $document = Document::factory()->for($owner, 'creator')->create();

    expect(Gate::forUser($adminEngineering)->allows('view', $document))->toBeTrue()
        ->and(Gate::forUser($adminEngineering)->allows('download', $document))->toBeTrue();
});

it('only permits stamping a signed document by an operational role with access', function () {
    $staffUploader = User::factory()->create();
    $staffUploader->assignRole('staff');
    $otherStaff = User::factory()->create();
    $otherStaff->assignRole('staff');
    $document = Document::factory()->for($staffUploader, 'creator')->create(['status' => DocumentStatus::Signed]);

    expect(Gate::forUser($staffUploader)->allows('stamp', $document))->toBeTrue()
        ->and(Gate::forUser($otherStaff)->allows('stamp', $document))->toBeFalse();

    $document->update(['status' => DocumentStatus::Uploaded]);
    expect(Gate::forUser($staffUploader)->allows('stamp', $document->fresh()))->toBeFalse();
});

it('allows an authorized operational user to stamp an already stamped document again', function () {
    $staffUploader = User::factory()->create();
    $staffUploader->assignRole('staff');
    $category = Category::create(['name' => 'Stamped test', 'description' => '-']);
    $document = Document::factory()->for($staffUploader, 'creator')->create([
        'title' => 'Document with an existing stamp',
        'status' => DocumentStatus::Stamped,
        'category_id' => $category->id,
    ]);

    expect(Gate::forUser($staffUploader)->allows('stamp', $document))->toBeTrue();

    $this->actingAs($staffUploader)
        ->get(route('dashboard.stamped.index'))
        ->assertOk()
        ->assertSee(route('dashboard.documents.stamp', $document), false);
});

it('allows the uploader to archive signed or stamped documents', function (DocumentStatus $status) {
    $uploader = User::factory()->create();
    $document = Document::factory()->for($uploader, 'creator')->create(['status' => $status]);

    $this->actingAs($uploader)
        ->patch(route('dashboard.documents.archive', $document))
        ->assertRedirect(route('dashboard.archiveds.index'));

    expect($document->fresh()->status)->toBe(DocumentStatus::Archived);
})->with([
    DocumentStatus::Signed,
    DocumentStatus::Stamped,
]);

it('does not let an unrelated user archive a document', function () {
    $uploader = User::factory()->create();
    $unrelated = User::factory()->create();
    $document = Document::factory()->for($uploader, 'creator')->create([
        'status' => DocumentStatus::Stamped,
    ]);

    $this->actingAs($unrelated)
        ->patch(route('dashboard.documents.archive', $document))
        ->assertForbidden();

    expect($document->fresh()->status)->toBe(DocumentStatus::Stamped);
});

it('does not allow super admin to bypass the active signer rule', function () {
    $uploader = User::factory()->create();
    $signer = User::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    $document = Document::factory()->for($uploader, 'creator')->create([
        'status' => DocumentStatus::WaitingForSignatures,
        'routing_started_at' => now(),
    ]);
    $route = DocumentSignRoute::create([
        'document_id' => $document->id,
        'user_id' => $signer->id,
        'created_by' => $uploader->id,
        'sequence' => 1,
        'status' => 'active',
        'activated_at' => now(),
    ]);
    $document->update(['current_sign_route_id' => $route->id]);

    $this->actingAs($admin)->get(route('dashboard.documents.sign', $document))->assertForbidden();
    $this->actingAs($signer)->get(route('dashboard.documents.sign', $document))->assertOk();
});

it('allows only the uploader to manage start and cancel routing', function () {
    $uploader = User::factory()->create();
    $other = User::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    $document = Document::factory()->for($uploader, 'creator')->create(['status' => DocumentStatus::Routing]);
    DocumentSignRoute::create([
        'document_id' => $document->id,
        'user_id' => $other->id,
        'created_by' => $uploader->id,
        'sequence' => 1,
        'status' => 'pending',
    ]);

    expect(Gate::forUser($uploader)->allows('manageSignRouting', $document))->toBeTrue()
        ->and(Gate::forUser($uploader)->allows('startSignRouting', $document))->toBeTrue()
        ->and(Gate::forUser($uploader)->allows('cancelSignRouting', $document))->toBeTrue()
        ->and(Gate::forUser($other)->allows('manageSignRouting', $document))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('manageSignRouting', $document))->toBeFalse();
});

it('allows super admin to delete a document in any workflow status', function () {
    $uploader = User::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    $document = Document::factory()->for($uploader, 'creator')->create([
        'status' => DocumentStatus::WaitingForSignatures,
        'routing_started_at' => now(),
    ]);

    expect(Gate::forUser($admin)->allows('delete', $document))->toBeTrue()
        ->and(Gate::forUser($uploader)->allows('delete', $document))->toBeFalse();

    $this->actingAs($admin)
        ->delete(route('dashboard.documents.destroy', $document))
        ->assertRedirect(route('dashboard.documents.index'));

    $this->assertDatabaseMissing('documents', ['id' => $document->id]);
});

it('shows all document metadata in listings without granting file access', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $category = Category::create(['name' => 'Authorization test', 'description' => '-']);
    $owned = Document::factory()->for($owner, 'creator')->create(['title' => 'Owned document', 'category_id' => $category->id]);
    $hidden = Document::factory()->for($other, 'creator')->create(['title' => 'Hidden document', 'category_id' => $category->id]);

    $this->actingAs($owner)
        ->getJson('/api/documents')
        ->assertOk()
        ->assertJsonFragment(['id' => $owned->id])
        ->assertJsonFragment(['id' => $hidden->id]);

    $this->actingAs($owner)
        ->get(route('dashboard.recently-uploaded.index'))
        ->assertOk()
        ->assertSee('Owned document')
        ->assertSee('Hidden document');

    $this->actingAs($owner)
        ->getJson('/api/documents/'.$hidden->id.'/show')
        ->assertForbidden();
});

it('protects sign tempel and keeps the removed api signing bypass unavailable', function () {
    $document = Document::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard.documents.sign-tempel', $document))->assertForbidden();
    $this->actingAs($user)->postJson('/api/documents/'.$document->id.'/sign')->assertNotFound();
});
