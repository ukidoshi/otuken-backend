<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Ранее эта миграция удаляла страницы complex/location/… и события — scope
 * снова полный (все страницы + события в админке и API). Удаление отключено,
 * чтобы новые деплои не теряли строки.
 *
 * Если миграция уже была применена в старом виде и данные в БД пропали:
 *   php artisan db:seed --class='Database\Seeders\LandingContentsSeeder'
 *   php artisan db:seed --class='Database\Seeders\LandingScenariosSeeder'
 */
return new class extends Migration
{
    public function up(): void
    {
        // Намеренно пусто — baseline восстанавливается идемпотентными сидерами.
    }

    public function down(): void
    {
        //
    }
};
