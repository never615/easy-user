<?php
namespace Mallto\User\Controller\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mallto\Tool\Exception\ResourceException;
use Mallto\User\Domain\Traits\VerifyCodeTrait;
use Mallto\User\Domain\UserUsecase;


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
     * @var UserUsecase
     */
    private $userUsecase;

    /**
     * RegisterController constructor.
     *
     * @param UserUsecase $userUsecase
     */
    public function __construct(UserUsecase $userUsecase)
    {
        $this->userUsecase = $userUsecase;
    }

    /**
     * 请求用户信息
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function show()
    {
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
        $user = Auth::guard('api')->user();

        $code = $request->get("code");
        $identifier = $request->get('identifier');
        $type = $request->get("type");

        $this->checkVerifyCode($identifier, $code, $type);

        $user->$type = $identifier;
        $user->save();

        return response()->nocontent();
    }

}
