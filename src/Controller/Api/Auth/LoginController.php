<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Controller\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mallto\Tool\Exception\NotFoundException;
use Mallto\Tool\Exception\PermissionDeniedException;
use Mallto\User\Domain\UserUsecase;


/**
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class LoginController extends Controller
{


    /**
     * 登录,支持微信和app;支持纯微信登录/或者必须绑定手机号或者邮箱等
     *
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function login(Request $request,UserUsecase $userUsecase)
    {
        $requestType = $request->header("REQUEST-TYPE");
        $type = $request->get("type", null);
        $rules = [];
        $rules = array_merge($rules, [
            "identifier" => "required",
        ]);
        if ($requestType == "WECHAT") {
        } else {
            throw new PermissionDeniedException("暂不支持非微信终端登录");
        }

        $this->validate($request, $rules);

//        $userUsecase = app(::class);


        $user = $userUsecase->existUser($type, false);
        if (!$user) {
            //用户不存在,如果是纯微信登录模式下,即type is null,则自动创建用户
            if (empty($type)) {
                //创建用户
                $user = $userUsecase->createUser($type);
            } else {
                throw new NotFoundException("用户不存在");
            }
        }

        return $userUsecase->getUserInfo($user->id);
    }


    //todo 登出 app才有

}
