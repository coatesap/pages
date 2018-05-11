<?php

namespace Optimus\Pages\Providers;

use Optimus\Pages\Page;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PageServiceProvider extends ServiceProvider
{
    protected $controllerNamespace = 'Optimus\Pages\Http\Controllers';

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->registerBladeDirectives();

        $this->mapAdminRoutes();

        $this->app->bind(Page::class, function () {
            return Page::where('uri', request()->path())
                ->with('templateContents')
                ->firstOrFail();
        });
    }

    protected function mapAdminRoutes()
    {
        Route::prefix('api')
             ->middleware('admin')
             ->namespace($this->controllerNamespace)
             ->group(function () {
                 Route::apiResource('pages', 'PagesController');
                 Route::get('page-templates', 'PageTemplatesController@index');
             });
    }

    protected function registerBladeDirectives()
    {
        Blade::directive('content', function ($contentKey) {
            return "<?php
                echo \$page->templateContents->firstWhere('key', $contentKey)->value ?? null;
            ?>";
        });
    }
}