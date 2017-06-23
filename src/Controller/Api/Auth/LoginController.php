<?php
namespace Mallto\User\Controller\Api\Auth;

use App\Exceptions\NotFoundException;
use App\Exceptions\PermissionDeniedException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mallto\User\Domain\UserUsecase;


/**
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class LoginController extends Controller
{
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
     * 登录,支持微信和app;支持纯微信登录/或者必须绑定手机号或者邮箱等
     *
     * @param Request $request
     * @param null    $type
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function login(Request $request, $type = null)
    {
        $requestType = $request->header("REQUEST-TYPE");
        $rules = [];
        if ($requestType == "WECHAT") {
            $rules = array_merge($rules, [
                "openid" => "required",
            ]);
        } else {
            throw new PermissionDeniedException("暂不支持非微信终端登录");
        }

        $this->validate($request, $rules);

        if (!$this->userUsecase->existUser($type, false)) {
            //用户不存在,如果是纯微信登录模式下,即type is null,则自动创建用户
            if (empty($type)) {
                //创建用户
                $user = $this->userUsecase->createUser($type);
            } else {
                throw new NotFoundException("用户不存在");
            }
        } else {
            $user = $this->userUsecase->existUser($type, false);
        }

        return $this->userUsecase->getUserInfo($user->id);
    }


    //todo 登出 app才有

}
