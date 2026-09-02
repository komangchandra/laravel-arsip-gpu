<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('documents', 'current_sign_route_id')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->foreignId('current_sign_route_id')->nullable()->after('signing_completed_at');
                $table->foreign('current_sign_route_id')->references('id')->on('document_sign_routes')->nullOnDelete();
            });
        }

        $hasUnique = collect(Schema::getIndexes('document_signeds'))
            ->contains(fn (array $index) => $index['unique'] && $index['columns'] === ['document_id', 'user_id']);
        if (! $hasUnique) {
            Schema::table('document_signeds', fn (Blueprint $table) => $table->unique(['document_id', 'user_id']));
        }
    }

    public function down(): void
    {
        // Compatibility migration intentionally leaves schema managed by the main migration.
    }
};
