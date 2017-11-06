<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Controller\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mallto\Tool\Exception\NotFoundException;
use Mallto\Tool\Utils\SubjectUtils;
use Mallto\User\Data\User;
use Mallto\User\Domain\Traits\AuthValidateTrait;
use Mallto\User\Domain\UserUsecase;


/**
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class LoginController extends Controller
{

    use AuthValidateTrait;


    /**
     * 纯微信用户登录
     *
     * @param Request     $request
     * @param UserUsecase $userUsecase
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|User|null
     */
    public function wechatLogin(Request $request, UserUsecase $userUsecase)
    {
        //请求字段验证
        //验证规则
        $rules = [];
        $rules = array_merge($rules, [
            "identifier"    => "required",
            "identity_type" => "required",
        ]);
        $this->validate($request, $rules);

        $this->isWechatRequest($request);

        $subject = SubjectUtils::getSubject();

        //从请求中提取需要的信息
        $credentials = $userUsecase->transformCredentials($request);
        //检查用户是否存在
        $user = $userUsecase->retrieveByCredentials($credentials, $subject);

        if (!$user) {
            //直接创建用户
            $user = $userUsecase->createUserByWechat($credentials, $subject);
        }

        //如果是微信请求则拉取最新的用户微信信息
        $userUsecase->updateUserWechatInfo($user, $credentials, $subject);

        $user = $userUsecase->getReturenUserInfo($user);

        return $user;
    }


    public function wechatLoginWithBinding(Request $request, UserUsecase $userUsecase)
    {
        //请求字段验证
        //验证规则
        $rules = [];
        $rules = array_merge($rules, [
            "identifier"    => "required",
            "identity_type" => "required",
            "bind_type"     => [
                'required',
                Rule::in(User::SUPPORT_BIND_TYPE),
            ],
        ]);
        $this->validate($request, $rules);

        $this->isWechatRequest($request);


        //对于账户是否有绑定需求,如果有则需要传递该字段
        $bindType = $request->get("bind_type");
        $subject = SubjectUtils::getSubject();

        //从请求中提取需要的信息
        $credentials = $userUsecase->transformCredentials($request);
        //检查用户是否存在
        $user = $userUsecase->retrieveByCredentials($credentials, $subject);

        if ($user) {
            //检查绑定状态
            //绑定状态字段不为空且检查用户该字段不存在,则失败.抛出用户不存在.
            if (!empty($bindType) && !$userUsecase->checkUserBindStatus($user, $bindType)) {
                throw new NotFoundException("用户不存在");
            }
        } else {
            //绑定登录模式下用户不存在,则需要去注册
            throw new NotFoundException("用户不存在");
        }

        //如果是微信请求则拉取最新的用户微信信息
        $userUsecase->updateUserWechatInfo($user, $credentials, $subject);

        $user = $userUsecase->getReturenUserInfo($user);

        return $user;
    }





//    /**
//     * 登录2.0版本接口
//     *
//     * @param Request     $request
//     * @param UserUsecase $userUsecase
//     * @return bool
//     */
//    public function login(Request $request, UserUsecase $userUsecase)
//    {
//        //请求字段验证
//        //验证规则
//        $rules = [];
//        $rules = array_merge($rules, [
//            "identifier"    => "required",
//            "identity_type" => "required",
//            "bind_type"     => [
//                Rule::in(User::SUPPORT_BIND_TYPE),
//            ],
//        ]);
//        $this->validate($request, $rules);
//
//        //对于账户是否有绑定需求,如果有则需要传递该字段
//        $bindType = $request->get("bind_type");
//
//        $subject = SubjectUtils::getSubject();
//
//        //从请求中提取需要的信息
//        $credentials = $userUsecase->transformCredentials($request);
//        //检查用户是否存在
//        $user = $userUsecase->retrieveByCredentials($credentials, $subject);
//        if ($user) {
//            //检查绑定状态
//            //绑定状态字段不为空且检查用户该字段不存在,则失败.抛出用户不存在.
//            if (!empty($bindType) && !$userUsecase->checkUserBindStatus($user, $bindType)) {
//                throw new NotFoundException("用户不存在");
//            }
//        } else {
//            //用户不存在
//            //如果是微信模式下且不存在绑定要求(即bindType为null),则直接创建用户
//            //否则返回用户不存在
//            if ($request->header("REQUEST-TYPE") == "WECHAT" && empty($bindType)) {
//                $user = $userUsecase->createUserByWechat($credentials, $subject);
//            } else {
//                throw new NotFoundException("用户不存在");
//            }
//        }
//
//        //如果是微信请求则拉取最新的用户微信信息
//        $userUsecase->updateUserWechatInfo($user, $credentials, $subject);
//
//        $user = $userUsecase->getReturenUserInfo($user);
//
//        $user = $userUsecase->addToken($user);
//
//        return $user;
//    }


    //todo 登出 app才有

}
