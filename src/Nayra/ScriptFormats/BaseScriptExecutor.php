<?php

namespace ProcessMaker\Laravel\Nayra\ScriptFormats;

use Exception;
use ProcessMaker\Laravel\Nayra\ScriptTask;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

abstract class BaseScriptExecutor
{
    /**
     * File with script code
     *
     * @var string
     */
    public $filename;

    /**
     * Prepare a script to be executed.
     */
    public function __construct()
    {
        $this->filename = storage_path('app/' . uniqid('script_'));
    }

    /**
     * Run a file with the script code
     *
     * @param ScriptTask $scriptTask
     * @param mixed $token
     *
     * @return mixed
     */
    abstract public function runFile(ScriptTask $scriptTask, TokenInterface $token);

    /**
     * Run a script code
     *
     * @param ScriptTask $scriptTask
     * @param string $script
     *
     * @return mixed
     */
    public function run(ScriptTask $scriptTask, TokenInterface $token, $script)
    {
        file_put_contents($this->filename, $script);
        try {
            $__response = $this->runFile($scriptTask, $token);
        } catch (Exception $exception) {
            file_exists($this->filename) ? unlink($this->filename) : null;
            throw $exception;
        }
        file_exists($this->filename) ? unlink($this->filename) : null;
        return $__response;
    }
}
