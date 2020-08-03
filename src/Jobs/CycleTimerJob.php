<?php

namespace ProcessMaker\Laravel\Jobs;

use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Laravel\Facades\Nayra;
use ProcessMaker\Nayra\Contracts\Bpmn\EventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\FlowElementInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TimerEventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\JobManagerInterface;

class CycleTimerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $cycle;
    public $elementId;
    public $eventDefinitionPath;
    public $instanceId;
    public $next;
    public $tokenId;
    public $processURL;

    /**
     * Create a new job instance.
     *
     * @param TokenInterface  $token
     *
     * @return void
     */
    public function __construct(
        //$processURL,
        $cycle,
        TimerEventDefinitionInterface $eventDefinition,
        FlowElementInterface $element,
        TokenInterface $token = null,
        DateTime $next
    ) {
        //$this->processURL = $processURL;
        $this->cycle = json_encode($cycle);
        $this->elementId = $element->getId();
        $this->eventDefinitionPath = $eventDefinition->getBpmnElement()->getNodePath();
        $this->instanceId = $token->getInstance()->getId();
        $this->next = $next->format(DateTime::ATOM);
        $this->tokenId = $token->getId();
    }

    /**
     * Execute the job.
     *
     */
    public function handle()
    {
        $instance = Nayra::getInstanceById($this->instanceId);
        $token = $instance->getTokens()->findFirst(function ($token) {
            return $token->getId() === $this->tokenId;
        });
        if ($token->getStatus() === EventInterface::TOKEN_STATE_ACTIVE) {
            Nayra::executeEvent($this->instanceId, $this->tokenId);
            $element = $instance->getOwnerDocument()->getElementInstanceById($this->elementId);
            $eventDefinition = $this->getEventDefinition($instance->getOwnerDocument());
            $manager = app(JobManagerInterface::class);
            $next = $manager->getNextDateTimeCycle($this->getCycle(), $this->getNext());
            CycleTimerJob::dispatch($this->getCycle(), $eventDefinition, $element, $token, $next)
                ->delay($next);
        }
    }

    /**
     * @return DatePeriod
     */
    private function getCycle()
    {
        return $this->loadTimerFromJson($this->cycle);
    }

    /**
     * @return DatePeriod
     */
    private function getEventDefinition($dom)
    {
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query($this->eventDefinitionPath);
        return $nodes ? $nodes->item(0)->getBpmnElementInstance() : null;
    }

    /**
     * @return DateTime
     */
    private function getNext()
    {
        return new DateTime($this->next);
    }

    private function loadTimerFromJson($timer)
    {
        $start = $timer->start ? $this->loadTimerFromJson($timer->start) : null;
        $interval = $this->loadTimerFromJson($timer->interval);
        $end = $timer->end ? $this->loadTimerFromJson($timer->end) : null;
        $recurrences = $timer->recurrences;
        return new DatePeriod($start, $interval, [$end, $recurrences - 1]);
    }
}
