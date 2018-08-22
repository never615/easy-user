<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/


use Illuminate\Support\Facades\Route;

$attributes = [
    'namespace'  => 'Mallto\User\Controller',
    'middleware' => ['web'],
];

Route::group($attributes, function ($router) {

//----------------------------------------  管理端开始  -----------------------------------------------
    Route::group(['prefix' => config('admin.route.prefix'), "middleware" => ["adminE"]],
        function ($router) {
            //用户
            Route::resource('users', 'UserController', ['except' => ['create', 'store']]);
            //解绑
            Route::get('users/{id}/unbind', 'UserController@unbind')
                ->name("users.unbind");

            //用户统计数据
            Route::post('statistics/users/cumulate', 'Admin\UserStatisticsController@cumulateUser');
            Route::post('statistics/users/new_user', 'Admin\UserStatisticsController@newUser');
        });

//----------------------------------------  管理端结束  -----------------------------------------------


});





