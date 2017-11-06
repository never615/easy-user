<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Controller\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mallto\Tool\Exception\ResourceException;
use Mallto\Tool\Utils\SubjectUtils;
use Mallto\User\Data\User;
use Mallto\User\Domain\Traits\AuthValidateTrait;
use Mallto\User\Domain\Traits\VerifyCodeTrait;
use Mallto\User\Domain\UserUsecase;
use Mallto\User\Exceptions\UserExistException;


/**
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class RegisterController extends Controller
{
    use VerifyCodeTrait, AuthValidateTrait;


    /**
     * app注册用户
     *
     * @param Request     $request
     * @param UserUsecase $userUsecase
     */
    public function registerByApp(Request $request, UserUsecase $userUsecase)
    {
        //todo
    }


    /**
     * 微信注册用户
     *
     * @param Request     $request
     * @param UserUsecase $userUsecase
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|User|null
     */
    public function registerByWechat(Request $request, UserUsecase $userUsecase)
    {
        //请求字段验证
        //验证规则
        $rules = [];
        $rules = array_merge($rules, [
            "identifier"    => "required",
            "identity_type" => "required",
            'bind_data'     => "required",
            "bind_type"     => [
                "required",
                Rule::in(User::SUPPORT_BIND_TYPE),
            ],
            "code"          => "required|numeric",
        ]);

        $this->validate($request, $rules);

        $this->isWechatRequest($request);

        $this->checkVerifyCode($request->bind_data, $request->code);

        $subject = SubjectUtils::getSubject();
        $bindData = $request->bind_data;
        $bindType = $request->bind_type;

        //检查bind_date是否被占用
        if ($userUsecase->isBinded($bindData, $bindType, $subject->id)) {
            throw new UserExistException($bindType."已经被使用");
        }
        //检查用户是否存在
        $credentials = $userUsecase->transformCredentials($request);
        $user = $userUsecase->retrieveByCredentials($credentials, $subject);
        if ($user) {
            //用户已经存在
            if (empty($user->$bindType)) {
                //用户没有绑定对应数据
                //继续注册流程,绑定数据
                $user = $userUsecase->bind($user, $bindType, $bindData);
            } else {
                //用户已经绑定了,则在微信注册模式下,不应该调用到该接口,抛出异常
                throw new ResourceException("接口调用异常,用户已存在");
            }
        } else {
            //开始创建用户
            $user = $userUsecase->createUserByWechat($credentials, $subject);
        }


        //更新用户微信信息
        $userUsecase->updateUserWechatInfo($user, $credentials, $subject);

        $user = $userUsecase->getReturenUserInfo($user);

        return $user;
    }

}
