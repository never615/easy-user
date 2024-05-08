<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Controller\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Mallto\Admin\SubjectUtils;
use Mallto\Tool\Exception\PermissionDeniedException;
use Mallto\Tool\Exception\ResourceException;
use Mallto\User\Data\User;
use Mallto\User\Domain\OpenidUtils;
use Mallto\User\Domain\SmsVerifyCodeUsecase;
use Mallto\User\Domain\Traits\AuthValidateTrait;
use Mallto\User\Domain\UserUsecase;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;
use Illuminate\Support\Facades\Log;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class RegisterController extends Controller
{

    use  AuthValidateTrait;

    /**
     * @var SmsVerifyCodeUsecase
     */
    private $smsVerifyCodeUsecase;

    /**
     * @var UserUsecase
     */
    private $userUsecase;


    /**
     * RegisterController constructor.
     *
     * @param SmsVerifyCodeUsecase  $smsUsecase
     * @param UserUsecase $userUsecase
     */
    public function __construct(SmsVerifyCodeUsecase $smsUsecase, UserUsecase $userUsecase)
    {
        $this->smsVerifyCodeUsecase = $smsUsecase;
        $this->userUsecase = $userUsecase;
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|\Illuminate\Http\JsonResponse|User|null
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function register(Request $request)
    {
        switch ($request->header("REQUEST-TYPE")) {
            case "WECHAT":
                //校验identifier(实际就是加密过得openid),确保只使用了一次
                $request = OpenidUtils::checkAndParseOpenid($request, 'identifier', false);

                return $this->registerByWechat($request);
            case "ALI":
                //校验identifier(实际就是加密过得openid),确保只使用了一次
                $request = OpenidUtils::checkAndParseOpenid($request, 'identifier', false);

                $request->input('identity_type', 'ali');

                return $this->registerByAli($request);
            case "IOS":
            case "ANDROID":
                return $this->registerByApp($request);
        }
        throw new PreconditionRequiredHttpException();
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
     * @param Request $request
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|User|null
     */
    private function registerByWechat(Request $request)
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
            'origin_id'  => 'nullable|numeric',
        ];

        $this->validate($request, $rules);

        $this->isWechatRequest($request);

        $this->smsVerifyCodeUsecase->checkVerifyCode($request->bind_data, $request->code);

        $subject = SubjectUtils::getSubject();

        $bindData = $request->bind_data;
        $bindType = $request->bind_type;

        //检查该微信用户是否已经存在
        $credentials = $this->userUsecase->transformCredentialsFromRequest($request);
        $user = $this->userUsecase->retrieveByCredentials($credentials, $subject);
        if ($user) {
            //微信用户已经存在
            if (empty($user->$bindType)) {
                //用户没有绑定对应数据
                //继续注册流程,绑定数据
                //检查是否存在关联的bind_data(可以是手机)用户
                if ($bindedUser = $this->userUsecase->isBinded($bindType, $bindData, $subject->id)) {
                    //存在
                    if ($this->userUsecase->hasUserAuth($bindedUser, "wechat")) {
                        //存在的这个用户已经绑定了微信号,提示该手机已经被其他微信绑定
                        throw new ResourceException($bindData . "已经被微信绑定");
                    } else {
                        //存在的绑定了这个手机的用户没有绑定微信号.
                        $user = $this->userUsecase->mergeAccount($bindedUser, $user);
                    }
                } else {
                    //不存在关联用户:直接绑定
                    $user = $this->userUsecase->bind($user, $bindType, $bindData);
                }
            } else {
                //用户已经绑定了,则在微信注册模式下,不应该调用到该接口,抛出异常
                //因为微信是自动调用登录接口的
                throw new PermissionDeniedException("非法调用,用户已存在,绑定的" . $bindType .
                    "是:" . $bindData);
            }
        } else {
            //微信用户不存在
            //检查是否存在关联的identifier(可以是手机)用户
            if ($bindedUser = $this->userUsecase->isBinded($bindType, $bindData, $subject->id)) {
                //存在
                if ($this->userUsecase->hasUserAuth($bindedUser, "wechat")) {
                    //存在的这个用户已经绑定了微信号,提示该手机已经被其他微信绑定
                    throw new ResourceException($bindData . "已经被微信绑定");
                } else {
                    //存在的绑定了这个手机的用户没有绑定微信号,关联手机和微信
                    $user = $this->userUsecase->createUserAuth($credentials, $bindedUser);
                }
            } else {
                $origin = $request->origin ?? null;
                $originId = $request->origin_id ?? null;

                //不存在关联用户,继续下一步
                //开始创建用户
                $user = $this->userUsecase->createUser($credentials, $subject, null, 'wechat', null, $origin,
                    $originId);
                //绑定
                $user = $this->userUsecase->bind($user, $bindType, $bindData);
            }
        }

        //更新用户微信信息
        $this->userUsecase->updateUserWechatInfo($user, $credentials, $subject);

        $user = $this->userUsecase->getReturnUserInfo($user);

        return $user;
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function registerByAli(Request $request)
    {
        //请求字段验证
        //验证规则
        $rules = [];
        $rules = array_merge($rules, [
            "identifier"  => "required",
            'bind_data'   => "required",
            "bind_type"   => [
                "required",
                Rule::in([ 'mobile' ]),
            ],
            "code"        => "required|numeric",
            'userable_id' => 'nullable|numeric',
        ]);
        $this->validate($request, $rules);

        $this->smsVerifyCodeUsecase->checkVerifyCode($request->bind_data, $request->code);

        $subject = SubjectUtils::getSubject();

        $bindData = $request->bind_data;
        $bindType = $request->bind_type;

        $credentials = $this->userUsecase->transformCredentialsFromRequest($request);

        $aliUser = $this->userUsecase->retrieveByCredentials($credentials, $subject);

        $registerGift = null;

        if ($aliUser) {
            //微信用户已经存在

            //检查用户是否注册到一半
            $this->userUsecase->checkUserStatus($aliUser);

            if ($aliUser->$bindType) {
                //todo 优化,返回用户
                Log::warning("注册成功重复,请求:" . $aliUser->id);
                Log::warning(new \Exception());

                throw new ResourceException("已经注册成功,若页面无跳转请刷新重试或重新打开页面");
            }
        }

        //检查是否存在关联的bind_data(如手机)的用户
        if ($bindedUser = $this->userUsecase->isBinded($bindType, $bindData, $subject->id)) {
            //存在关联的bind_data(如手机)的用户
            if ($this->userUsecase->hasUserAuth($bindedUser, "ali")) {
                //$bindedUser已绑定微信
                throw new ResourceException($bindData . "已经被其他支付宝绑定");
            } else {
                //$bindedUser未绑定微信
                if ($aliUser) {
                    //合并账户
                    $user = $this->userUsecase->mergeAccount($bindedUser, $aliUser);
                } else {
                    //存在的这个用户没有绑定了微信号,关联手机和微信
                    $user = $this->userUsecase->createUserAuth($credentials, $bindedUser);
                }
            }
        } else {
            //不存在关联的bind_data(如手机)的用户
            DB::beginTransaction();
            $userAbleType = $request->userable_type ?? false;
            $userAbleId = $request->userable_id ?? false;
            $channel = $request->channel ?? 'ali';

            if ( ! $aliUser) {
                if ($userAbleId && $userAbleType) {
                    //开始创建用户
                    $wechatUser = $this->userUsecase->createUser($credentials, $subject, null, $channel, null,
                        $userAbleType, $userAbleId);
                } else {
                    $wechatUser = $this->userUsecase->createUser($credentials, $subject, null, $channel);
                }

            }

            $user = $this->userUsecase->bind($wechatUser, $bindType, $bindData);

//            //如果用户填写了名字,则检查是否是否有性别和生日(兼容旧花园城)
//            if ($request->name) {
//                //旧的注册页面逻辑,手机号和会员信息一起提交
//                $this->validate($request, [
//                    "name"     => "required",
//                    "gender"   => "required",
//                    "birthday" => "required",
//                ]);
//            }

            //注册会员
            [ $user, $member, $isNewMember ] = $this->userUsecase->bindOrCreateByOtherInfo($user,
                array_merge([
                    $bindType => $bindData,
                ], $request->only([ "name", "gender", "birthday" ])));

            if ($isNewMember && $user->status == 'normal') {
                $registerGift = $this->userUsecase->registerComplete($user);
            }

            DB::commit();
        }

        //更新用户微信信息
        //$this->userUsecase->updateUserWechatInfo($user, $credentials, $subject);

        $user = $this->userUsecase->getReturnUserInfo($user);

        return response()->json([
            "user"          => $user,
            "register_gift" => $registerGift ?? null,
        ]);
    }


    /**
     * app注册用户
     *
     * @param Request $request
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|User|null
     */
    private function registerByApp(Request $request)
    {
        //请求字段验证
        $rules = [
            "identifier"    => "required",
            "identity_type" => [
                "required",
                Rule::in([ 'mobile' ]),
            ],
            "credential"    => "required",
            "code"          => "required|numeric",
        ];

        $this->validate($request, $rules);
        $this->smsVerifyCodeUsecase->checkVerifyCode($request->identifier, $request->code);
        $subject = SubjectUtils::getSubject();

        //检查用户是否存在
        $credentials = $this->userUsecase->transformCredentialsFromRequest($request);

        $user = $this->userUsecase->retrieveByCredentials($credentials, $subject);
        if ($user) {
            //使用注册凭证查询到对应的用户,表示用户已经存在了
            throw new ResourceException("用户已经存在,请直接登录");
        } else {
            //使用注册凭证查询不到对应的用户

            //检查是否存在关联的用户(检查是否有用户已经绑定了要注册的字段:比如手机)
            if ($bindedUser = $this->userUsecase->isBinded($request->identity_type, $request->identifier,
                $subject->id)) {
                //检查此用户是否已经有手机号密码的登录方式
                if ($this->userUsecase->hasUserAuth($bindedUser, "mobile")) {
                    throw new ResourceException("手机号已经被注册:" . $bindedUser->mobile);
                } else {
                    //存在(已经在微信注册过了),关联此用户,即增加新的identifier+credential的登录方式
                    $user = $this->userUsecase->createUserAuth($credentials, $bindedUser);
//                    $userUsecase->bindSalt($user,$request->salt_Id);
                }
            } else {
                //不存在,正常注册
                $user = $this->userUsecase->createUser($credentials, $subject, null, "app");
//                $userUsecase->bindSalt($user,$request->salt_Id);
            }

            $user = $this->userUsecase->getReturnUserInfo($user);

            return $user;
        }
    }



    //
    //
    ///**
    // * 获取用户密码要使用的salt
    // *
    // * @return $this|\Illuminate\Database\Eloquent\Model
    // */
    //public function userSalt()
    //{
    //    $salt = \Mallto\Tool\Utils\AppUtils::getRandomString(8);
    //    $encryptSalt = encrypt($salt);
    //    $userSalt = UserSalt::create([
    //        "salt" => $encryptSalt,
    //    ]);
    //
    //    return response()->json([
    //        'id'   => $userSalt->id,
    //        'salt' => $salt,
    //    ]);
    //}

}
