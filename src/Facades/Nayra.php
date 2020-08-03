<?php

namespace ProcessMaker\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Workflow Manager Facade
 *
 * @see \ProcessMaker\Laravel\Nayra\Manager
 *
 * @method static \ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface cancelProcess(string $instanceId)
 * @method static \ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface startProcess(string $processURL, string $eventId, array $data)
 * @method static \ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface callProcess(string $processURL, array $data)
 * @method static \ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface completeTask(string $instanceId, string $tokenId, array $data)
 * @method static \ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface getInstanceById(string $instanceId)
 * @method static \ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface executeScript(string $instanceId, string $tokenId)
 * @method static \ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface executeEvent(string $instanceId, string $tokenId, $eventDefinition)
 */
class Nayra extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'nayra.manager';
    }
}
