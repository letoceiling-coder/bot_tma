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
        Schema::table('wheel_sections', function (Blueprint $table) {
            $table->decimal('probability', 5, 2)->default(0)->after('is_active')->comment('Вероятность выпадения сектора в процентах (0-100)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wheel_sections', function (Blueprint $table) {
            $table->dropColumn('probability');
        });
    }
};
