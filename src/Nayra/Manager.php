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
     * @var JobManagerInterface $jobManager
     */
    private $jobManager;

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
     * @var Process $processData
     */
    private $processData;

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
        $this->loadProcess($processURL);
        $process = $this->bpmnRepository->getElementsByTagName('process')->item(0)->getBpmnElementInstance();
        $dataStorage = $process->getRepository()->createDataStore();
        $dataStorage->setData($data);
        $instance = $process->call($dataStorage);
        $instanceId = $this->getPerformerByTypeName($process, 'performer', 'identifier', $data) ?: uniqid();
        $instance->setId($instanceId);

        $this->engine->runToNextState();
        $this->saveState();
        $instance = $this->engine->loadExecutionInstance($instanceId, $this->bpmnRepository);
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
        $this->loadProcess($processURL);
        $event = $this->bpmnRepository->getStartEvent($eventId);
        $process = $event->getOwnerProcess();

        //Create a new data store
        $dataStorage = $process->getRepository()->createDataStore();
        $dataStorage->setData($data);
        $instance = $this->engine->createExecutionInstance(
            $process,
            $dataStorage
        );
        $instanceId = $this->getPerformerByTypeName($process, 'performer', 'identifier', $data) ?: uniqid();
        $instance->setId($instanceId);

        $event->start($instance);
        $this->engine->runToNextState();
        $this->saveState();

        $instance = $this->engine->loadExecutionInstance($instanceId, $this->bpmnRepository);
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
        if (!$this->processData) {
            return null;
        }
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

        // Custom implementation dataOutput without connection act as a post processor for all the user tasks
        $process = $instance->getProcess();
        $ioSpecification = $process->getBpmnElement()->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'ioSpecification')->item(0);
        $models = [];
        if ($ioSpecification) {
            $dataOutputs = $ioSpecification->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'dataOutput');
            foreach($dataOutputs as $dataOutput) {
                $varName = $dataOutput->getAttribute('name');
                // @todo get Model name from itemDefinition and xsi
                $varModel = $dataOutput->getAttribute('itemSubjectRef');
                $models[$varName] = [
                    'name' => $varName,
                    'model' => $varModel,
                ];
            }
        } else {
            $models = [];
        }
        // Update data
        foreach ($data as $key => $value) {
            if (isset($models[$key])) {
                $value = $this->postProcessValue($value, $models[$key]);
            }
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
        $instance = $this->engine->loadExecutionInstance($instance->getId(), $this->bpmnRepository);
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
        $instance = $this->engine->loadExecutionInstance($instanceId, $this->bpmnRepository);
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
        $instance = $this->engine->loadExecutionInstance($instanceId, $this->bpmnRepository);
        return $instance;
    }

    /**
     * Carga un proceso BPMN
     *
     * @param string $processName
     */
    private function loadProcess($filename)
    {
        $this->bpmnRepository->load(base_path($filename));
        $this->bpmn = $filename;
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
        if (!$processData) {
            return $processData;
        }
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
                ScriptTaskJob::dispatchSync($token);
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

    public function getPerformerByTypeName(EntityInterface $node, $type, $name, array $data)
    {
        $performers = $node->getBpmnElement()->getElementsByTagNameNS('http://www.omg.org/spec/BPMN/20100524/MODEL', $type);
        // find performer by name
        foreach ($performers as $performer) {
            if ($performer->getAttribute('name') === $name) {
                $expression = $performer->getElementsByTagNameNS('http://www.omg.org/spec/BPMN/20100524/MODEL', 'formalExpression')->item(0);
                $code = $expression->nodeValue;
                $self = $node;
                if (substr($code, 0, 5) === '<?php') {
                    return eval("?>$code;");
                } else {
                    return eval("return $code;");
                }
            }
        }
        return null;
    }

    public function getBpmn(): string
    {
        return $this->bpmn;
    }

    private function postProcessValue($value, $dataIO)
    {
        if ($dataIO['model']) {
            $model = 'App\Models\\' . $dataIO['model'];
            $id = $value['id'];
            if ($id) {
                $record = $model::find($id);
                if ($record) {
                    unset($value['id']);
                    \Log::debug('update model ' . $id, $value);
                    $record->update($value);
                    $value = $record->toArray();
                }
            } else {
                \Log::debug('create model', $value);
                $record = $model::create($value);
                $value = $record->toArray();
            }
        }
        return $value;
    }
}
