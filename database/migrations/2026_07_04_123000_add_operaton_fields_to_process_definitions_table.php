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
        Schema::table('process_definitions', function (Blueprint $table) {
            $table->string('engine_deployment_id')->nullable()->after('published_at');
            $table->string('engine_process_definition_id')->nullable()->after('engine_deployment_id');
            $table->timestamp('engine_deployed_at')->nullable()->after('engine_process_definition_id');
            $table->text('engine_deployment_error')->nullable()->after('engine_deployed_at');

            $table->index('engine_deployment_id');
            $table->index('engine_process_definition_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_definitions', function (Blueprint $table) {
            $table->dropIndex(['engine_deployment_id']);
            $table->dropIndex(['engine_process_definition_id']);
            $table->dropColumn([
                'engine_deployment_id',
                'engine_process_definition_id',
                'engine_deployed_at',
                'engine_deployment_error',
            ]);
        });
    }
};
