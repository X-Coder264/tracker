<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map(Registrar $registrar): void
    {
        $this->mapApiRoutes($registrar);

        $this->mapWebRoutes($registrar);
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(Registrar $registrar): void
    {
        $registrar->group(['middleware' => ['web'], 'namespace' => $this->namespace], base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapApiRoutes(Registrar $registrar): void
    {
        $registrar->group(['middleware' => ['api'], 'namespace' => $this->namespace], base_path('routes/api.php'));
    }
}
