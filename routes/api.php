<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
use Illuminate\Support\Facades\Route;

$attributes = [
    'namespace'  => 'Mallto\User\Controller\Api',
    'prefix'     => 'api',
    'middleware' => ['api'],
];

Route::group($attributes, function ($router) {


    /**
     * 需要经过验证
     */
    Route::group(['middleware' => ['requestCheck']], function () {

        //公共接口
        //短信验证码
        Route::get('code', 'PublicController@getMessageCode');

        //邮箱验证码
//        Route::get('mail_code', 'PublicController@getMailMessageCode');

        //微信登录:企业号,使用userid登录,企业号使用
        Route::post("login_by_corp", 'Auth\WechatLoginController@loginByCorp');


        //微信登录:只要是微信用户就行 (使用openid登录)
        Route::post("login_by_openid", 'Auth\WechatLoginController@loginByOpenid');

        //微信登录:登录账户需要绑定type,type可以是mobile或者email
//        Route::post("login/{type}", 'Auth\WechatLoginController@wechatLoginWithType');


        //(新) 登录接口
        Route::post("login/{type?}", 'Auth\LoginController@login');
        //注册:通用注册,包含微信和app
        Route::post('register/{type?}', 'Auth\RegisterController@register');


//        Route::post('bind', 'Auth\RegisterController@bind');


        //todo 登录登出 app使用
//        Route::post('login', 'Auth\LoginController@login');
//        Route::post('logout', 'Auth\LoginController@logout');
//
//        // Registration Routes...
//        Route::post('register', 'Auth\RegisterController@register');
//
//        // Password Reset Routes... 忘记密码
//        Route::post('password/email', 'Auth\ForgotPasswordController@resetPassword');

        /**
         * 需要经过签名校验
         */
        Route::group(['middleware' => ['authSign']], function () {

        });


        /**
         * 需要经过授权
         */
//        Route::group(['middleware' => ['jwt.auth', 'jwt.refresh']], function () {
        Route::group(['middleware' => ['auth:api']], function () {

            Route::group(["middleware" => ["scopes:mobile-token"]], function () {

            });

            Route::group(["middleware" => ["scope:mobile-token,wechat-token"]], function () {

            });

//            //用户
//            //重置密码
//            Route::post('password/reset', 'Auth\ResetPasswordController@resetByEmail');
//
//            //获取用户信息
//            Route::get('user', 'UserController@show');
//
//            //更新用户信息
////            Route::patch('user', "UserController@update");


        });
    });
});





