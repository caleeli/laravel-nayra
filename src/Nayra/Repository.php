<?php

namespace ProcessMaker\Laravel\Nayra;

use ProcessMaker\Nayra\Contracts\Repositories\ExecutionInstanceRepositoryInterface;
use ProcessMaker\Nayra\Contracts\Repositories\TokenRepositoryInterface;
use ProcessMaker\Nayra\Contracts\RepositoryInterface;
use ProcessMaker\Nayra\RepositoryTrait;

class Repository implements RepositoryInterface
{
    use RepositoryTrait;

    /**
     * Create instance of FormalExpression.
     *
     * @return \ProcessMaker\Nayra\Contracts\Bpmn\FormalExpressionInterface
     */
    public function createFormalExpression()
    {
        return new FormalExpression();
    }

    /**
     * Create instance of CallActivity.
     *
     * @return \ProcessMaker\Nayra\Contracts\Bpmn\CallActivityInterface
     */
    public function createCallActivity()
    {
        return new CallActivity();
    }

    /**
     * Create instance of ScriptTask.
     *
     * @return \ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface
     */
    public function createScriptTask()
    {
        return new ScriptTask();
    }

    /**
     * Create a execution instance repository.
     *
     * @return ExecutionInstanceRepositoryInterface
     */
    public function createExecutionInstanceRepository()
    {
        return app(ExecutionInstanceRepositoryInterface::class);
    }

    /**
     * Creates a TokenRepository
     *
     * @return \ProcessMaker\Nayra\Contracts\Repositories\TokenRepositoryInterface
     */
    public function getTokenRepository()
    {
        return app(TokenRepositoryInterface::class);
    }
}
