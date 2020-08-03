<?php

namespace ProcessMaker\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Laravel\Facades\Nayra;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

class ScriptTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tokenId;
    protected $instanceId;

    /**
     * Create a new job instance.
     *
     * @param TokenInterface  $token
     *
     * @return void
     */
    public function __construct(TokenInterface $token)
    {
        $this->tokenId = $token->getId();
        $this->instanceId = $token->getInstance()->getId();
    }

    /**
     * Execute the job.
     *
     */
    public function handle()
    {
        Nayra::executeScript($this->instanceId, $this->tokenId);
    }
}
