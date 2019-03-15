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
    Route::group(['prefix' => config('admin.route.prefix'), "middleware" => ["adminE_base"]],
        function ($router) {

            Route::group(["namespace" => "Statistics"], function ($router) {
                //todo 主页不能没,动态权限显示内容处理
                //统计
                Route::get('/', 'DashboardController@dashboard')->name("dashboard");
                //------------------- 数据源提供 开始 --------------------
                //微信用户uv
                Route::post('/statistics/users/user_uv', 'DataService\UserStatisticsController@userUv');

                //页面pv排名
                Route::post('/statistics/page/pv/rank', 'DataService\PagePvStatisticsController@pagePvRank');
                //前端页面pv变化趋势
                Route::post('/statistics/page/pv/trend', 'DataService\PagePvStatisticsController@pagePvTrend');
                //开放的page paths,page pv统计展示用
                Route::post('/statistics/page/pv/page_paths', 'DataService\PagePvStatisticsController@pagePaths');

                //------------------- 数据源提供 结束 --------------------

            });

            Route::group(['middleware' => ['adminE.auto_permission']], function ($router) {  //指定auth的guard为mall


                //用户
                Route::resource('users', 'UserController');
                //解绑
                Route::get('users/{id}/unbind', 'UserController@unbind')
                    ->name("users.unbind");

                //微信统计数据
                Route::post('statistics/wechat_user/cumulate', 'Admin\WechatUserStatisticsController@cumulateUser');
                Route::post('statistics/wechat_user/new_user', 'Admin\WechatUserStatisticsController@newUser');

                //用户统计数据
                Route::post('statistics/users/cumulate', 'Admin\UserStatisticsController@cumulateUser');
                Route::post('statistics/users/new_user', 'Admin\UserStatisticsController@newUser');
            });
        });

//----------------------------------------  管理端结束  -----------------------------------------------


});





