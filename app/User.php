<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email','jabatan', 'password','resignMonth','departmentId','optLock','joinDateView','resignYear','bankName','contactInfoId','startMonthPayment','nikNo','addressId','username','joinDate','salesmanUserId','nettoIncomeBefore','pphBefore','vehicleNo','posRoleId','startYearPayment','bankAccountName','employeeTaxStatus','bankAccount','branchId','bankCode','domisiliType','calculatePtkp','pph','npwpNo','suspended','employeeWorkStatus','name','salesman','resign', 'departmentId', 'id_approval', 'idKaryawan'
    ];


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function isHeadDeptApproverForSubmitter(int $submitterId): bool
    {
        if ($submitterId <= 0 || (int) $this->id <= 0) {
            return false;
        }

        return (int) static::whereKey($submitterId)->value('id_approval') === (int) $this->id;
    }

    /**
     * Final finance approvers at status 11. jabatan "Owner" is labeled Finance in admin UI.
     */
    public static function financeManagerNotificationRecipients()
    {
        return static::query()
            ->whereIn('jabatan', ['Finance Manager', 'Owner'])
            ->whereNotNull('phoneNumber')
            ->where('phoneNumber', '!=', '')
            ->get();
    }
}
