<?php

namespace Kudastech\Bible;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class BibleServiceProvider extends PackageServiceProvider
{
    public static string $name = 'bible';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(Bible::class, function () {
            return new Bible();
        });

        $this->app->alias(Bible::class, 'bible');
    }

    public function packageBooted(): void
    {
        $this->publishes([
            __DIR__ . '/../bibles' => storage_path('app/bibles'),
        ], 'bible-files');
    }
}