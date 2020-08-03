<?php

namespace ProcessMaker\Laravel\Repositories;

use ProcessMaker\Laravel\Contracts\RequestRepositoryInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ParticipantInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Contracts\Repositories\ExecutionInstanceRepositoryInterface;
use ProcessMaker\Nayra\Contracts\Repositories\StorageInterface;
use ProcessMaker\Nayra\Engine\ExecutionInstance;

class InstanceRepository implements ExecutionInstanceRepositoryInterface
{
    /**
     * @var RequestRepositoryInterface
     */
    private $requestRepository;

    public function __construct(RequestRepositoryInterface $requestRepository)
    {
        $this->requestRepository = $requestRepository;
    }
    /**
     * Load an execution instance from a persistent storage.
     *
     * @param string $uid
     * @param StorageInterface $storage
     *
     * @return null|ExecutionInstanceInterface
     */
    public function loadExecutionInstanceByUid($uid, StorageInterface $storage)
    {
        $data = $this->requestRepository->find($uid);
        $instance = $this->createExecutionInstance();
        $instance->setId($uid);
        $process = $storage->getProcess($data->process_id);
        $dataStore = $storage->getFactory()->createDataStore();
        $dataStore->setData($data->data);
        $instance->setProcess($process);
        $instance->setDataStore($dataStore);
        $instance->setOwnerDocument($process->getOwnerDocument());
        $process->getTransitions($storage->getFactory());

        //Load tokens:
        foreach ($data->tokens as $tokenInfo) {
            $token = $storage->getFactory()->getTokenRepository()->createTokenInstance();
            $token->setProperties($tokenInfo);
            $element = $storage->getElementInstanceById($tokenInfo['element']);
            $element->addToken($instance, $token);
        }
        return $instance;
    }

    /**
     * Save an instance
     *
     * @param ExecutionInstance $instance
     */
    public function saveProcessInstance(ExecutionInstance $instance, $bpmn)
    {
        $id = $instance->getId();
        $processData = $this->requestRepository->find($id);
        if (!$processData) {
            $processData = $this->requestRepository->make([
                'process_id' => $instance->getProcess()->getId(),
                'bpmn' => $bpmn,
                'status' => 'ACTIVE',
            ]);
        }
        $dataStore = $instance->getDataStore();
        $tokens = $instance->getTokens();
        $mtokens = [];
        foreach ($tokens as $token) {
            $element = $token->getOwnerElement();
            $mtokens[] = [
                'id' => $token->getId(),
                'element' => $element->getId(),
                'name' => $element->getName(),
                'implementation' => $element->getProperty('implementation'),
                'status' => $token->getStatus(),
                'index' => $token->getIndex(),
            ];
        }
        $processData->tokens = $mtokens;
        $processData->data = $dataStore->getData();
        $processData->save();
        $instance->setId($processData->getKey());
    }

    /**
     * Creates an execution instance.
     *
     * @return ExecutionInstance
     */
    public function createExecutionInstance()
    {
        return new ExecutionInstance();
    }

    /**
     * Persists instance's data related to the event Process Instance Created
     *
     * @param ExecutionInstanceInterface $instance
     *
     * @return mixed
     */
    public function persistInstanceCreated(ExecutionInstanceInterface $instance)
    {
    }

    /**
     * Persists instance's data related to the event Process Instance Completed
     *
     * @param ExecutionInstanceInterface $instance
     *
     * @return mixed
     */
    public function persistInstanceCompleted(ExecutionInstanceInterface $instance)
    {
        $request = $this->requestRepository->find($instance->getId());
        $request->status = 'COMPLETED';
        $request->save();
    }

    /**
     * Persists collaboration between two instances.
     *
     * @param ExecutionInstanceInterface $target Target instance
     * @param ParticipantInterface $targetParticipant Participant related to the target instance
     * @param ExecutionInstanceInterface $source Source instance
     * @param ParticipantInterface $sourceParticipant
     */
    public function persistInstanceCollaboration(ExecutionInstanceInterface $target, ParticipantInterface $targetParticipant, ExecutionInstanceInterface $source, ParticipantInterface $sourceParticipant)
    {
    }
}
