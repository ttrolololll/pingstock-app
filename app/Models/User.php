<?php

namespace App\Models;

use App\Http\Controllers\Subscription\SubscriptionBuilder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable, Billable;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token', 'email_verification_code'
    ];

    public function serviceVerifications()
    {
        return $this->hasMany(ServiceVerification::class, 'user_id', 'id');
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * @return string
     */
    public function verificationLink()
    {
        return config('app.front_url') . '/verify/email/' . $this->email_verification_code;
    }

    /**
     * @param string $token
     * @return string
     */
    public function resetPasswordLink($token = '')
    {
        return config('app.front_url') . '/forgotpassword/reset/' . $token;
    }

    /**
     * Validates password to ensure at least one uppercase, lowercase, numeric, special character
     *
     * @param $password
     * @return bool
     */
    public static function validatePassword($password)
    {
        $match = preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*(_|[^\w])).+$/", $password);

        if ($match == 1) {
            return true;
        }

        return false;
    }

    public static function isUserServiceLinked(User $user, $service)
    {
        switch ($service) {
            case 'telegram':
                if ($user->telegram_id) {
                    return true;
                }
                break;
        }
        return false;
    }

    /**
     * Overload function in Billable trait
     *
     * @param $subscription
     * @param $plan
     * @return SubscriptionBuilder
     */
    public function newSubscription($subscription, $plan)
    {
        return new SubscriptionBuilder($this, $subscription, $plan);
    }
}
