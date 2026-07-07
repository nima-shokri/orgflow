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
        Schema::create('process_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('process_key', 120);
            $table->string('name');
            $table->unsignedInteger('version');
            $table->string('status', 20)->default('draft');
            $table->longText('bpmn_xml');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['process_key', 'version']);
            $table->index(['process_key', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_definitions');
    }
};
