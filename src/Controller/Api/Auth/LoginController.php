<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Controller\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Mallto\Tool\Exception\NotFoundException;
use Mallto\Tool\Exception\PermissionDeniedException;
use Mallto\Tool\Utils\SubjectUtils;
use Mallto\User\Data\User;
use Mallto\User\Domain\Traits\AuthValidateTrait;
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

    use AuthValidateTrait;

    /**
     * 登录
     *
     * @param Request     $request
     * @param UserUsecase $userUsecase
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|User|null
     */
    public function login(Request $request, UserUsecase $userUsecase)
    {
        switch ($request->header("REQUEST-TYPE")) {
            case "WECHAT":
                if (!empty($request->get("bind_type"))) {
                    return $this->loginByWechatWithBinding($request, $userUsecase);
                } else {
                    return $this->loginByWechat($request, $userUsecase);
                }
                break;
            case "IOS":
            case "ANDROID":
                throw new PermissionDeniedException("不可用");
                break;
        }
        throw new PreconditionRequiredHttpException();
    }


    /**
     * 纯微信用户登录
     *
     * @param Request     $request
     * @param UserUsecase $userUsecase
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|User|null
     */
    public function loginByWechat(Request $request, UserUsecase $userUsecase)
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
        $credentials = $userUsecase->
        transformCredentials("wechat", $request->identifier, $request->header('REQUEST-TYPE'));

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


    /**
     * 微信登录,用户需要绑定手机或者其他项
     *
     * @param Request     $request
     * @param UserUsecase $userUsecase
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|User|null
     */
    public function loginByWechatWithBinding(Request $request, UserUsecase $userUsecase)
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


        //对于账户是否有绑定需求,如果有则需要传递该字段
        $bindType = $request->get("bind_type");
        $subject = SubjectUtils::getSubject();

        //从请求中提取需要的信息
        $credentials = $userUsecase->
        transformCredentials("wechat", $request->identifier, $request->header('REQUEST-TYPE'));
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


}
