<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Controller\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Mallto\Admin\SubjectUtils;
use Mallto\Tool\Exception\AuthorizeFailedException;
use Mallto\Tool\Exception\NotFoundException;
use Mallto\User\Data\User;
use Mallto\User\Domain\Traits\AuthValidateTrait;
use Mallto\User\Domain\Traits\OpenidCheckTrait;
use Mallto\User\Domain\UserUsecase;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class LoginController extends Controller
{

    use AuthValidateTrait, OpenidCheckTrait;

    /**
     * @var UserUsecase
     */
    private $userUsecase;


    /**
     * LoginController constructor.
     *
     * @param UserUsecase $userUsecase
     */
    public function __construct(UserUsecase $userUsecase)
    {
        $this->userUsecase = $userUsecase;
    }


    /**
     * 登录
     *
     * @param Request $request
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|User|null
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        switch ($request->header("REQUEST-TYPE")) {
            case "WECHAT":
                //校验identifier(实际就是加密过得openid),确保只使用了一次
                $request = $this->checkOpenid($request, 'identifier');
                if ( ! empty($request->get("bind_type"))) {
                    return $this->loginByWechatWithBinding($request);
                } else {
                    return $this->loginByWechat($request);
                }
                break;
            case "ALI":
                $request = $this->checkUserid($request, 'identifier');
                if ( ! empty($request->get("bind_type"))) {
                    return $this->loginByAliWithBinding($request);
                } else {
                    return $this->loginByAli($request);
                }
            case "IOS":
            case "ANDROID":
                return $this->loginByApp($request);
                break;
        }
        throw new PreconditionRequiredHttpException();
    }


    /**
     * app登录
     *
     * @param Request $request
     *
     * @return User
     */
    public function loginByApp(Request $request)
    {
        $this->validate($request, [
            "identifier"    => "required",
            "identity_type" => [
                "required",
                Rule::in([ 'mobile' ]),
            ],
            "credential"    => "required",
        ]);

        $user = $this->userUsecase->retrieveByRequestCredentials($request, SubjectUtils::getSubject());

        if ( ! $user) {
            throw new AuthorizeFailedException();
        }

        return $this->userUsecase->getReturnUserInfo($user);
    }


    /**
     * 纯微信用户登录
     *
     * @param Request $request
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|User|null
     * @throws \Illuminate\Validation\ValidationException
     */
    public function loginByWechat(Request $request)
    {
        //请求字段验证
        //验证规则
        $rules = [];
        $rules = array_merge($rules, [
            "identifier" => "required",
        ]);
        $this->validate($request, $rules);

        $this->isWechatRequest($request);

        $subject = SubjectUtils::getSubject();

        //从请求中提取需要的信息
        $credentials = $this->userUsecase->transformCredentialsFromRequest($request);

        //检查用户是否存在
        $user = $this->userUsecase->retrieveByCredentials($credentials, $subject);

        if ( ! $user) {
            //直接创建用户
            $user = $this->userUsecase->createUser($credentials, $subject, null, "wechat");
        }

        //如果是微信请求则拉取最新的用户微信信息
        $this->userUsecase->updateUserWechatInfo($user, $credentials, $subject);

        $user = $this->userUsecase->getReturnUserInfo($user, true, true);

        return $user;
    }


    /**
     * 微信登录,用户需要绑定手机或者其他项
     *
     * @param Request $request
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|User|null
     */
    public function loginByWechatWithBinding(Request $request)
    {
        //请求字段验证
        //验证规则
        $rules = [];
        $rules = array_merge($rules, [
            "identifier" => "required",
            "bind_type"  => [
                'required',
                Rule::in(User::SUPPORT_BIND_TYPE),
            ],
        ]);
        $this->validate($request, $rules);

        $this->isWechatRequest($request);
        \Log::debug(1);
        //对于账户是否有绑定需求,如果有则需要传递该字段
        $bindType = $request->get("bind_type");
        $subject = SubjectUtils::getSubject();
        \Log::debug(2);
        //从请求中提取需要的信息
        $credentials = $this->userUsecase->transformCredentialsFromRequest($request);

        //检查用户是否存在
        $user = $this->userUsecase->retrieveByCredentials($credentials, $subject);
        \Log::debug(3);
        if ($user) {
            //检查绑定状态
            //绑定状态字段不为空且检查用户该字段不存在,则失败.抛出用户不存在.
            if ( ! empty($bindType) && ! $this->userUsecase->checkUserBindStatus($user, $bindType)) {
                throw new NotFoundException('不存在绑定了该' . $bindType . '的用户');
            }
        } else {
            //绑定登录模式下用户不存在,则需要去注册
            throw new NotFoundException('用户不存在');
        }
        \Log::debug(4);
        //如果是微信请求则拉取最新的用户微信信息
        $this->userUsecase->updateUserWechatInfo($user, $credentials, $subject);

        $user = $this->userUsecase->getReturnUserInfo($user);
        \Log::debug(5);
        return $user;
    }


    /**
     * 退出登录
     *
     * @return \Illuminate\Http\Response
     */
    public function logout()
    {
        //删除用户token
        $user = Auth::guard("api")->user();

        $client = \DB::table("oauth_clients")
            ->where('name', "墨兔科技 Password Grant Client")
            ->first();

        $user->tokens()
            ->where("client_id", $client->id)
            ->delete();

        return response()->nocontent();
    }


    /**
     * 纯支付宝用户登录
     *
     * @param Request $request
     *
     * @return User
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function loginByAli(Request $request)
    {
        //请求字段验证
        //验证规则
        $rules = [];
        $rules = array_merge($rules, [
            "identifier" => "required",
        ]);
        $this->validate($request, $rules);

        $this->isAliRequest($request);

        $subject = SubjectUtils::getSubject();

        //从请求中提取需要的信息
        $credentials = $this->userUsecase->transformCredentialsFromRequest($request);

        //检查用户是否存在
        $user = $this->userUsecase->retrieveByCredentials($credentials, $subject);

        if ( ! $user) {
            //直接创建用户
            $user = $this->userUsecase->createUser($credentials, $subject, null, 'ali');
        }

        //如果是微信请求则拉取最新的用户微信信息
        //$this->userUsecase->updateUserAliInfo($user, $credentials, $subject);

        $user = $this->userUsecase->getReturnUserInfo($user, true, true);

        return $user;
    }


    /**
     * 支付宝登录,用户需要绑定手机或者其他项
     *
     * @param Request $request
     *
     * @return User
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function loginByAliWithBinding(Request $request)
    {
        //请求字段验证
        //验证规则
        $rules = [];
        $rules = array_merge($rules, [
            "identifier" => "required",
            "bind_type"  => [
                'required',
                Rule::in(User::SUPPORT_BIND_TYPE),
            ],
        ]);
        $this->validate($request, $rules);

        $this->isAliRequest($request);

        //对于账户是否有绑定需求,如果有则需要传递该字段
        $bindType = $request->get("bind_type");
        $subject = SubjectUtils::getSubject();

        //从请求中提取需要的信息
        $credentials = $this->userUsecase->transformCredentialsFromRequest($request);

        //检查用户是否存在
        $user = $this->userUsecase->retrieveByCredentials($credentials, $subject);

        if ($user) {
            //检查绑定状态
            //绑定状态字段不为空且检查用户该字段不存在,则失败.抛出用户不存在.
            if ( ! empty($bindType) && ! $this->userUsecase->checkUserBindStatus($user, $bindType)) {
                throw new NotFoundException('不存在绑定了该' . $bindType . '的用户');
            }
        } else {
            //绑定登录模式下用户不存在,则需要去注册
            throw new NotFoundException('用户不存在');
        }

        //如果是微信请求则拉取最新的用户微信信息
        //$this->userUsecase->updateUserAliInfo($user, $credentials, $subject);

        $user = $this->userUsecase->getReturnUserInfo($user);

        return $user;
    }

}
