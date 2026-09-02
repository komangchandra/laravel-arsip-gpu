<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->timestamp('routing_started_at')->nullable()->after('status');
            $table->timestamp('signing_completed_at')->nullable()->after('routing_started_at');
            $table->foreignId('current_sign_route_id')->nullable()->after('signing_completed_at');
            $table->foreignId('revision_of_document_id')
                ->nullable()
                ->after('category_id')
                ->constrained('documents')
                ->nullOnDelete();
        });

        Schema::create('document_sign_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('status')->default('pending');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('revision_requested_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'user_id']);
            $table->unique(['document_id', 'sequence']);
            $table->index(['user_id', 'status']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('current_sign_route_id')->references('id')->on('document_sign_routes')->nullOnDelete();
        });

        Schema::table('document_signeds', function (Blueprint $table) {
            $table->unique(['document_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['current_sign_route_id']);
            $table->dropConstrainedForeignId('revision_of_document_id');
            $table->dropColumn(['routing_started_at', 'signing_completed_at', 'current_sign_route_id']);
        });

        Schema::table('document_signeds', function (Blueprint $table) {
            $table->dropUnique(['document_id', 'user_id']);
        });

        Schema::dropIfExists('document_sign_routes');
    }
};
