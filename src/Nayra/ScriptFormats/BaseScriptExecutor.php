<?php

namespace ProcessMaker\Laravel\Nayra\ScriptFormats;

use ProcessMaker\Laravel\Nayra\ScriptTask;
use Exception;

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
     * @param mixed $model
     *
     * @return mixed
     */
    abstract public function runFile(ScriptTask $scriptTask, $model);

    /**
     * Run a script code
     *
     * @param ScriptTask $scriptTask
     * @param string $script
     *
     * @return mixed
     */
    public function run(ScriptTask $scriptTask, $model, $script)
    {
        file_put_contents($this->filename, $script);
        try {
            $__response = $this->runFile($scriptTask, $model);
        } catch (Exception $exception) {
            file_exists($this->filename) ? unlink($this->filename) : null;
            throw $exception;
        }
        file_exists($this->filename) ? unlink($this->filename) : null;
        return $__response;
    }
}
