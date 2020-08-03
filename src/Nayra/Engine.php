<?php

namespace ProcessMaker\Laravel\Nayra;

use ProcessMaker\Nayra\Contracts\Engine\EngineInterface;
use ProcessMaker\Nayra\Contracts\Engine\JobManagerInterface;
use ProcessMaker\Nayra\Contracts\EventBusInterface;
use ProcessMaker\Nayra\Contracts\RepositoryInterface;
use ProcessMaker\Nayra\Engine\EngineTrait;

class Engine implements EngineInterface
{
    use EngineTrait;

    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @var EventBusInterface $dispatcher
     */
    protected $dispatcher;

    /**
     * Engine constructor.
     *
     * @param \ProcessMaker\Nayra\Contracts\RepositoryInterface $repository
     * @param \ProcessMaker\Nayra\Contracts\EventBusInterface $dispatcher
     * @param \ProcessMaker\Nayra\Contracts\Engine\JobManagerInterface|null $jobManager
     */
    public function __construct(RepositoryInterface $repository, $dispatcher, JobManagerInterface $jobManager = null)
    {
        $this->setRepository($repository);
        $this->setDispatcher($dispatcher);
        $this->setJobManager($jobManager);
    }

    /**
     * @return EventBusInterface
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @param EventBusInterface $dispatcher
     *
     * @return $this
     */
    public function setDispatcher($dispatcher)
    {
        $this->dispatcher = $dispatcher;
        return $this;
    }

    /**
     * @return FactoryInterface
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param RepositoryInterface $repository
     *
     * @return $this
     */
    public function setRepository(RepositoryInterface $repository)
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * Clear instances from the current instance
     *
     * @return void
     */
    public function clearInstances()
    {
        $this->executionInstances = [];
    }
}
