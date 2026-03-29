<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guide_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('audience')->nullable();
            $table->text('summary')->nullable();
            $table->string('duration', 20)->nullable();
            $table->json('steps')->nullable();
            $table->json('voiceover')->nullable();
            $table->json('captions')->nullable();
            $table->string('video_type', 20)->default('youtube');
            $table->string('video_url')->nullable();
            $table->string('video_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_videos');
    }
};
