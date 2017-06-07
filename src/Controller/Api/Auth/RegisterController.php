<?php
namespace Mallto\User\Controller\Api\Auth;

use App\Exceptions\PermissionDeniedException;
use App\Exceptions\ResourceException;
use App\Http\Controllers\Controller;
use Encore\Admin\AppUtils;
use Illuminate\Http\Request;
use Mallto\User\Data\User;
use Mallto\User\Domain\UserUsecase;


/**
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class RegisterController extends Controller
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
     * 注册
     *
     * @param Request $request
     * @param null    $type
     * @param null    $info ,来自第三方系统的用户信息
     * @return PermissionDeniedException|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function register(Request $request, $type = null, $info = null)
    {
        $requestType = $request->header("REQUEST-TYPE");

        $rules = [];

        if (!empty($type)) {
            $rules = array_merge($rules, [
                'name'     => 'required',
                'birthday' => 'required|date',
                'gender'   => 'required|integer',
            ]);
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

        if ($requestType == "WECHAT") {
            $rules = array_merge($rules, [
                "openid" => "required",
            ]);
        }

        $this->validate($request, $rules);

        if ($this->userUsecase->existUser($type)) {
            throw new ResourceException("用户已经存在");
        }


        $subject = AppUtils::getSubject();
        $memberSystem = $subject->member_system;
        if ($memberSystem) {
            switch ($memberSystem) {
                case "kemai":
                    if ($request->mobile) {
                        $member = app("member");
                        //1.检查会员是否存在
                        try {
                            $memberInfo = $member->getInfo($request->mobile, $subject->id);
                        } catch (\Exception $e) {
                            //2.不存在注册
                            $memberInfo = $member->register($request->all(), $subject->id);
                        }
                        //3. 创建用户
                        $user = $this->userUsecase->createUser($type, $memberInfo);

                        $user = User::with(["member"])->findOrFail($user->id);
                        if ($type) {
                            //todo 目前type只有mobile
                            $token = $user->createToken("easy", ["mobile-token"])->accessToken;
                        } else {
                            $token = $user->createToken("easy", ["wechat-token"])->accessToken;
                        }

                        $user->token = $token;
                    } else {
                        throw new ResourceException("手机号不能为空");
                    }
                    break;
                case "mallto_seaworld":
                    throw new PermissionDeniedException("暂不支持该会员系统:mallto_seaworld");
                    break;
                default:
                    throw new PermissionDeniedException("无效的会员系统:".$memberSystem);
                    break;
            }

        } else {
            //todo 无会员系统的注册逻辑 或者是纯微信用户注册
            throw new PermissionDeniedException("无效的会员系统");
        }

        return $user;
    }

    /**
     * 绑定会员
     */
    public
    function bind()
    {
        //todo 绑定会员
    }


    /**
     * 解绑会员
     */
    public
    function unbind()
    {
        //todo 解绑会员
    }


}
