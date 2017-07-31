<?php

namespace Mallto\User\Controller\Api\Auth;

use App\Http\Controllers\Controller;
use Encore\Admin\AppUtils;
use Illuminate\Http\Request;
use Mallto\Mall\Controller\SubjectController;
use Mallto\Tool\Exception\PermissionDeniedException;
use Mallto\Tool\Exception\ResourceException;
use Mallto\Tool\Exception\ThirdPartException;
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
     * @param PublicUsecase        $publicUsecase
     */
    public function __construct( PublicUsecase $publicUsecase)
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

        $userUsecase = app(UserUsecase::class);

        if ($userUsecase->existUser($type)) {
            throw new UserExistException();
        }

        $subject = AppUtils::getSubject();
        $memberSystem = $subject->member_system;
        if ($memberSystem && $type == "mobile") {
            switch ($memberSystem) {
                case "kemai":
                    if ($request->identifier) {
                        $memberOperate = app("member");
                        //1.检查会员是否存在
                        try {
                            $memberInfo = $memberOperate->getInfo($request->identifier, $subject->id);
                            //存在,更新会员信息
                            if ($memberInfo && $request->name) {
                                try {
                                    $memberInfo = $memberOperate->updateInfo([
                                        "mobile"     => $request->identifier,
                                        "subject_id" => $subject->id,
                                        "sex"        => $request->gender,
                                        "birthday"   => $request->birthday,
                                    ]);
                                } catch (ThirdPartException $e) {
                                    //todo 更新会员信息失败的处理
                                    \Log::warning("科脉会员过期,无法更新用户信息".$request->identifier);
                                }
                            }
                        } catch (ThirdPartException $e) {
                            $rules = array_merge($rules, [
                                'name'     => 'required',
                                'birthday' => 'required|date',
                                'gender'   => 'required|integer',
                            ]);
                            $this->validate($request, $rules);
                            //2.不存在注册
                            $memberInfo = $memberOperate->register($request->all(), $subject->id);
                        }

                        //按理说下面这段是不会执行的
                        if (!$memberInfo) {
                            $rules = array_merge($rules, [
                                'name'     => 'required',
                                'birthday' => 'required|date',
                                'gender'   => 'required|integer',
                            ]);
                            $this->validate($request, $rules);
                            //2.不存在注册
                            $memberInfo = $memberOperate->register($request->all(), $subject->id);
                        }

                        //3. 创建用户
                        $user = $userUsecase->createUser($type, $memberInfo);

                        return $userUsecase->getUserInfo($user->id);
                    } else {
                        throw new ResourceException("手机号不能为空");
                    }
                    break;
                case "mallto_seaworld":
                    throw new PermissionDeniedException("暂不支持该会员系统注册:".SubjectController::MEMBER_REALIZE['mallto_seaworld']);
                    break;
                default:
                    throw new PermissionDeniedException("无效的会员系统:".$memberSystem);
                    break;
            }

        } else {
            // 创建用户
            $user = $userUsecase->createUser($type);

            return $userUsecase->getUserInfo($user->id);
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
        $memberOperate = app("member");

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
                        $memberOperate = app("member");
                        //1.检查会员是否存在
                        try {
                            $memberInfo = $memberOperate->getInfo($request->identifier, $subject->id);
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
                    throw new PermissionDeniedException("暂不支持该会员系统注册:".SubjectController::MEMBER_REALIZE['mallto_seaworld']);
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


}
