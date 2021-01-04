<?php

namespace Mallto\User\Controller\Api\MiniProgram;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mallto\Admin\SubjectUtils;
use Mallto\Mall\Data\SubjectSetting;
use Mallto\Tool\Exception\ResourceException;
use Mallto\User\Domain\MiniProgramUsecase;

class LoginController extends Controller
{

    /**
     * 小程序授权
     *
     * @param Request            $request
     * @param MiniProgramUsecase $miniProgramUsecase
     *
     * @return \Illuminate\Support\Collection
     * @throws \Illuminate\Validation\ValidationException
     */
    public function oauth(Request $request, MiniProgramUsecase $miniProgramUsecase)
    {
        $this->validate($request, [
            'code' => 'required',
        ]);

        $subject = SubjectUtils::getSubject();

        $subjectSetting = SubjectSetting::query()
            ->where('subject_id', $subject->id)
            ->first();

        if ( ! $subjectSetting) {
            throw new ResourceException('该主体未配置小程序信息');
        }

        return $miniProgramUsecase->oauth($request->code, $subjectSetting->fast_point_appid);
    }

}
