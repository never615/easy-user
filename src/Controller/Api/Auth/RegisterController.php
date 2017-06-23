<?php
namespace Mallto\User\Controller\Api\Auth;

use App\Exceptions\PermissionDeniedException;
use App\Exceptions\ResourceException;
use App\Http\Controllers\Controller;
use Encore\Admin\AppUtils;
use Illuminate\Http\Request;
use Mallto\Mall\Domain\Member\MemberOperate;
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
     * @var MemberOperate
     */
    private $memberOperate;

    /**
     * RegisterController constructor.
     *
     * @param UserUsecase   $userUsecase
     * @param MemberOperate $memberOperate
     */
    public function __construct(UserUsecase $userUsecase,MemberOperate $memberOperate)
    {
        $this->userUsecase = $userUsecase;
        $this->memberOperate = $memberOperate;
    }


    /**
     * 注册,支持微信/app;支持必须绑定手机用户 或者绑定邮箱用户等
     *
     * @param Request $request
     * @param null    $type
     * @return PermissionDeniedException|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function register(Request $request, $type = null)
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
                        $this->memberOperate = app("member");
                        //1.检查会员是否存在
                        try {
                            $memberInfo = $this->memberOperate->getInfo($request->mobile, $subject->id);
                            if ($memberInfo) {
                                //存在,更新会员信息
                                try {
                                    $memberInfo = $this->memberOperate->updateInfo([
                                        "mobile"     => $request->mobile,
                                        "subject_id" => $subject->id,
                                        "sex"        => $request->gender,
                                        "birthday"   => $request->birthday,
                                    ]);
                                } catch (\Exception $e) {
                                    //todo 更新会员信息失败的处理
                                }
                            } else {
                                //2.不存在注册
                                $memberInfo = $this->memberOperate->register($request->all(), $subject->id);
                            }
                        } catch (\Exception $e) {
                            //2.不存在注册
                            $memberInfo = $this->memberOperate->register($request->all(), $subject->id);
                        }
                        //3. 创建用户
                        $user = $this->userUsecase->createUser($type, $memberInfo);

                        return $this->userUsecase->getUserInfo($user->id);
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
