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
        // 1) Add soft-delete column to targets
        Schema::table('targets', function (Blueprint $table) {
            $table->softDeletes();
        });

        // 2) Add soft-delete column to monthly_targets
        Schema::table('monthly_targets', function (Blueprint $table) {
            $table->softDeletes();
        });

        // 3) Change user_targets.target_id FK: CASCADE → RESTRICT
        Schema::table('user_targets', function (Blueprint $table) {
            $table->dropForeign(['target_id']);
            $table->foreign('target_id')
                  ->references('id')
                  ->on('targets')
                  ->restrictOnDelete();
        });

        // 4) Change user_monthly_targets.monthly_target_id FK: CASCADE → RESTRICT
        Schema::table('user_monthly_targets', function (Blueprint $table) {
            $table->dropForeign(['monthly_target_id']);
            $table->foreign('monthly_target_id')
                  ->references('id')
                  ->on('monthly_targets')
                  ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert user_monthly_targets FK back to CASCADE
        Schema::table('user_monthly_targets', function (Blueprint $table) {
            $table->dropForeign(['monthly_target_id']);
            $table->foreign('monthly_target_id')
                  ->references('id')
                  ->on('monthly_targets')
                  ->cascadeOnDelete();
        });

        // Revert user_targets FK back to CASCADE
        Schema::table('user_targets', function (Blueprint $table) {
            $table->dropForeign(['target_id']);
            $table->foreign('target_id')
                  ->references('id')
                  ->on('targets')
                  ->cascadeOnDelete();
        });

        // Remove soft-delete columns
        Schema::table('monthly_targets', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('targets', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
