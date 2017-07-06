<?php
namespace Mallto\User\Controller\Api;


use Mallto\Tool\Domain\Exception\ThirdPartException;
use Mallto\Tool\Domain\Exception\ValidationHttpException;
use App\Http\Controllers\Controller;
use Encore\Admin\AppUtils;
use Faker\Factory as Faker;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Mallto\User\Domain\Mail\Register;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 29/11/2016
 * Time: 7:32 PM
 */
class PublicController extends Controller
{
    /**
     * 获取短信验证码
     *
     * @return mixed
     */
    public function getMessageCode()
    {
        $mobile = Input::get('mobile');
        $subjectId = AppUtils::getSubjectId();

        $data['mobile'] = $mobile;
        $validator = Validator::make($data,
            ['mobile' => ['required', 'mobile'],]
        );

        if ($validator->fails()) {
            throw new ValidationHttpException($validator->errors()->first());
        }

        $faker = Faker::create();
        $code = $faker->numerify('####');


        if (Cache::has('code'.$subjectId.$mobile)) {
            //如果验证码还没过期,用户再次请求则重复发送一次验证码
            $code = Cache::get('code'.$subjectId.$mobile);
//            throw new RateLimitExceededException();
        } else {
            Cache::put('code'.$subjectId.$mobile, $code, 5);
        }

        $subject = AppUtils::getSubject();
        $name = $subject->name;
        //模板id
        $tplValue = urlencode("#code#=$code&#app#=$name");

        $client = new Client();
        $response = $client->request('GET',
            "http://v.juhe.cn/sms/send", [
                "query" => [
                    "mobile"    => $mobile,
                    "tpl_id"    => "36548",
                    "tpl_value" => $tplValue,
                    "key"       => "c5f32ac02366e464f51a566bb9073af0",
                ],
            ]);

        $res = json_decode($response->getBody(), true);
        if ($res['error_code'] != 0) {
            throw new ThirdPartException($res['reason']);
        } else {
            return response()->nocontent();
        }
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
