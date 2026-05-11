<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table): void {
            $table->boolean('is_actuality_highlight')->default(false)->after('approved_at');
            $table->index('is_actuality_highlight');
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table): void {
            $table->dropIndex(['is_actuality_highlight']);
            $table->dropColumn('is_actuality_highlight');
        });
    }
};
