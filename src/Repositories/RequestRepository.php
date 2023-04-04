<?php

namespace ProcessMaker\Laravel\Repositories;

use Illuminate\Support\Facades\Redis;
use ProcessMaker\Laravel\Contracts\RequestRepositoryInterface;
use ProcessMaker\Laravel\Facades\Nayra;
use ProcessMaker\Laravel\Models\Request;
use ProcessMaker\Nayra\Engine\ExecutionInstance;

class RequestRepository implements RequestRepositoryInterface
{
    /**
     * @return Request
     */
    public function find($id)
    {
        $isNumeric = is_numeric($id);
        if ($isNumeric) {
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
        $process = $instance->getProcess();
        if ($process->getProperty('processType') === 'Private') {
            $id = Nayra::getPerformerByTypeName($process, 'performer', 'identifier') ?: uniqid();
            $request->setIncrementing(false);
            $request->id = $id;
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
