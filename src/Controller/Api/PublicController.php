<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Controller\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Mallto\Admin\SubjectUtils;
use Mallto\User\Domain\SmsUsecase;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 29/11/2016
 * Time: 7:32 PM
 */
class PublicController extends Controller
{

    /**
     * @var SmsUsecase
     */
    private $smsUsecase;


    /**
     * PublicController constructor.
     *
     * @param SmsUsecase $smsUsecase
     */
    public function __construct(SmsUsecase $smsUsecase)
    {
        $this->smsUsecase = $smsUsecase;
    }


    /**
     * 获取短信验证码
     *
     * @return mixed
     */
    public function getMessageCode()
    {
        $mobile = \Request::input('mobile');

        $use = \Request::input("use", \Request::input("type", "register"));

        $subjectId = SubjectUtils::getSubjectId();

        $this->smsUsecase->sendSms($mobile, $subjectId, $use);

        return response()->nocontent();
    }

//    /**
//     * 获取邮箱验证码
//     */
//    public function getMailMessageCode()
//    {
//        $email = \Request::input('email');
//        $subjectId = SubjectUtils::getSubjectId();
//
//        $data['email'] = $email;
//        $validator = Validator::make($data,
//            ['email' => ['required', 'email'],]
//        );
//
//        if ($validator->fails()) {
//            throw new ValidationHttpException($validator->errors()->first());
//        }
//
//
//        $code = mt_rand(1000, 9999);
//
//        if (Cache::has('code'.$subjectId.$email)) {
//            //如果验证码还没过期,用户再次请求则重复发送一次验证码
//            $code = Cache::get('code'.$subjectId.$email);
////            throw new RateLimitExceededException();
//        } else {
//            Cache::put('code'.$subjectId.$email, $code, 5);
//        }
//
//        $message = (new Register($code))->onQueue('emails');
////        $message = new Register($code);
//
//
//        Mail::to(
//            $email
//        )
////            ->send(new Register($code));
//            ->queue($message);
//
//        return response()->nocontent();
//    }

}
