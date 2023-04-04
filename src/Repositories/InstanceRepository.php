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
     * @var RequestRepository
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
        $processModel = $this->requestRepository->find($uid);
        $instance = $this->createExecutionInstance();
        $instance->setId($uid);
        $process = $storage->getProcess($processModel->process_id);
        $dataStore = $storage->getFactory()->createDataStore();
        $dataStore->setData($processModel->data);
        $instance->setProcess($process);
        $instance->setDataStore($dataStore);
        $instance->setOwnerDocument($process->getOwnerDocument());
        $process->getTransitions($storage->getFactory());

        //Load tokens:
        foreach ($processModel->tokens as $tokenInfo) {
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
        $processModel = $this->requestRepository->find($id);
        $process = $instance->getProcess();
        if (!$processModel) {
            $processModel = $this->requestRepository->make([
                'process_id' => $process->getId(),
                'bpmn' => $bpmn,
                'status' => 'ACTIVE',
            ]);
        }
        $dataStore = $instance->getDataStore();
        $tokens = $instance->getTokens();
        $processModel->process_id = $process->getId();
        $processModel->bpmn = $bpmn;
        $processModel->tokens = self::dumpTokens($tokens);
        $processModel->data = $dataStore->getData();
        $this->requestRepository->save($processModel, $instance);
        $instance->setId($processModel->getKey());
    }

    public static function dumpTokens($tokens)
    {
        $tokensDump = [];
        foreach ($tokens as $token) {
            $element = $token->getOwnerElement();
            $tokensDump[] = [
                'id' => $token->getId(),
                'element' => $element->getId(),
                'name' => $element->getName(),
                'implementation' => $element->getProperty('implementation'),
                'user' => $token->getProperty('user'),
                'status' => $token->getStatus(),
                'index' => $token->getIndex(),
            ];
        }
        return $tokensDump;
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
