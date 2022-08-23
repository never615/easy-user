<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Controller\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Mallto\Tool\Exception\PermissionDeniedException;
use Mallto\Tool\Exception\ResourceException;
use Mallto\User\Data\User;
use Mallto\User\Domain\UserUsecase;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class UserController extends Controller
{

    /**
     * 请求用户信息
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function show(UserUsecase $userUsecase)
    {
        $user = Auth::guard("api")->user();

        return $userUsecase->getReturnUserInfo($user, false);
    }


    /**
     * 更新用户信息
     *
     * @param Request     $request
     * @param UserUsecase $userUsecase
     *
     * @return User
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, UserUsecase $userUsecase)
    {
        $user = Auth::guard("api")->user();

        //请求字段验证
        $rules = [
            "birthday" => "date",
            "gender"   => [
                Rule::in([ 0, 1, 2 ]),
            ],
        ];

        $this->validate($request, $rules);

        $userUsecase->updateUser($user, $request->all());

        return $userUsecase->getReturnUserInfo($user, false);
    }


    /**
     * 更新密码
     *
     * @param Request $request
     */
    public function updatePassword(Request $request)
    {
        $this->validate($request, [
            "identity_type" => "required",
            "old_passwd"    => "required",
            "new_passwd"    => "required",
        ]);
        $user = Auth::guard("api")->user();
        $userAuth = $user->userAuths()->where("identity_type", $request->identity_type)->first();

        if ( ! $userAuth) {
            throw new ResourceException("未找到对应的授权方式:" . $request->identity_type);
        }

        //1.校验旧的密码
        if ( ! \Hash::check($request->old_passwd, $userAuth->credential)) {
            throw new ResourceException("旧密码输入错误");
        }

        //2.更新密码
        $userAuth->credential = \Hash::make($request->new_passwd);
        $userAuth->save();

        return response()->nocontent();
    }


    /**
     * todo
     * 验证旧的手机号/邮箱
     */
    public function verifyOldIdentifier(Request $request)
    {
        throw new PermissionDeniedException();

        $type = $request->get("type");
        $identifier = $request->get('identifier');
        $code = $request->get('code');
        $this->checkVerifyCode($identifier, $code, $type);
        $user = Auth::guard('api')->user();

        if ($user->$type == $identifier) {
            $token = $user->createToken("easy", [ "account-token" ])->accessToken;

            //手机号一致
            return response()->json([
                'token' => $token,
            ]);
        } else {
            throw new ResourceException("手机号输入错误");
        }

    }


    /**
     * todo
     * 更新手机/邮箱
     *
     * @param Request $request
     *
     * @return
     */
    public function updateIdentifier(Request $request)
    {
        throw new PermissionDeniedException();

        $user = Auth::guard('api')->user();

        $code = $request->get("code");
        $identifier = $request->get('identifier');
        $type = $request->get("type");

        $this->checkVerifyCode($identifier, $code, $type);

        $user->$type = $identifier;
        $user->save();

        //处理会员相关逻辑,因为重新绑定的手机号不一定在会员系统中是会员

        //todo 更换手机号需要更新会员系统,暂不可用

        return response()->nocontent();
    }

}
