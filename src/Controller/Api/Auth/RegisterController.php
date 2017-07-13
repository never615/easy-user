<?php
namespace Mallto\User\Controller\Api\Auth;

use App\Http\Controllers\Controller;
use Encore\Admin\AppUtils;
use Illuminate\Http\Request;
use Mallto\Mall\Domain\Member\MemberOperate;
use Mallto\Tool\Exception\PermissionDeniedException;
use Mallto\Tool\Exception\ResourceException;
use Mallto\Tool\Utils\ResponseUtils;
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
     * @var UserUsecase
     */
    private $userUsecase;
    /**
     * @var MemberOperate
     */
    private $memberOperate;
    /**
     * @var PublicUsecase
     */
    private $publicUsecase;

    /**
     * RegisterController constructor.
     *
     * @param UserUsecase   $userUsecase
     * @param PublicUsecase $publicUsecase
     */
    public function __construct(UserUsecase $userUsecase, PublicUsecase $publicUsecase)
    {
        $this->userUsecase = $userUsecase;
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
        $this->memberOperate = app("member");

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
                        'identifier' => 'required|size:11',
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

        $this->checkVerifyCode($request->identifier, $request->code, $type);


        if ($this->userUsecase->existUser($type)) {
            throw new UserExistException();
        }

        $subject = AppUtils::getSubject();
        $memberSystem = $subject->member_system;
        if ($memberSystem) {
            switch ($memberSystem) {
                case "kemai":
                    if ($request->identifier) {
                        $this->memberOperate = app("member");
                        //1.检查会员是否存在
                        try {
                            $memberInfo = $this->memberOperate->getInfo($request->identifier, $subject->id);
                            if ($memberInfo && $request->name) {
                                //存在,更新会员信息
                                try {
                                    $memberInfo = $this->memberOperate->updateInfo([
                                        "mobile"     => $request->identifier,
                                        "subject_id" => $subject->id,
                                        "sex"        => $request->gender,
                                        "birthday"   => $request->birthday,
                                    ]);
                                } catch (\Exception $e) {
                                    //todo 更新会员信息失败的处理
                                    \Log::warning("科脉会员过期,无法更新用户信息".$memberInfo->identifier);
                                }
                            } else {

                                $rules = array_merge($rules, [
                                    'name'     => 'required',
                                    'birthday' => 'required|date',
                                    'gender'   => 'required|integer',
                                ]);
                                $this->validate($request, $rules);
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
     * 检查该手机号在会员系统中是否存在了
     *
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function existMember(Request $request)
    {
        $this->memberOperate = app("member");

        $type = $request->get("type", null);

        $requestType = $request->header("REQUEST-TYPE");

        $rules = [];


        if (!empty($type)) {
            $rules = array_merge($rules, [
            ]);
            switch ($type) {
                case "mobile":
                    $rules = array_merge($rules, [
                        'identifier' => 'required|size:11',
                    ]);
                    break;
                default:
                    throw new PermissionDeniedException("不支持该类型注册:".$type);
                    break;
            }
        }

        if ($requestType == "WECHAT") {
            $rules = array_merge($rules, [
            ]);
        }

        $this->validate($request, $rules);

        $isMember = 0;
        //检查会员系统是否存在该用户
        $subject = AppUtils::getSubject();
        $memberSystem = $subject->member_system;
        if ($memberSystem) {
            switch ($memberSystem) {
                case "kemai":
                    if ($request->identifier) {
                        $this->memberOperate = app("member");
                        //1.检查会员是否存在
                        try {
                            $memberInfo = $this->memberOperate->getInfo($request->identifier, $subject->id);
                            if (!$memberInfo) {
                                //2.不存在注册
                                $isMember = 0;
                            } else {
                                $isMember = 1;
                            }
                        } catch (\Exception $e) {
                            //2.不存在注册
                            $isMember = 0;
                        }
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


        $this->publicUsecase->sendSms($request->identifier, $subject->id);

        return response()->json([
            'is_member' => $isMember,
        ]);
    }

    /**
     * 为了迁移旧项目数据做的桥
     *
     * @param Request $request
     * @return
     */
    public function bridge(Request $request)
    {
        //检查会员系统是否存在该用户
        $subject = AppUtils::getSubject();

        $url = $request->url;

        $memberSystem = $subject->member_system;
        if ($memberSystem) {
            switch ($memberSystem) {
                case "kemai":
                    if ($request->identifier) {
                        $this->memberOperate = app("member");
                        //1.检查会员是否存在
                        try {
                            $memberInfo = $this->memberOperate->getInfo($request->identifier, $subject->id);
                            if (!$memberInfo) {
                                //2.不存在注册
                                return redirect($url);
                            }
                        } catch (\Exception $e) {
                            //2.不存在注册
                            return redirect($url);
                        }
                        //3. 创建用户
                        try {
                            $user = $this->userUsecase->createUser('mobile', $memberInfo, "bridge");

                            return redirect($url);
                        } catch (UserExistException $e) {
                            return redirect($url);
                        }
                    } else {
                        \Log::info("手机号码为空");

                        return redirect($url);
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


}
