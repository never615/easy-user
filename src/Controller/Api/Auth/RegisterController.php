<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Controller\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mallto\Tool\Exception\PermissionDeniedException;
use Mallto\User\Domain\PublicUsecase;
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
    use VerifyCodeTrait;

    /**
     * @var PublicUsecase
     */
    private $publicUsecase;

    /**
     * RegisterController constructor.
     *
     * @param PublicUsecase $publicUsecase
     */
    public function __construct(PublicUsecase $publicUsecase)
    {
        $this->publicUsecase = $publicUsecase;
    }


    /**
     * 注册,支持微信/app;支持必须绑定手机用户 或者绑定邮箱用户等
     *
     * @param Request $request
     * @return PermissionDeniedException|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function register(Request $request)
    {
        $type = $request->get("type", null);

        $requestType = $request->header("REQUEST-TYPE");

        $rules = [];

        if (!empty($type)) {
//            $rules = array_merge($rules, [
//                'name'     => 'required',
//                'birthday' => 'required|date',
//                'gender'   => 'required|integer',
//            ]);
            switch ($type) {
                case "mobile":
                    $rules = array_merge($rules, [
                        'mobile' => 'required|size:11',
                    ]);
                    break;
                default:
                    throw new PermissionDeniedException("不支持该类型注册:".$type);
                    break;
            }
        }

        $rules = array_merge($rules, [
            "identifier" => "required",
        ]);

        if ($requestType == "WECHAT") {

        }

        $this->validate($request, $rules);

        $this->checkVerifyCode($request->mobile, $request->code, $type);

        $userUsecase = app(UserUsecase::class);

        if ($userUsecase->existUser($type)) {
            throw new UserExistException();
        }

        $user = $userUsecase->createUser($type);

        return $userUsecase->getUserInfo($user->id);
    }

}
