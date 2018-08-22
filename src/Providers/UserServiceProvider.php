<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Mallto\User\Domain\UserUsecase;
use Mallto\User\Domain\UserUsecaseImpl;

class UserServiceProvider extends ServiceProvider
{


    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'Laravel\Passport\Events\AccessTokenCreated' => [
            'Mallto\User\Listeners\RevokeOldTokens',
        ],

        'Laravel\Passport\Events\RefreshTokenCreated' => [
            'Mallto\User\Listeners\PruneOldTokens',
        ],
    ];


    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [];

    /**
     * @var array
     */
    protected $commands = [
        'Mallto\User\Commands\InstallCommand',
        'Mallto\User\Commands\UpdateCommand',
        'Mallto\User\Commands\UserAuthAddSmsCommand',
        'Mallto\User\Commands\UserStatisticsCommand',
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
        foreach ($this->listens() as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }

        foreach ($this->subscribe as $subscriber) {
            Event::subscribe($subscriber);
        }

        $this->loadViewsFrom(__DIR__.'/../../views', 'user');

        $this->loadMigrationsFrom(__DIR__.'/../../migrations');

        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');

        $this->authBoot();


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

    private function authBoot()
    {
        Passport::routes(null, [
            'prefix' => 'api/oauth',
        ]);
        //私人令牌下列设置无效
        Passport::tokensExpireIn(Carbon::now()->addDays(7));
        Passport::refreshTokensExpireIn(Carbon::now()->addDays(60));


        Passport::tokensCan([
            'mobile-token'  => 'mobile token可以访问所有需要用户绑定了手机号才能访问的接口',
            'wechat-token'  => '微信token是通过openId换取的,只能访问部分接口',
            'parking-token' => '停车需要使用到的token',
            'account-token' => "账户操作权限:如重新绑定手机",
        ]);
    }


    /**
     * Get the events and handlers.
     *
     * @return array
     */
    public function listens()
    {
        return $this->listen;
    }

}
