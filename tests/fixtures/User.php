<?php

namespace Parth\LaravelRbacSuite\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Parth\LaravelRbacSuite\Traits\HasRbacUser;

class User extends Authenticatable
{
    use HasRbacUser;

    protected $guarded = [];

    protected $table = 'users';

    protected $guard_name = 'web';
}
