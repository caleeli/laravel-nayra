<?php

namespace ProcessMaker\Laravel\Repositories;

use Illuminate\Support\Facades\Redis;
use ProcessMaker\Laravel\Contracts\RequestRepositoryInterface;
use ProcessMaker\Laravel\Models\Request;
use ProcessMaker\Nayra\Engine\ExecutionInstance;

class RequestRepository implements RequestRepositoryInterface
{
    /**
     * @return Request
     */
    public function find($id)
    {
        $sessionOnlyProcess = substr($id, 0, 1) === '_';
        if (!$sessionOnlyProcess) {
            return Request::find($id);
        }
        $serializedRequest = Redis::get("request:{$id}");
        if ($serializedRequest) {
            $request = unserialize($serializedRequest);
            return $request;
        }
        return null;
    }

    public function save(Request $request, ExecutionInstance $instance)
    {
        $id = $instance->getId();
        $sessionOnlyProcess = substr($id, 0, 1) === '_';
        if ($sessionOnlyProcess) {
            Redis::set("request:{$id}", serialize($request));
        } else {
            $request->save();
        }
    }

    public function make(array $data)
    {
        return Request::make($data);
    }
}
