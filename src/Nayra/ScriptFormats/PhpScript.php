<?php

namespace ProcessMaker\Laravel\Nayra\ScriptFormats;

use ProcessMaker\Laravel\Nayra\ScriptTask;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

class PhpScript extends BaseScriptExecutor
{
    /**
     * Run a file with the script code
     *
     * @param ScriptTask $scriptTask
     * @param TokenInterface $token
     *
     * @return mixed
     */
    public function runFile(ScriptTask $scriptTask, TokenInterface $token)
    {
        $self = $this;
        $closure = function (ScriptTask $scriptTask, $token) use ($self) {
            $data = $token->getInstance()->getDataStore()->getData();
            return require $self->filename;
        };
        return $closure->call($scriptTask, $scriptTask, $token);
    }
}
