<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cryptographic_session_bindings')) {
            return;
        }

        Schema::create('cryptographic_session_bindings', function (Blueprint $table) {
            $table->id();

            $table->string('session_id')->unique();

            // Mendukung UUID, ULID, atau Integer User ID
            $table->string('user_id', 64)->index();

            $table->json('public_key');

            $table->string('algorithm', 20)->default('ECDSA');
            $table->string('curve', 20)->default('P-256');
            $table->string('digest', 20)->default('SHA-256');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')
                ->useCurrent()
                ->useCurrentOnUpdate();

            $table->timestamp('revoked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cryptographic_session_bindings');
    }
};

