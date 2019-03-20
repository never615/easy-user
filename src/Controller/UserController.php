<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Controller;


use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\MessageBag;
use Mallto\Admin\Controllers\Base\AdminCommonController;
use Mallto\Tool\Exception\PermissionDeniedException;
use Mallto\Tool\Exception\ResourceException;
use Mallto\User\Controller\User\UserBasicInfoTrait;
use Mallto\User\Controller\User\WechatInfoTrait;
use Mallto\User\Data\User;


class UserController extends AdminCommonController
{
    use UserBasicInfoTrait, WechatInfoTrait;


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
            return $value;
        })->image("", 50, 50);
        $grid->mobile()->sortable();

        $grid->filter(function (Grid\Filter $filter) {
            $filter->ilike("mobile");
        });

        $grid->disableCreation();
    }

    protected function formOption(Form $form)
    {
        $user = User::find($this->currentId);

        $form->tab("基本资料", function (Form $form) use ($user) {
            $this->basicInfoForm($form, $user);
        })->tab("微信信息", function ($form) {
            $this->wechatInfoForm($form);
        });


        $form->ignore(['subject_id', 'new_mobile', 'mobile_code']);


        $form->saving(function ($form) {

            $this->autoSubjectSaving($form);
            $this->autoAdminUserSaving($form);
            $user = User::find($form->model()->id);

            //更新手机号
            $this->updateMobile($form, $user);
        });

    }


    /**
     * 解绑
     *
     * 解绑手机和解绑微信
     *
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unbind($id)
    {
        $type = Input::get("type");

        $user = User::findOrFail($id);
        $member = $user->member;

        if (!$member) {
            throw new ResourceException("用户未注册会员,无法解绑");
        }


        $result = false;
        $description = "";

        switch ($type) {
            case 'wechat':
                $description = "解绑微信";
                $result = $this->unbindWechat($user);
                break;
            case 'mobile':
                if (empty($user->mobile)) {
                    throw new PermissionDeniedException("解绑失败,用户未绑定手机");
                } else {
                    $description = "解绑手机:".$user->mobile;
                    $result = $this->unbindMobile($user);
                }
                break;
            default:
                throw new PermissionDeniedException("无效的请求参数");

                break;
        }

        //记录操作人
        $admin = Admin::user();
        $user->admin_user_id = $admin->id;
        $user->save();


        if ($result) {
            $success = new MessageBag([
                'title' => "解绑成功",
            ]);

            return back()->with(compact('success'));
        } else {
            throw new ResourceException("解绑失败");
        }

    }
}
