<?php

namespace ProcessMaker\Laravel\Nayra;

use ProcessMaker\Laravel\Contracts\RequestRepositoryInterface;
use ProcessMaker\Laravel\Jobs\ScriptTaskJob;
use ProcessMaker\Laravel\Models\Process;
use ProcessMaker\Laravel\Repositories\InstanceRepository;
use ProcessMaker\Nayra\Bpmn\Models\MessageEventDefinition;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\EntityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Contracts\Engine\JobManagerInterface;
use ProcessMaker\Nayra\Engine\ExecutionInstance;
use ProcessMaker\Nayra\Storage\BpmnDocument;

class Manager
{
    /**
     * @var Repository $repository
     */
    private $repository;

    /**
     * @var \ProcessMaker\Nayra\Contracts\EventBusInterface $dispatcher
     */
    private $dispatcher;

    /**
     * @var Engine $engine
     */
    private $engine;

    /**
     * @var BpmnDocument $bpmnRepository
     */
    private $bpmnRepository;

    /**
     * @var InstanceRepository
     */
    private $instanceRepository;

    /**
     * @var string $bpmn
     */
    private $bpmn;

    /**
     * @var RequestRepositoryInterface
     */
    private $requestRepository;

    public function __construct(RequestRepositoryInterface $requestRepository)
    {
        $this->requestRepository = $requestRepository;
        $this->repository = new Repository;
        $this->dispatcher = app('events');
        $this->jobManager = app(JobManagerInterface::class);
        $this->engine = new Engine($this->repository, $this->dispatcher, $this->jobManager);
        $this->registerEvents();
    }

    private function prepare()
    {
        $this->engine->clearInstances();
        $this->bpmnRepository = new BpmnDocument();
        $this->bpmnRepository->setEngine($this->engine);
        $this->bpmnRepository->setFactory($this->repository);
        $this->bpmnRepository->setSkipElementsNotImplemented(true);
        $this->engine->setRepository($this->repository);
        $this->instanceRepository = $this->repository->createExecutionInstanceRepository($this->bpmnRepository);
    }

    /**
     * Call a process
     *
     * @param string $processURL
     * @param array $data
     *
     * @return ExecutionInstanceInterface
     */
    public function callProcess($processURL, $data = [])
    {
        $this->prepare();
        $process = $this->loadProcess($processURL);
        $dataStorage = $process->getRepository()->createDataStore();
        $dataStorage->setData($data);
        $instance = $process->call($dataStorage);
        $this->engine->runToNextState();
        $this->saveState();
        return $instance;
    }

    /**
     * Start a process by start event
     *
     * @param strin $processURL
     * @param string $eventId
     * @param array $data
     * @return ExecutionInstanceInterface
     */
    public function startProcess($processURL, $eventId, $data = [])
    {
        $this->prepare();
        //Process
        $process = $this->loadProcess($processURL);
        $event = $this->bpmnRepository->getStartEvent($eventId);

        //Create a new data store
        $dataStorage = $process->getRepository()->createDataStore();
        $dataStorage->setData($data);
        $instance = $this->engine->createExecutionInstance(
            $process,
            $dataStorage
        );
        $event->start($instance);

        $this->engine->runToNextState();
        $this->saveState();
        return $instance;
    }

    /**
     * Get the list of tasks.
     *
     * @return type
     */
    public function tasks($instanceId)
    {
        $this->prepare();
        // Load the execution data
        $this->processData = $this->loadData($this->bpmnRepository, $instanceId);

        // Process and instance
        $instance = $this->engine->loadExecutionInstance($instanceId, $this->bpmnRepository);

        return $instance->getTokens();
    }

    /**
     * @return ExecutionInstance
     */
    public function getInstanceById($instanceId)
    {
        $this->prepare();
        // Load the execution data
        $this->processData = $this->loadData($this->bpmnRepository, $instanceId);

        // Process and instance
        $instance = $this->engine->loadExecutionInstance($instanceId, $this->bpmnRepository);

        return $instance;
    }

    /**
     * Complete a task
     *
     * @param string $instanceId
     * @param string $tokenId
     * @param array $data
     *
     * @return ExecutionInstanceInterface
     */
    public function completeTask($instanceId, $tokenId, $data = [])
    {
        $this->prepare();
        // Load the execution data
        $this->loadData($this->bpmnRepository, $instanceId);

        // Process and instance
        $instance = $this->engine->loadExecutionInstance($instanceId, $this->bpmnRepository);

        // Update data
        foreach ($data as $key => $value) {
            $instance->getDataStore()->putData($key, $value);
        }

        // Complete task
        $token = $instance->getTokens()->findFirst(function ($token) use ($tokenId) {
            return $token->getId() === $tokenId;
        });
        $task = $this->bpmnRepository->getActivity($token->getProperty('element'));
        $task->complete($token);
        $this->engine->runToNextState();
        $this->saveState();

        //Return the instance id
        return $instance;
    }

    /**
     * Cancela un proceso por id de instancia.
     *
     * @param string $instanceId
     *
     * @return ExecutionInstanceInterface
     */
    public function cancelProcess($instanceId)
    {
        $this->prepare();
        //Load the execution data
        $processData = $this->loadData($this->bpmnRepository, $instanceId);

        $processData->status = 'CANCELED';
        $processData->save();

        //Return the instance id
        $instance = $this->engine->loadExecutionInstance($instanceId, $this->bpmnRepository);
        return $instance;
    }

