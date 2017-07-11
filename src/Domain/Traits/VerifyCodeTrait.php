<?php
/**
 * Created by PhpStorm.
 * User: never615
 * Date: 11/07/2017
 * Time: 2:43 PM
 */

namespace Mallto\User\Domain\Traits;


use Encore\Admin\AppUtils;
use Illuminate\Support\Facades\Cache;
use Mallto\Tool\Exception\PermissionDeniedException;
use Mallto\Tool\Exception\ResourceException;

trait VerifyCodeTrait
{
    /**
     * 检查验证码
     *
     * @param $identifier
     * @param $code
     * @param $type
     * @return bool
     * @throws PermissionDeniedException
     * @throws ResourceException
     */
    protected function checkVerifyCode($identifier, $code, $type)
    {
        if (!empty($type)) {
            switch ($type) {
                case "mobile":
                    $tempCode = Cache::get('code'.AppUtils::getSubjectId().$identifier);
                    if ($tempCode !== $code) {
                        if (config("app.env") !== 'production' && $code == "000000") {

                        } else {
                            throw  new ResourceException("验证码错误");
                        }
                    }
                    break;
                default:
                    throw new PermissionDeniedException("不支持该类型注册:".$type);
                    break;
            }
        }
    }
}