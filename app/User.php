<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Class User
 * @package App
 *
 * @property int id
 * @property string name
 * @property string email
 */

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
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
        return [
            'name' => $this->name
        ];
    }

    /**
     * @param array $params
     * @return Builder|Model|static
     */
    public static function create(array $params)
    {
        $params['password'] = Hash::make($params['password']);

        return self::query()->create($params);
    }

    /**
     * @param $password
     * @return bool
     */
    public function resetPassword($password)
    {
        return $this->update([
            'password' => Hash::make($password)
        ]);
    }

    /**
     * @param $email
     * @return Builder|Model|object|null|static
     */
    public static function getByEmail($email)
    {
        return self::query()->where('email', $email)->first();
    }
}
