<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Controller;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Mallto\Admin\AdminUtils;
use Mallto\Admin\Controllers\Base\AdminCommonController;
use Mallto\User\Controller\User\ActionTrait;
use Mallto\User\Controller\User\UserBasicInfoTrait;
use Mallto\User\Controller\User\WechatInfoTrait;
use Mallto\User\Data\User;

class UserController extends AdminCommonController
{

    use UserBasicInfoTrait, WechatInfoTrait, ActionTrait;


    /**
     * 获取这个模块的标题
     *
     * @return mixed
     */
    protected function getHeaderTitle()
    {
        return "用户管理";
    }


    /**
     * 获取这个模块的Model
     *
     * @return mixed
     */
    protected function getModel()
    {
        return User::class;
    }


    protected function gridOption(Grid $grid)
    {
        $grid->userprofile()->wechat_user("微信昵称")->display(function ($value) {
            return $value ? $value['nickname'] : "";
        });
        $grid->avatar()->display(function ($value) {
            if ($value) {
                return $value;
            } else {
                $wechatUser = $this->userprofile->wechat_user ?? null;
                if ($wechatUser) {
                    return $wechatUser['avatar'];
                } else {
                    return null;
                }
            }
        })->image("", 50, 50);
        $grid->mobile()->sortable();

        $grid->filter(function (Grid\Filter $filter) {
            $filter->ilike("mobile");
        });

        $grid->disableCreation();
        $grid->actions(function (Grid\Displayers\Actions $actions) {
            if ( ! AdminUtils::isOwner()) {
                $actions->disableDelete();
            }
            
            $actions->disableView();
        });
    }


    protected function formOption(Form $form)
    {
        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
        });

        $user = User::find($this->currentId);

        $form->tab("基本资料", function (Form $form) use ($user) {
            $this->basicInfoForm($form, $user);
        })->tab("微信信息", function ($form) use ($user) {
            $this->wechatInfoForm($form, $user);
        });

        $form->ignore([ 'subject_id', 'new_mobile', 'mobile_code' ]);

        $form->saving(function ($form) {

            $this->autoSubjectSaving($form);
            $this->autoAdminUserSaving($form);
            $user = User::find($form->model()->id);

            //更新手机号
            $this->updateMobile($form, $user);
        });

    }

}
