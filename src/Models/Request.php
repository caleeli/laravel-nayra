<?php

namespace ProcessMaker\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $guarded = [];
    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'id' => 'string',
        'data' => 'array',
        'tokens' => 'array',
    ];
}
