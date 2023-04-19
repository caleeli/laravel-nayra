<?php

namespace ProcessMaker\Laravel\Repositories;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Laravel\Facades\Nayra;
use ProcessMaker\Nayra\Bpmn\Collection;
use ProcessMaker\Nayra\Bpmn\Models\Token;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CatchEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CollectionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\EventBasedGatewayInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\GatewayInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ThrowEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Repositories\TokenRepositoryInterface;

/**
 * Token Repository.
 *
 * @package ProcessMaker\Models
 */
class TokenRepository implements TokenRepositoryInterface
{
    public $persistCalls = 0;

    /**
     * Create a token instance.
     *
     * @return TokenInterface
     */
    public function createTokenInstance()
    {
        $token = new Token();
        return $token;
    }

    /**
     * Load a token from a persistent storage.
     *
     * @param string $uid
     *
     * @return \ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface
     */
    public function loadTokenByUid($uid)
    {
    }

    /**
     * Create or update a activity to a persistent storage.
     *
     * @param \ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface $token
     * @param bool $saveChildElements
     *
     * @return $this
     */
    public function store(TokenInterface $token, $saveChildElements = false)
    {
    }

    /**
     * Persists instance and token data when a token arrives to an activity
     *
     * @param ActivityInterface $activity
     * @param TokenInterface $token
     *
     * @return mixed
     */
    public function persistActivityActivated(ActivityInterface $activity, TokenInterface $token)
    {
        // Evaluate user to assign
        $data = $token->getInstance()->getDataStore()->getData();
        $user = Nayra::getPerformerByTypeName($activity, 'humanPerformer', 'user', $data) ?: Auth::id();
        $token->setProperty('user', $user);
    }

    /**
     * Persists instance and token data when a token within an activity change to error state
     *
     * @param ActivityInterface $activity
     * @param TokenInterface $token
     *
     * @return mixed
     */
    public function persistActivityException(ActivityInterface $activity, TokenInterface $token)
    {
    }

    /**
     * Persists instance and token data when a token is completed within an activity
     *
     * @param ActivityInterface $activity
     * @param TokenInterface $token
     *
     * @return mixed
     */
    public function persistActivityCompleted(ActivityInterface $activity, TokenInterface $token)
    {
    }

    /**
     * Persists instance and token data when a token is closed by an activity
     *
     * @param ActivityInterface $activity
     * @param TokenInterface $token
     *
     * @return mixed
     */
    public function persistActivityClosed(ActivityInterface $activity, TokenInterface $token)
    {
    }

    /**
     * Get persist calls
     *
     * @return int
     */
    public function getPersistCalls()
    {
        return $this->persistCalls;
    }

    /**
     * Reset persist calls
     *
     */
    public function resetPersistCalls()
    {
        $this->persistCalls = 0;
    }

    /**
     * Persists instance and token data when a token arrives in a throw event
     *
     * @param ThrowEventInterface $event
     * @param TokenInterface $token
     *
     * @return mixed
     */
    public function persistThrowEventTokenArrives(ThrowEventInterface $event, TokenInterface $token)
    {
    }

    /**
     * Persists instance and token data when a token is consumed in a throw event
     *
     * @param ThrowEventInterface $endEvent
     * @param TokenInterface $token
     *
     * @return mixed
     */
    public function persistThrowEventTokenConsumed(ThrowEventInterface $endEvent, TokenInterface $token)
    {
    }

    /**
     * Persists instance and token data when a token is passed in a throw event
     *
     * @param ThrowEventInterface $endEvent
     * @param TokenInterface $token
     *
     * @return mixed
     */
    public function persistThrowEventTokenPassed(ThrowEventInterface $endEvent, TokenInterface $token)
    {
    }

    /**
     * Persists instance and token data when a token arrives in a gateway
     *
     * @param GatewayInterface $exclusiveGateway
     * @param TokenInterface $token
     *
     * @return mixed
     */
    public function persistGatewayTokenArrives(GatewayInterface $exclusiveGateway, TokenInterface $token)
    {
    }

    /**
     * Persists instance and token data when a token is consumed in a gateway
     *
     * @param GatewayInterface $exclusiveGateway
     * @param TokenInterface $token
     *
     * @return mixed
     */
    public function persistGatewayTokenConsumed(GatewayInterface $exclusiveGateway, TokenInterface $token)
    {
    }

    /**
     * Persists instance and token data when a token is passed in a gateway
     *
     * @param GatewayInterface $exclusiveGateway
     * @param TokenInterface $token
     *
     * @return mixed
     */
    public function persistGatewayTokenPassed(GatewayInterface $exclusiveGateway, TokenInterface $token)
    {
    }

    /**
     * Persists instance and token data when a token arrives in a catch event
     *
     * @param CatchEventInterface $intermediateCatchEvent
     * @param TokenInterface $token
     *
     * @return mixed
     */
    public function persistCatchEventTokenArrives(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
    }

    /**
     * Persists instance and token data when a token is consumed in a catch event
     *
     * @param CatchEventInterface $intermediateCatchEvent
     * @param TokenInterface $token
     *
     * @return mixed
     */
    public function persistCatchEventTokenConsumed(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
    }

    /**
     * Persists instance and token data when a token is passed in a catch event
     *
     * @param CatchEventInterface $intermediateCatchEvent
     * @param Collection $consumedTokens
     *
     * @return mixed
     */
    public function persistCatchEventTokenPassed(CatchEventInterface $intermediateCatchEvent, Collection $consumedTokens)
    {
    }

    /**
     * Persists instance and token data when a message arrives to a catch event
     *
     * @param CatchEventInterface $intermediateCatchEvent
     * @param TokenInterface $token
     */
    public function persistCatchEventMessageArrives(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
    }

    /**
     * Persists instance and token data when a message is consumed in a catch event
     *
     * @param CatchEventInterface $intermediateCatchEvent
     * @param TokenInterface $token
     */
    public function persistCatchEventMessageConsumed(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
    }

    public function persistStartEventTriggered(\ProcessMaker\Nayra\Contracts\Bpmn\StartEventInterface $startEvent, \ProcessMaker\Nayra\Contracts\Bpmn\CollectionInterface $tokens)
    {
    }

    public function persistEventBasedGatewayActivated(EventBasedGatewayInterface $eventBasedGateway, TokenInterface $passedToken, CollectionInterface $consumedTokens)
    {
    }
}
