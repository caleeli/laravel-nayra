<?php

namespace ProcessMaker\Laravel\Repositories;

use ProcessMaker\Laravel\Contracts\RequestRepositoryInterface;
use ProcessMaker\Laravel\Models\Request;

class RequestRepository implements RequestRepositoryInterface
{
    public function find($id)
    {
        return Request::find($id);
    }

    public function make(array $data)
    {
        return Request::make($data);
    }
}
