<?php

namespace Mallto\User\Providers;

use Illuminate\Support\ServiceProvider;
use Mallto\User\Domain\UserUsecaseImpl;
use Mallto\User\Domain\UserUsecase;

class UserServiceProvider extends ServiceProvider
{

    /**
     * @var array
     */
    protected $commands = [
        'Mallto\User\Commands\InstallCommand',
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
    ];

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {

        $this->loadMigrationsFrom(__DIR__.'/../../migrations');

        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);

        $this->app->bind(
            UserUsecase::class,
            UserUsecaseImpl::class
        );
    }

}
