<?php

namespace ProcessMaker\Laravel\Jobs;

use DOMXPath;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Laravel\Facades\Nayra;
use ProcessMaker\Nayra\Contracts\Bpmn\FlowElementInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TimerEventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Storage\BpmnDocument;

class TimerJob implements ShouldQueue
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
        TokenInterface $token = null
    ) {
        //$this->processURL = $processURL;
        $this->cycle = json_encode($cycle);
        $this->elementId = $element->getId();
        $this->eventDefinitionPath = $eventDefinition->getBpmnElement()->getNodePath();
        $this->instanceId = $token->getInstance()->getId();
        $this->tokenId = $token->getId();
    }

    /**
     * Execute the job.
     *
     */
    public function handle()
    {
        $instance = Nayra::getInstanceById($this->instanceId);
        $eventDefinition = $this->getEventDefinition($instance->getOwnerDocument());
        Nayra::executeEvent($this->instanceId, $this->tokenId, $eventDefinition);
    }

    /**
     * @return DatePeriod
     */
    private function getEventDefinition(BpmnDocument $dom)
    {
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query($this->eventDefinitionPath);
        return $nodes ? $nodes->item(0)->getBpmnElementInstance() : null;
    }
}
