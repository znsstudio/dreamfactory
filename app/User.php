<?php namespace DreamFactory;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use Authenticatable, CanResetPassword;

    //******************************************************************************
    //* Members
    //******************************************************************************

    /** @inheritdoc */
    protected $table = 'users';
    /** @inheritdoc */
    protected $fillable = ['name', 'email', 'password'];
    /** @inheritdoc */
    protected $hidden = ['password', 'remember_token'];

}
