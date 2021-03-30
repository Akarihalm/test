<?php

namespace App\Utils;

use App\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Foundation\Application;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\JWTGuard;
use Tymon\JWTAuth\Payload;

/**
 * Class JwtManager
 * @package App\Utils
 */

class JwtManager
{
    const CLAIMS_TOKEN_KEY = 'token';
    const CLAIMS_TOKEN_ACCESS_VALUE = 'access';
    const CLAIMS_TOKEN_REFRESH_VALUE = 'refresh';
    const CLAIMS_TOKEN_RESET_PASSWORD_VALUE = 'reset_password';
    const CLAIMS_TOKEN_EMAIL_VERIFY_VALUE = 'email_verify';

    /**
     * @return Factory|Guard|StatefulGuard|Application|JWTGuard
     */
    private static function guard()
    {
        return auth('api');
    }

    /**
     * @return Payload
     */
    private static function payload()
    {
        return self::guard()->payload();
    }

    /**
     * @return mixed
     */
    private static function getTokenType()
    {
        return self::payload()->get(self::CLAIMS_TOKEN_KEY);
    }

    /**
     * @return Authenticatable|null|JWTSubject|User
     */
    public static function user()
    {
        return self::guard()->user();
    }

    /**
     * @param array $credentials
     * @return bool|string
     */
    public static function login($credentials = [])
    {
        return self::guard()->attempt($credentials, true);
    }

    /**
     * @return string
     */
    public static function createRefreshToken()
    {
        return self::guard()
            ->setTTL(config('jwt.refresh_ttl'))
            ->claims([
                self::CLAIMS_TOKEN_KEY => self::CLAIMS_TOKEN_REFRESH_VALUE
            ])
            ->login(self::user());
    }

    /**
     * @param $refreshToken
     * @return string
     */
    public static function refresh($refreshToken)
    {
        $user = self::guard()->setToken($refreshToken)->user();
        if (! $user || ! self::accessedByRefreshToken()) {
            return false;
        }

        return self::guard()->claims([
            self::CLAIMS_TOKEN_KEY => self::CLAIMS_TOKEN_ACCESS_VALUE
        ])->refresh();
    }

    /**
     * @param JWTSubject $user
     * @return string
     */
    public static function createPasswordResetToken(JWTSubject $user)
    {
        return self::guard()
            ->setTTL(config('jwt.reset_password_ttl'))
            ->claims([
                self::CLAIMS_TOKEN_KEY => self::CLAIMS_TOKEN_RESET_PASSWORD_VALUE
            ])
            ->login($user);
    }

    /**
     * @param $token
     * @return bool
     */
    public static function checkPasswordResetToken($token)
    {
        $user = self::guard()->setToken($token)->user();

        return $user && self::accessedByResetPasswordToken();
    }

    /**
     * @return bool
     */
    public static function clearPasswordResetToken()
    {
        return !! self::refreshByClaims([
            self::CLAIMS_TOKEN_KEY => self::CLAIMS_TOKEN_RESET_PASSWORD_VALUE
        ]);
    }

    /**
     * @param JWTSubject $user
     * @return string
     */
    public static function createEmailVerifyToken(JWTSubject $user)
    {
        return self::guard()
            ->setTTL(config('jwt.email_verify_ttl'))
            ->claims([
                self::CLAIMS_TOKEN_KEY => self::CLAIMS_TOKEN_EMAIL_VERIFY_VALUE
            ])
            ->login($user);
    }

    /**
     * @param $token
     * @return bool
     */
    public static function checkEmailVerifyToken($token)
    {
        $user = self::guard()->setToken($token)->user();

        return $user && self::accessedByEmailVerifyToken();
    }

    /**
     * @return bool
     */
    public static function clearEmailVerifyToken()
    {
        return !! self::refreshByClaims([
            self::CLAIMS_TOKEN_KEY => self::CLAIMS_TOKEN_EMAIL_VERIFY_VALUE
        ]);
    }

    /**
     * @param array $claims
     * @return string
     */
    private static function refreshByClaims($claims = [])
    {
        return self::guard()
            ->claims($claims)
            ->refresh();
    }

    /**
     * @return bool
     */
    public static function accessedByRefreshToken()
    {
        return self::getTokenType() === self::CLAIMS_TOKEN_REFRESH_VALUE;
    }

    /**
     * @return bool
     */
    public static function accessedByResetPasswordToken()
    {
        return self::getTokenType() === self::CLAIMS_TOKEN_RESET_PASSWORD_VALUE;
    }

    /**
     * @return bool
     */
    public static function accessedByEmailVerifyToken()
    {
        return self::getTokenType() === self::CLAIMS_TOKEN_EMAIL_VERIFY_VALUE;
    }
}
