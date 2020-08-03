<?php

namespace ProcessMaker\Laravel\Nayra;

use Exception;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Laravel\Models\Process as Model;
use ProcessMaker\Laravel\Nayra\ScriptFormats\BaseScriptExecutor;
use ProcessMaker\Laravel\Nayra\ScriptFormats\BashScript;
use ProcessMaker\Laravel\Nayra\ScriptFormats\PhpScript;
use ProcessMaker\Nayra\Bpmn\Models\ScriptTask as ScriptTaskBase;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

/**
 * This activity will raise an exception when executed.
 *
 */
class ScriptTask extends ScriptTaskBase
{
    const scriptFormats = [
        'application/x-php' => PhpScript::class,
        'application/x-bash' => BashScript::class,
    ];

    /**
     * Model instance for the process instance
     *
     * @var Process
     */
    private $model = null;

    /**
     * Runs the ScriptTask
     *
     * @param TokenInterface $token
     */
    public function runScript(TokenInterface $token)
    {
        //if the script runs correctly complete te activity, otherwise set the token to failed state
        if ($this->executeScript($token, $this->getScript(), $this->getScriptFormat())) {
            $this->complete($token);
        } else {
            $token->setStatus(ActivityInterface::TOKEN_STATE_FAILING);
        }
    }

    /**
     * Script runner fot testing purposes that just evaluates the sent php code
     *
     * @param TokenInterface $token
     * @param string $script
     *
     * @return bool
     */
    private function executeScript(TokenInterface $token, $script, $format)
    {
        try {
            $response = $this->runCode($this->model, $script, $format);
            if (is_array($response)) {
                foreach($response as $key => $value) {
                    $token->getInstance()->getDataStore()->putData($key, $value);
                }
            }
            return true;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    /**
     * Run the code isolated
     *
     * @param Model $model
     * @param string $__filename
     *
     * @return mixed
     */
    private function runCode($model, $script, $format)
    {
        return $this->scriptFactory($format)->run($this, $model, $script);
    }

    /**
     * Create a script exector for the required $format
     *
     * @param string $format
     *
     * @return BaseScriptExecutor
     */
    private function scriptFactory($format)
    {
        $class = self::scriptFormats[$format];
        return new $class;
    }

    /**
     * Set the model of the process instance
     *
     * @param \ProcessMaker\Laravel\Models\Process $model
     *
     * @return self
     */
    public function setModel(Model $model)
    {
        $this->model = $model;
        return $this;
    }
}
