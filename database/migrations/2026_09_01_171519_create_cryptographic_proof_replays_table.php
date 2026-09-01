<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cryptographic_proof_replays', function (Blueprint $table) {
            $table->id();

            $table->string('jti', 100)->unique();

            $table->unsignedBigInteger('binding_id');

            $table->dateTime('issued_at');
            $table->dateTime('expires_at');

            $table->timestamps();

            $table->foreign('binding_id')
                ->references('id')
                ->on('cryptographic_session_bindings')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cryptographic_proof_replays');
    }
};