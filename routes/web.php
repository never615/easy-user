<?php

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
    Route::group(['prefix' => config('admin.prefix'), "middleware" => ["admin"]], function ($router) {
        //需要授权的
        Route::group(['middleware' => ['admin.auto_permission']], function ($router) {  //指定auth的guard为mall
            //用户
            Route::resource('users', 'UserController', ['except' => ['create', 'store']]);
        });
    });

//----------------------------------------  管理端结束  -----------------------------------------------


});





