<?php

namespace Mallto\User\Controller\Api;


use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Request;

class TestController extends Controller
{

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index(Request $request)
    {


//        try {
//            $openId = decrypt($openId);
//        } catch (DecryptException $e) {
//            Log::error("openid解密失败");
//            throw new InternalHttpException("系统错误:");
//        }
//
//        return $openId;
//
//        $user = User::first();
//        $token = $user->createToken("easy", ["wechat-token"])->accessToken;
//
//        return $token;
//        echo ExchangeCodeUtils::generateSeckillCode(123456);


//        Log::info($request->getBaseUrl());
//        Log::info($request->getBasePath());
//        Log::info($request->getHost());
//        Log::info($request->getHttpHost());
//        Log::info($request->getUri());
//        Log::info($request->getRequestUri());
//        Log::info($request->getMethod());
//        Log::info(\Illuminate\Support\Facades\Request::root());

    }

    public function testToken()
    {
        $openId = encrypt("oHTZ4vy07xa_0YsQj5Du2wNxisKM");

        return $openId;
    }

    /**
     * 测试授权
     */
    public function testOauth()
    {
        $user = Auth::guard("api")->user();

        return $user;
    }


}
