<?php

namespace App\Providers;

use App\Services\Export\Contract\Exporter;
use App\Services\Export\Service\ExporterFactory;
use App\Services\Export\Service\ExportService;
use App\Services\Export\Service\KeyStore;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

/**
 * DEPENDENCY INJECTION: nơi duy nhất biết lắp ráp các pattern với nhau.
 * Code nghiệp vụ chỉ cần type-hint Exporter / ExportService.
 */
class ExportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(KeyStore::class, fn (Application $app) => new KeyStore(
            $app['config']['export.keys.encryption'],
            Storage::disk($app['config']['export.disk']),
            $app['config']['export.keys.private_key'],
            $app['config']['export.keys.public_key'],
        ));

        $this->app->singleton(ExporterFactory::class, fn (Application $app) => new ExporterFactory(
            $app['config']['export'],
            $app->make(KeyStore::class),
            $app->make('log'),
        ));

        // Exporter mặc định theo config (giống bind DistanceCalculator trong bài viết)
        $this->app->bind(Exporter::class, fn (Application $app) => $app->make(ExporterFactory::class)->make());

        $this->app->bind(ExportService::class, fn (Application $app) => new ExportService(
            $app->make(Exporter::class),
            Storage::disk($app['config']['export.disk']),
        ));
    }
}
