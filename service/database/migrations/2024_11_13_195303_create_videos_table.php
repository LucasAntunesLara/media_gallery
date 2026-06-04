<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url', 255);
            $table->string('thumbnail', 255)->default('');
            $table->string('tags')->nullable();
            $table->boolean('isFavourite')->default(false);
            $table->boolean('isPrivate')->nullable()->default(false);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('videos');
    }
};
