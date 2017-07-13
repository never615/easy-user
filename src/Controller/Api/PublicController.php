<?php
namespace Mallto\User\Controller\Api;


use App\Http\Controllers\Controller;
use Encore\Admin\AppUtils;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Mallto\Tool\Exception\ThirdPartException;
use Mallto\Tool\Exception\ValidationHttpException;
use Mallto\User\Domain\Mail\Register;
use Mallto\User\Domain\PublicUsecase;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 29/11/2016
 * Time: 7:32 PM
 */
class PublicController extends Controller
{
    /**
     * @var PublicUsecase
     */
    private $publicUsecase;

    /**
     * PublicController constructor.
     *
     * @param PublicUsecase $publicUsecase
     */
    public function __construct(PublicUsecase $publicUsecase)
    {
        $this->publicUsecase = $publicUsecase;
    }


    /**
     * 获取短信验证码
     *
     * @return mixed
     */
    public function getMessageCode()
    {
        $mobile = Input::get('mobile');
        $subjectId = AppUtils::getSubjectId();

        $this->publicUsecase->sendSms($mobile, $subjectId);

        return response()->nocontent();
    }

    /**
     * 获取邮箱验证码
     */
    public function getMailMessageCode()
    {
        $email = Input::get('email');
        $subjectId = AppUtils::getSubjectId();

        $data['email'] = $email;
        $validator = Validator::make($data,
            ['email' => ['required', 'email'],]
        );

        if ($validator->fails()) {
            throw new ValidationHttpException($validator->errors()->first());
        }

        $faker = Faker::create();
        $code = $faker->numerify('####');


        if (Cache::has('code'.$subjectId.$email)) {
            //如果验证码还没过期,用户再次请求则重复发送一次验证码
            $code = Cache::get('code'.$subjectId.$email);
//            throw new RateLimitExceededException();
        } else {
            Cache::put('code'.$subjectId.$email, $code, 5);
        }

        $message = (new Register($code))->onQueue('emails');
//        $message = new Register($code);


        Mail::to(
            $email
        )
//            ->send(new Register($code));
            ->queue($message);

        return response()->nocontent();
    }


}
