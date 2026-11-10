<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_admin')->default(false));

        Schema::create('marketplace_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->text('token');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('marketplace_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_id')->nullable()->constrained('marketplace_tokens')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('path');
            $table->text('query')->nullable();
            $table->string('kind');
            $table->unsignedSmallInteger('status');
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('served_sha', 40)->nullable();
            $table->timestamp('created_at')->index();
            $table->index(['user_id', 'kind', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_requests');
        Schema::dropIfExists('marketplace_tokens');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
    }
};
