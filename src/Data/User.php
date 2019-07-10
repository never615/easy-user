<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Data;


use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Mallto\Admin\Data\Subject;
use Mallto\Admin\Data\Traits\DynamicData;
use Mallto\Admin\Data\Traits\SelectSource;
use Mallto\Dangjian\Data\Company;
use Mallto\Dangjian\Data\Course;
use Mallto\Dangjian\Data\PartyTag;
use Mallto\Dangjian\Data\Qa\QaAnswer;
use Mallto\Dangjian\Data\Qa\QaComment;
use Mallto\Dangjian\Data\Qa\QaQuestion;
use Mallto\Dangjian\Data\UserCourse;
use Mallto\Dangjian\Data\UserCourseRecord;
use Mallto\Dangjian\Data\UserExam;
use Mallto\Dangjian\Data\UserExamAnswer;
use Mallto\Dangjian\Data\UserExamRecord;
use Mallto\Dangjian\Data\UserOnlineStudy;
use Mallto\Dangjian\Data\UserOnlineStudyRecord;
use Mallto\Mall\Data\Activity;
use Mallto\Mall\Data\Member;
use Mallto\Mall\Data\ParkingRecord;
use Mallto\Mall\Data\Seckill\UserSeckill;
use Mallto\Mall\Data\Shop;
use Mallto\Mall\Data\ShopComment;
use Mallto\Mall\Data\SpecialTopic;
use Mallto\Mall\Data\Ticket;
use Mallto\Mall\Data\UserCoupon;
use Mallto\Tool\Domain\Traits\TagTrait;

class User extends Authenticatable
{
    use Notifiable, DynamicData, HasApiTokens, SelectSource,
        \Mallto\User\Domain\Traits\UserAuthTrait, TagTrait;


    //用户标识(状态),如:注册中(用户信息待完善),注册中(用户标签待完善),黑名单等
    const STATUS = [
        "normal"    => "正常用户",
        "blacklist" => "黑名单用户",
    ];

    /**
     * 支持绑定的字段
     */
    const SUPPORT_BIND_TYPE = ['mobile'];

    /**
     * 注册来源
     */
    const REGISTER_FROM = [
        "wechat"     => "微信注册",
        "app"        => "app注册",
        "admin"      => "管理端创建",
        "admin_import"=>"管理端批量导入创建",
        "third_part" => "第三方系统注册",
    ];
    /**
     * 用户信息接口需要展示的字段
     *
     * @var array
     */
    protected $info = [
        "id",
        "email",
        "nickname",
    ];

    protected $guarded = [

    ];

    protected $dates = [
        'bind_car_at',
    ];

    protected $casts = [
        'online_time'        => "double",
        'total_online_time'  => "double",
        'offline_time'       => "double",
        'total_offline_time' => "double",
        'wechat_user'        => 'array',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
        'easemob_id',
        'easemob_password',
        'easemob_username',
        'remember_token',
    ];

    public static function selectSourceDate()
    {
        return static::dynamicData()->pluck("nickname", "id");
    }


    public static function djSelectSourceDate()
    {
        return static::dynamicData()
            ->whereHas('partyTags', function () {
            })->pluck("nickname", "id");
    }


    /**
     *
     * 获取用户信息接口需要返回的字段
     *
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
    }


    public function userAuths()
    {
        return $this->hasMany(UserAuth::class);
    }

    public function userCoupons()
    {
        return $this->hasMany(UserCoupon::class);
    }


    public function userProfile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function userSeckills()
    {
        return $this->hasMany(UserSeckill::class);
    }

    //------------- 问答开始 -------------------------------

    public function userQuestions()
    {
        return $this->hasMany(QaQuestion::class);
    }

    public function userAnswers()
    {
        return $this->hasMany(QaAnswer::class);
    }

    public function userComments()
    {
        return $this->hasMany(QaComment::class);
    }

    //------------- 问答结束 -------------------------------


    //------------------- 党建开始 ---------------------

    public function userCourses()
    {
        return $this->hasMany(UserCourse::class);
    }

    public function userCourseRecords()
    {
        return $this->hasMany(UserCourseRecord::class);
    }

    public function userExams()
    {
        return $this->hasMany(UserExam::class);
    }

    public function userExamRecords()
    {
        return $this->hasMany(UserExamRecord::class);
    }

    public function userExamAnswers()
    {
        return $this->hasMany(UserExamAnswer::class);
    }


    public function userOnlineStudies()
    {
        return $this->hasMany(UserOnlineStudy::class);
    }

    public function userOnlineStudyRecords()
    {
        return $this->hasMany(UserOnlineStudyRecord::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, "user_courses");
    }


    public function partyTags()
    {
        return $this->belongsToMany(PartyTag::class, "party_tag_users");
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function userSalt()
    {
        return $this->hasOne(UserSalt::class);
    }


    public function topSubject()
    {
        return $this->belongsTo(Subject::class, "top_subject_id");
    }


    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }


    public function getAvatarAttribute($value)
    {
        if (request()->header("mode") === "api") {
            if (empty($value)) {
                $user = User::find($this->id);
                if ($user && $user->userProfile && $user->userProfile->wechat_user) {
                    return $user->userProfile->wechat_user['avatar'];
                }

                return null;
            }

            if (starts_with($value, "http")) {
                return $value;
            }
        } else {
            if ($value) {
                if (starts_with($value, "http")) {
                    return $value;
                } else {
                    return config("app.file_url_prefix").$value;
                }
            } else {
                return $value;
            }
        }
    }

    public function member()
    {
        return $this->hasOne(Member::class);
    }


    public function shopComments()
    {
        return $this->hasMany(ShopComment::class);
    }


    public function shops()
    {
        return $this->morphedByMany(Shop::class, 'userable', 'user_collections');
    }


    public function activities()
    {
        return $this->morphedByMany(Activity::class, 'userable', 'user_collections');
    }

    public function topices()
    {
        return $this->morphedByMany(SpecialTopic::class, 'userable', 'user_collections');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function parkingRecords()
    {
        return $this->hasMany(ParkingRecord::class);
    }


    //todo 移动到对应库

    /**
     * 查询用户今年的这次考试
     *
     * @param $id
     * @return mixed
     */
    public function examThisYear($id)
    {
        return $this->userExams()
            ->where("exam_id", $id)
            ->whereYear("created_at", Carbon::now()->year)
            ->first();
    }

    /**
     * 查询用户今年的这次学习
     *
     * @param $id
     * @return mixed
     */
    public function onlineStudyThisYear($id)
    {
        return $this->userOnlineStudies()
            ->where("online_study_id", $id)
            ->whereYear("created_at", Carbon::now()->year)
            ->first();
    }


    //------------------- 党建结束 ---------------------


}
