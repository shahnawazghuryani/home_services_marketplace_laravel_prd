<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('bio')->nullable();
            $table->unsignedInteger('experience_years')->default(0);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->string('service_area')->nullable();
            $table->string('availability')->default('Mon-Sat, 9 AM - 7 PM');
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
