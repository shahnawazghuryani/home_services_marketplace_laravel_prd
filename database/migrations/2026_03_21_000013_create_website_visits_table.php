<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_key', 80)->index();
            $table->string('path', 255)->index();
            $table->text('full_url');
            $table->text('referrer_url')->nullable();
            $table->string('source', 255)->nullable()->index();
            $table->string('device_type', 30)->nullable();
            $table->timestamp('visited_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_visits');
    }
};
