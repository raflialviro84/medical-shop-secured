<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cryptographic_session_bindings', function (Blueprint $table) {
            $table->id();

            $table->string('session_id')->unique();

            $table->char('user_id', 36);

            $table->json('public_key');

            $table->string('algorithm', 20)->default('ECDSA');
            $table->string('curve', 20)->default('P-256');
            $table->string('digest', 20)->default('SHA-256');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')
                ->useCurrent()
                ->useCurrentOnUpdate();

            $table->timestamp('revoked_at')->nullable();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cryptographic_session_bindings');
    }
};