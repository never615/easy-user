<?php

namespace Mallto\User\Data;


use Encore\Admin\Auth\Database\Traits\DynamicData;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Mallto\Activity\Data\QaAnswer;
use Mallto\Activity\Data\QaComment;
use Mallto\Activity\Data\QaQuestion;
use Mallto\Activity\Data\UserSeckill;
use Mallto\Dangjian\Data\Course;
use Mallto\Dangjian\Data\PartyTag;
use Mallto\Dangjian\Data\UserCourse;
use Mallto\Dangjian\Data\UserCourseRecord;
use Mallto\Dangjian\Data\UserExam;
use Mallto\Dangjian\Data\UserExamRecord;
use Mallto\Dangjian\Data\UserOnlineStudy;
use Mallto\Dangjian\Data\UserOnlineStudyRecord;
use Mallto\Mall\Data\Member;
use Mallto\Mall\Data\ParkingRecord;
use Mallto\Mall\Data\PointHistory;
use Mallto\Mall\Data\Ticket;
use Mallto\Mall\Data\UserCoupon;

class User extends Authenticatable
{
    use Notifiable, DynamicData, HasApiTokens;

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


    public function member()
    {
        return $this->hasOne(Member::class);
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


    public function pointHistories()
    {
        return $this->hasMany(PointHistory::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function parkingRecords()
    {
        return $this->hasMany(ParkingRecord::class);
    }

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
        return $this->belongsToMany(PartyTag::class);
    }

}
