<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('nightwatch-github.table'), function (Blueprint $table): void {
            $table->id();
            $table->uuid('nightwatch_issue_id')->unique();
            $table->string('github_repository');
            $table->unsignedBigInteger('github_issue_number');
            $table->string('last_event');
            $table->timestamps();

            $table->unique(['github_repository', 'github_issue_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('nightwatch-github.table'));
    }
};
