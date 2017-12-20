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
use Mallto\User\Domain\SmsUsecase;
use Mallto\User\Domain\UserUsecase;


/**
 * 重置密码
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class ResetPasswordController extends Controller
{
    /**
     * @var SmsUsecase
     */
    private $smsUsecase;

    /**
     * RegisterController constructor.
     *
     * @param SmsUsecase $smsUsecase
     */
    public function __construct(SmsUsecase $smsUsecase)
    {
        $this->smsUsecase = $smsUsecase;
    }

    public function reset(Request $request, UserUsecase $userUsecase)
    {
        $this->validate($request, [
            "identity_type" => [
                "required",
                Rule::in(['mobile']),
            ],
            "identifier"    => 'required',
            "credential"    => 'required',
            "code"          => 'required',
        ]);


        $subject = SubjectUtils::getSubject();

        //校验短信验证码
        if (!$this->smsUsecase->checkVerifyCode($request->identifier, $request->code, SmsUsecase::USE_RESET)) {
            throw new ResourceException("验证码错误");
        }

        //查询到用户
        $credentials = $userUsecase->transformCredentialsFromRequest($request);
        $user = $userUsecase->retrieveByCredentials($credentials, $subject);

        //设置新密码
        $userAuth = $user->userAuths()
            ->where("identity_type", $request->identity_type)
            ->first();

        $userAuth->credential = \Hash::make($request->credential);
        $userAuth->save();

        return response()->nocontent();
    }


}
