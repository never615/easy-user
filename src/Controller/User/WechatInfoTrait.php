<?php
/**
 * Copyright (c) 2018. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Controller\User;

use Mallto\Admin\SelectConstants;


/**
 * Created by PhpStorm.
 * User: never615 <never615.com>
 * Date: 2018/6/7
 * Time: 下午4:51
 */
trait WechatInfoTrait
{
    protected function wechatInfoForm($form)
    {
        $form->displayE('userprofile.wechat_user', "昵称")->with(function ($value) {
            if ($value) {
                return $value['nickname'];
            }
        });

        $form->displayE('userprofile.wechat_user', "头像")->with(function ($value) {
            if ($value) {
                $avatar = $value['avatar'];

                return "<img src='$avatar' style='height: 80px'/>";
            }
        });

        $form->displayE('userprofile.wechat_user', "省份")->with(function ($value) {
            if ($value) {
                return $value['province'];
            }
        });

        $form->displayE('userprofile.wechat_user', "城市")->with(function ($value) {
            if ($value) {
                return $value['city'];
            }
        });

        $form->displayE('userprofile.wechat_user', "国家")->with(function ($value) {
            if ($value) {
                return $value['country'];
            }
        });

        $form->displayE('userprofile.wechat_user', "性别")->with(function ($value) {
            if ($value) {
                $value = $value['sex'];
                if (is_null($value)) {
                    return "未知";
                } else {
                    return SelectConstants::GENGDER[$value];
                }
            }
        });


        $form->displayE('userprofile.wechat_user', "语言")->with(function ($value) {
            if ($value) {
                return $value['language'];
            }
        });
    }


}