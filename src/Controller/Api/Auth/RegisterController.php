<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Controller\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mallto\Tool\Exception\PermissionDeniedException;
use Mallto\Tool\Exception\ResourceException;
use Mallto\Tool\Utils\SubjectUtils;
use Mallto\User\Data\User;
use Mallto\User\Data\UserSalt;
use Mallto\User\Domain\Traits\AuthValidateTrait;
use Mallto\User\Domain\Traits\VerifyCodeTrait;
use Mallto\User\Domain\UserUsecase;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;


/**
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class RegisterController extends Controller
{
    use VerifyCodeTrait, AuthValidateTrait;

    public function register(Request $request, UserUsecase $userUsecase)
    {
        switch ($request->header("REQUEST-TYPE")) {
            case "WECHAT":
                return $this->registerByWechat($request, $userUsecase);
                break;
            case "IOS":
            case "ANDROID":
                throw new PermissionDeniedException("不可用");

                return $this->registerByApp($request, $userUsecase);
                break;
        }
        throw new PreconditionRequiredHttpException();
    }


    /**
     * 获取用户密码要使用的salt
     *
     * @return $this|\Illuminate\Database\Eloquent\Model
     */
    public function userSalt()
    {
        $salt = \Mallto\Tool\Utils\AppUtils::getRandomString(8);
        $encryptSalt = encrypt($salt);
        $userSalt = UserSalt::create([
            "salt" => $encryptSalt,
        ]);

        return response()->json([
            'id'   => $userSalt->id,
            'salt' => $salt,
        ]);
    }


    /**
     * app注册用户
     *
     * @param Request     $request
     * @param UserUsecase $userUsecase
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|User|null
     */
    private function registerByApp(Request $request, UserUsecase $userUsecase)
    {
        //请求字段验证
        $rules = [
            "identifier"    => "required",
            "identity_type" => "required",
            "credential"    => "required",
            "code"          => "required|numeric",
            "name"          => "required",
            "salt_id"       => "required",
        ];

        $this->validate($request, $rules);
        $this->checkVerifyCode($request->bind_data, $request->code);
        $subject = SubjectUtils::getSubject();

        //检查用户是否存在
        $credentials = $userUsecase->transformCredentialsFromRequest($request);

        $user = $userUsecase->retrieveByCredentials($credentials, $subject);
        if ($user) {
            //使用注册凭证查询到对应的用户,表示用户已经存在了
            throw new ResourceException("用户已经存在,请直接登录");
        } else {
            //使用注册凭证查询不到对应的用户

            //检查是否存在关联的用户(检查是否有用户已经绑定了要注册的字段:比如手机)
            if ($bindUser = $userUsecase->isBinded($request->identity_type, $request->identifier, $subject->id)) {
                //检查此用户是否已经有手机号密码的登录方式
                if ($userUsecase->hasIdentityType($bindUser, "mobile")) {
                    throw new ResourceException("手机号已经被注册:".$bindUser->mobile);
                } else {
                    //存在(已经在微信注册过了),关联此用户,即增加新的identifier+credential的登录方式
                    $user = $userUsecase->addIdentifier($bindUser, $credentials);
                }
            } else {
                //不存在,正常注册
                $user = $userUsecase->createUserByApp($credentials, $subject);
            }

            $user = $userUsecase->getReturenUserInfo($user);

            return $user;
        }
    }


    /**
     * 微信注册用户
     *
     * 用户有三种: 1. 纯微信用户;  2. 微信用户但是绑定了手机 3. app用户
     *
     * 注册时出现的情况:
     * 1. 注册人是彻底的新用户:创建微信用户,绑定手机
     * 2. 注册人已经是纯微信用户了:绑定手机
     * 3. 注册人在app注册过但不是微信纯用户:
     * --------------- 情况1:app那个用户没有绑定过微信,在app用户上添加微信授权方式 情况2: app那个用户已经绑定过微信则提示
     * 4. 注册人在app注册过且是纯微信用户:
     * --------------- 情况1:app那个用户没有绑定过微信,合并app用户和微信用户 情况2: app那个用户已经绑定过微信则提示
     *
     * @param Request     $request
     * @param UserUsecase $userUsecase
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|User|null
     */
    private function registerByWechat(Request $request, UserUsecase $userUsecase)
    {
        //请求字段验证
        $rules = [
            "identifier" => "required",
            'bind_data'  => "required",
            "bind_type"  => [
                "required",
                Rule::in(User::SUPPORT_BIND_TYPE),
            ],
            "code"       => "required|numeric",
        ];

        $this->validate($request, $rules);

        $this->isWechatRequest($request);

        $this->checkVerifyCode($request->bind_data, $request->code);

        $subject = SubjectUtils::getSubject();

        $bindData = $request->bind_data;
        $bindType = $request->bind_type;

        //检查该微信用户是否已经存在
        $credentials = $userUsecase->
        transformCredentials("wechat", $request->identifier, $request->header('REQUEST-TYPE'));
        $user = $userUsecase->retrieveByCredentials($credentials, $subject);
        if ($user) {
            //微信用户已经存在
            if (empty($user->$bindType)) {
                //用户没有绑定对应数据
                //继续注册流程,绑定数据
                //检查是否存在关联的bind_data(可以是手机)用户
                if ($bindUser = $userUsecase->isBinded($bindType, $bindData, $subject->id)) {
                    //存在
                    if ($userUsecase->hasIdentityType($bindUser, "wechat")) {
                        //存在的这个用户已经绑定了微信号,提示该手机已经被其他微信绑定
                        throw new ResourceException($bindData."已经被微信绑定");
                    } else {
                        //存在的绑定了这个手机的用户没有绑定微信号.
                        $user = $userUsecase->mergeAccount($bindUser, $user);
                    }
                } else {
                    //不存在关联用户:直接绑定
                    $user = $userUsecase->bind($user, $bindType, $bindData);
                }
            } else {
                //用户已经绑定了,则在微信注册模式下,不应该调用到该接口,抛出异常
                //因为微信是自动调用登录接口的
                throw new PermissionDeniedException("非法调用,用户已存在,绑定的".$bindType.
                    "是:".$bindData);
            }
        } else {
            //微信用户不存在
            //检查是否存在关联的identifier(可以是手机)用户
            if ($bindUser = $userUsecase->isBinded($bindType, $bindData, $subject->id)) {
                //存在
                if ($userUsecase->hasIdentityType($bindUser, "wechat")) {
                    //存在的这个用户已经绑定了微信号,提示该手机已经被其他微信绑定
                    throw new ResourceException($bindData."已经被微信绑定");
                } else {
                    //存在的绑定了这个手机的用户没有绑定微信号,关联手机和微信
                    $user = $userUsecase->addIdentifier($bindUser, $credentials);
                }
            } else {
                //不存在关联用户,继续下一步
                //开始创建用户
                $user = $userUsecase->createUserByWechat($credentials, $subject);
                //绑定
                $user = $userUsecase->bind($user, $bindType, $bindData);
            }
        }


        //更新用户微信信息
        $userUsecase->updateUserWechatInfo($user, $credentials, $subject);

        $user = $userUsecase->getReturenUserInfo($user);

        return $user;
    }

}
