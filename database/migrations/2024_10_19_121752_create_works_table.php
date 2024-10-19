<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('image');
            $table->string('title');
            $table->string('slug');
            $table->string('timeline');
            $table->string('publish_date');
            $table->string('role');
            $table->json('tags');
            $table->string('project_link')->nullable();
            $table->string('full_image');
            $table->text('overview')->nullable();
            $table->text('learn')->nullable();
            $table->text('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('works');
    }
};
