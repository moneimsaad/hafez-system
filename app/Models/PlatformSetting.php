<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $table = 'settings';

    protected $fillable = ['key', 'group', 'value'];
}