    /**
     * Execute a script task
     *
     * @param string $instanceId
     * @param string $tokenId
     *
     * @return ExecutionInstanceInterface
     */
    public function executeScript($instanceId, $tokenId)
    {
        $this->prepare();
        // Load the execution data
        $model = $this->loadData($this->bpmnRepository, $instanceId);

        // Process and instance
        $instance = $this->engine->loadExecutionInstance($instanceId, $this->bpmnRepository);

        // Complete task
        $token = $instance->getTokens()->findFirst(function ($token) use ($tokenId) {
            return $token->getId() === $tokenId;
        });
        $task = $this->bpmnRepository->getScriptTask($token->getProperty('element'));
        $task->runScript($token);
        $this->engine->runToNextState();
        $this->saveState();

        // Return the instance id
        return $instance;
    }

    /**
     * Execute a script task
     *
     * @param string $instanceId
     * @param string $tokenId
     * @param mixed $ref
     *
     * @return ExecutionInstanceInterface
     */
    public function executeEvent($instanceId, $tokenId, $ref)
    {
        $this->prepare();
        // Load the execution data
        $model = $this->loadData($this->bpmnRepository, $instanceId);

        // Process and instance
        $instance = $this->engine->loadExecutionInstance($instanceId, $this->bpmnRepository);

        if (!$tokenId) {
            $eventDefinition = $this->repository->createSignalEventDefinition();
            $signal = $this->repository->createSignal();
            $signal->setId($ref);
            $eventDefinition->setPayload($signal);
            $eventDefinition->setProperty('signalRef', $ref);
            $this->engine->getEventDefinitionBus()->dispatchEventDefinition(
                null,
                $eventDefinition,
                null
            );
        } else {

            // Execute event
            $token = $instance->getTokens()->findFirst(function ($token) use ($tokenId) {
                return $token->getId() === $tokenId;
            });

            $owner = $token->getOwnerElement();
            if ($owner instanceof ActivityInterface) {
                foreach ($owner->getBoundaryEvents() as $event) {
                    $eventDefinitions = $event->getEventDefinitions();
                    foreach ($eventDefinitions as $eventDefinition) {
                        if ($eventDefinition instanceof MessageEventDefinition && $eventDefinition->getPayload()->getId() === $ref) {
                            $event->execute($eventDefinition, $instance);
                        }
                    }
                }
            } else {
                $eventDefinitions = $owner->getEventDefinitions();
                foreach ($eventDefinitions as $eventDefinition) {
                    if ($eventDefinition instanceof MessageEventDefinition && $eventDefinition->getPayload()->getId() === $ref) {
                        $owner->execute($eventDefinition, $instance);
                    }
                }
            }
        }

        $this->engine->runToNextState();
        $this->saveState();

        // Return the instance id
        return $instance;
    }

    /**
     * Carga un proceso BPMN
     *
     * @param string $processName
     *
     * @return ProcessInterface
     */
    private function loadProcess($filename)
    {
        $this->bpmnRepository->load($filename);
        $this->bpmn = $filename;
        $process = $this->bpmnRepository->getElementsByTagName('process')->item(0)->getBpmnElementInstance();
        return $process;
    }

    /**
     * Carga los datos de la instancia almacenados en la BD.
     *
     * @param BpmnDocument $repository
     * @param type $instanceId
     *
     * @return Process
     */
    private function loadData(BpmnDocument $repository, $instanceId)
    {
        $processData = $this->requestRepository->find($instanceId);
        $this->loadProcess($processData->bpmn);
        return $processData;
    }

    /**
     * Listen for Workflow events
     *
     */
    private function registerEvents()
    {
        $this->dispatcher->listen(
            ScriptTaskInterface::EVENT_SCRIPT_TASK_ACTIVATED,
            function (ScriptTaskInterface $scriptTask, TokenInterface $token) {
                $this->saveProcessInstance($token->getInstance());
                ScriptTaskJob::dispatch($token);
            }
        );
    }

    /**
     * Save the instance state (tokens)
     *
     * @return void
     */
    private function saveState()
    {
        $processes = $this->bpmnRepository->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'process');
        foreach ($processes as $node) {
            $process = $node->getBpmnElementInstance();
            foreach ($process->getInstances() as $instance) {
                $this->saveProcessInstance($instance);
            }
        }
    }

    /**
     * Save the state of the process instance
     *
     * @param ExecutionInstance $instance
     *
     * @return self
     */
    public function saveProcessInstance(ExecutionInstanceInterface $instance)
    {
        $this->instanceRepository->saveProcessInstance($instance, $this->bpmn, $this->bpmnRepository);
        return $this;
    }

    public function getPerformerByTypeName(EntityInterface $node, $type, $name)
    {
        $performers = $node->getBpmnElement()->getElementsByTagNameNS('http://www.omg.org/spec/BPMN/20100524/MODEL', $type);
        // find performer by name
        foreach ($performers as $performer) {
            if ($performer->getAttribute('name') === $name) {
                $expression = $performer->getElementsByTagNameNS('http://www.omg.org/spec/BPMN/20100524/MODEL', 'formalExpression')->item(0);
                $expression = $expression->nodeValue;
                $self = $node;
                return eval("return $expression;");
            }
        }
        return null;
    }
}
