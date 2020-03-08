<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceVerification extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'service',
        'token',
        'expires_at'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'expires_at'
    ];

    public static $validServices = ['telegram'];

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }

    /**
     * @param $service
     * @param $token
     * @param $email
     * @return ServiceVerification
     * @throws \Exception
     */
    public static function verifyToken($service, $token, $email)
    {
        if (!$service || !$token || !$email) {
            throw new \Exception('Fields service, token and email must not be empty');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Field email invalid format');
        }
        if (!self::isValidService($service)) {
            throw new \Exception('Unsupported service');
        }

        $sv = self::where([
            ['service', '=', $service],
            ['token', '=', $token],
            ['email', '=', $email],
        ])->first();

        if (!$sv) {
            throw new \Exception('Invalid token');
        }
        if ($sv->expires_at->lte(now())) {
            throw new \Exception('Token expired');
        }

        return $sv;
    }

    /**
     * @param $service
     * @return bool
     */
    public static function isValidService($service)
    {
        return in_array($service, self::$validServices);
    }
}
