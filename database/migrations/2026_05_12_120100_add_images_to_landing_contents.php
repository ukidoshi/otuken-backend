<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Галерея фотографий объекта (не зависит от локали).
 *
 * Храним как упорядоченный JSON-массив относительных путей на диске public:
 *   ["landing-objects/<slug>/abc.webp", "landing-objects/<slug>/def.jpg"]
 *
 * Порядок отражает порядок drag&drop из админки и используется фронтом:
 * первое фото = hero, все вместе = галерея.
 *
 * Поле общее для всех записей таблицы, фактически заполняется только для
 * записей вида `object.<slug>`. Для site_pages/events остаётся null.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Идемпотентная миграция: при первом прогоне DDL может частично примениться
        // (DDL в MySQL не транзакционен), поэтому при повторном запуске не пытаемся
        // создать колонку, если она уже на месте.
        if (Schema::hasColumn('landing_contents', 'images')) {
            return;
        }

        Schema::table('landing_contents', function (Blueprint $table): void {
            $table->json('images')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('landing_contents', 'images')) {
            return;
        }

        Schema::table('landing_contents', function (Blueprint $table): void {
            $table->dropColumn('images');
        });
    }
};
