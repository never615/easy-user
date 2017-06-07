<?php
namespace Mallto\User\Domain;

use App\Exceptions\PermissionDeniedException;
use App\Exceptions\ResourceException;
use Encore\Admin\AppUtils;
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
     * @param $type
     * @return bool|User
     */
    public function existUser($type = null)
    {
        $requestType = Request::header("REQUEST-TYPE");

        $subject = AppUtils::getSubject();

        if ($requestType == "WECHAT") {
            $openid = decrypt(Input::get("openid"));

            //根据openId,查询微信用户信息
            $query = UserAuth::where("identity_type", "wechat")
                ->where("identifier", $openid)
                ->where("subject_id", $subject->id);

            $userAuth = $query->first();
            if ($userAuth) {
                if (!empty($type)) {
                    $user = $userAuth->user()
                        ->where("subject_id", $subject->id)
                        ->where($type, Input::get($type))
                        ->first();
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
            $openid = decrypt(Input::get("openid"));
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

                    return new PermissionDeniedException("openid未找到,请在微信内打开");
                }

                //处理用户数据
                if ($memberInfo) {
                    $nickname = $memberInfo["real_name"];
                } else {
                    $nickname = $wechatUserInfo->nickname;
                }

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

                \Log::info($memberInfo);
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
     * 创建或者更新用户,包括关联表
     */
    private function createOrUpdateUser()
    {
        $requestType = Request::header("REQUEST-TYPE");
        $subject = AppUtils::getSubject();
        if ($requestType == "WECHAT") {
            //先判断有么有存在
        }
    }

}
