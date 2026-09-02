<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cryptographic_navigation_grants', function (Blueprint $table) {
            $table->id();

            $table->string('token', 64)->unique();

            $table->unsignedBigInteger('user_id');
            $table->string('session_id');
            $table->string('binding_id', 36);

            $table->string('method', 10);
            $table->string('path', 2048);

            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();

            $table->timestamps();

            $table->index(['session_id', 'binding_id']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cryptographic_navigation_grants');
    }
};