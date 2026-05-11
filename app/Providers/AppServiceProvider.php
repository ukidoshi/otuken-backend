<?php

namespace App\Providers;

use App\Models\News;
use App\Models\User;
use App\Policies\NewsPolicy;
use App\Policies\UserPolicy;
use Filament\Events\ServingFilament;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Временные загрузки Livewire по умолчанию идут на default-диск (у нас это local → storage/app/private).
        // Spatie Media Library пишет обложки на public. Один диск для tmp и финала убирает сбои при переносе/чистке.
        $this->app->booting(function (): void {
            if (config('filesystems.disks.public') && config('filesystems.disks.public.driver') === 'local') {
                config([
                    'livewire.temporary_file_upload.disk' => 'public',
                    'filament.default_filesystem_disk' => 'public',
                ]);
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(News::class, NewsPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Event::listen(ServingFilament::class, function (): void {
            app()->setLocale('ru');
        });

        // Чтобы URL файлов (Spatie /storage/…) совпадали с тем, как вы открываете сайт (например :8081 в Docker),
        // а не с устаревшим APP_URL=http://localhost без порта — иначе превью в Filament «висит», картинки не грузятся.
        $this->app->booted(function (): void {
            if ($this->app->runningInConsole()) {
                return;
            }

            $request = request();

            if ($request === null || $request->getHost() === '') {
                return;
            }

            $root = rtrim($request->getSchemeAndHttpHost(), '/');

            URL::forceRootUrl($root);
            // Иначе Spatie / Storage::url() остаются на env('APP_URL') (часто http://localhost:80 без порта бэка).
            config(['filesystems.disks.public.url' => $root.'/storage']);
        });
    }
}
