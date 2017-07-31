<?php

namespace Mallto\User\Controller\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mallto\Tool\Exception\PermissionDeniedException;
use Mallto\Tool\Exception\ResourceException;
use Mallto\User\Domain\Traits\VerifyCodeTrait;
use Mallto\User\Domain\UserUsecaseInterface;


/**
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class UserController extends Controller
{

    use VerifyCodeTrait;

    /**
     * @var UserUsecaseInterface
     */
    private $userUsecase;

    /**
     * 请求用户信息
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function show()
    {
        $this->userUsecase = app(UserUsecaseInterface::class);

        $user = Auth::guard("api")->user();

        return $this->userUsecase->getUserInfo($user->id);
    }

//    /**
//     * 更新用户信息
//     *
//     * @param Request $request
//     */
//    public function update(Request $request)
//    {
//        \Log::info($request->all());
//    }


    /**
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
            $token = $user->createToken("easy", ["account-token"])->accessToken;

            //手机号一致
            return response()->json([
                'token' => $token,
            ]);
        } else {
            throw new ResourceException("手机号输入错误");
        }

    }

    /**
     * 更新手机/邮箱
     *
     * @param Request $request
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
