<?php

namespace ProcessMaker\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
        'tokens' => 'array',
    ];
}
