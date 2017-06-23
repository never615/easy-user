<?php
namespace Mallto\User\Domain;

use App\Exceptions\PermissionDeniedException;
use App\Exceptions\ResourceException;
use Encore\Admin\AppUtils;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Mallto\Mall\Data\Member;
use Mallto\User\Data\User;
use Mallto\User\Data\UserAuth;
use Mallto\User\Data\WechatAuthInfo;
use Mallto\User\Data\WechatUserInfo;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 06/06/2017
 * Time: 7:57 PM
 */
class UserUsecase
{
    /**
     * 判断用户是否存在
     *
     * @param      $type
     * @param bool $register ,注册或者登陆模式,
     *                       当type是mobile的情况下:注册模式下判断用户是否存在使用提交参数手机号查询,登陆模式下判断用户是否存在判断mobile字段是不是空
     * @return bool|User
     */
    public function existUser($type = null, $register = true)
    {
        $requestType = Request::header("REQUEST-TYPE");

        $subject = AppUtils::getSubject();

        if ($requestType == "WECHAT") {
            $openid = $this->getOpenid();
            //根据openId,查询微信用户信息
            $query = UserAuth::where("identity_type", "wechat")
                ->where("identifier", $openid)
                ->where("subject_id", $subject->id);

            $userAuth = $query->first();
            if ($userAuth) {
                if (!empty($type)) {
                    $query = $userAuth->user()
                        ->where("subject_id", $subject->id);
                    if ($register) {
                        $query = $query->where($type, Input::get($type));
                    } else {
                        $query = $query->whereNotNull($type);
                    }
                    $user = $query->first();
                } else {
                    $user = $userAuth->user;
                }
                if ($user) {
                    return $user;
                }
            }
        }

        return false;
    }


    /**
     * 创建用户
     *
     * @param string $type ,mobile or email ..
     * @param Member $memberInfo
     * @return PermissionDeniedException
     */
    public function createUser($type = null, $memberInfo = null)
    {
        $requestType = Request::header("REQUEST-TYPE");

        if ($requestType == "WECHAT") {
            $openid = $this->getOpenid();
            $subject = AppUtils::getSubject();
            $uuid = $subject->uuid;

            if (!$this->existUser($type)) {
                //用户不存在
                //查询微信用户信息
                $wechatAuthInfo = WechatAuthInfo::where("uuid", $uuid)->first();
                if (!$wechatAuthInfo) {
                    throw new PermissionDeniedException("公众号未授权");
                }

                $wechatUserInfo = WechatUserInfo::where("openid", $openid)
                    ->where("app_id", $wechatAuthInfo->authorizer_appid)
                    ->first();

                if (!$wechatUserInfo) {
                    \Log::error("无法获取微信信息");

                    throw new PermissionDeniedException("openid未找到,请在微信内打开");
                }


                $nickname = $wechatUserInfo->nickname;

                $data = [
                    "subject_id" => $subject->id,
                    "nickname"   => $nickname,
                    "avatar"     => $wechatUserInfo->avatar,
                ];

                if (!empty($type)) {
                    $data = array_merge($data, [$type => Input::get($type)]);
                }

                DB::beginTransaction();

                //先判断有没有存在纯微信用户
                if ($this->existUser()) {
                    //已经存在纯微信用户信息了,更新
                    $user = $this->existUser();
                    $user->update($data);
                } else {
                    //不存在纯微信用户,直接创建
                    //创建用户

                    $user = User::create($data);

                    $user->userAuths()->create([
                        "identity_type" => "wechat",
                        "identifier"    => $openid,
                        "subject_id"    => $subject->id,
                    ]);
                }

                //填充微信信息
                $user->userProfile()->updateOrCreate([
                    "wechat_nickname"  => $wechatUserInfo->nickname,
                    "wechat_avatar"    => $wechatUserInfo->avatar,
                    "wechat_province"  => $wechatUserInfo->province,
                    "wechat_city"      => $wechatUserInfo->city,
                    "wechat_country"   => $wechatUserInfo->country,
                    "wechat_sex"       => $wechatUserInfo->sex,
                    "wechat_language"  => $wechatUserInfo->language,
                    "wechat_privilege" => $wechatUserInfo->privilege,
                ]);

                if ($memberInfo) {
                    //存在会员 关联用户id和到会员表
                    $member = Member::where("id", $memberInfo["id"])->firstOrFail();
                    $member->user_id = $user->id;
                    $member->save();
                }

                DB::commit();


                return $user;
            } else {
                throw new ResourceException("用户已经存在");
            }
        } else {
            throw new PermissionDeniedException("暂不支持来自非微信的注册");
        }
    }

    /**
     * 获取用户信息
     *
     * @param $userId
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function getUserInfo($userId)
    {
        $user = User::with(["member"])->findOrFail($userId);

        if ($user->mobile) {
            $token = $user->createToken("easy", ["mobile-token"])->accessToken;
        } else {
            $token = $user->createToken("easy", ["wechat-token"])->accessToken;
        }

//        //更新用户拥有的权限不同,生成不同的token
//        if (!empty($type)) {
//            switch ($type) {
//                case "mobile":
//                    $token = $user->createToken("easy", ["mobile-token"])->accessToken;
//                    break;
//                default:
//                    throw new InvalidParamException("不支持的type");
//                    break;
//            }
//        } else {
//            $token = $user->createToken("easy", ["wechat-token"])->accessToken;
//        }

        $user->token = $token;

        return $user;
    }

    /**
     * 获取openid
     *
     * @return mixed
     */
    private function getOpenid()
    {
        try {
            $openid = decrypt(Input::get("openid"));

            return $openid;
        } catch (DecryptException $e) {
            \Log::warning(Input::get("openid"));
            \Log::error("openid解密失败");
            throw new ResourceException("openid无效");
        }
    }

}
