<?php

namespace KnpU\Challenger\Command;

class Challenge
{
    private $challengeKey;

    private $appId;

    private $appToken;

    private $isSuccessful;

    private $errorMessage;

    private $isGradeable = true;

    public function __construct($challengeKey)
    {
        $this->challengeKey = $challengeKey;
    }

    public function setAppIdAndToken($appId, $appToken)
    {
        $this->appId = $appId;
        $this->appToken = $appToken;
    }

    public function markAsSuccessful()
    {
        $this->isSuccessful = true;
    }

    public function markAsFailed($errorMessage)
    {
        $this->isSuccessful = false;
        $this->errorMessage = $errorMessage;
    }

    public function getChallengeKey()
    {
        return $this->challengeKey;
    }

    public function getAppId()
    {
        return $this->appId;
    }

    public function getAppToken()
    {
        return $this->appToken;
    }

    public function isSuccessful()
    {
        return $this->isSuccessful;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function setAsNotGradeable()
    {
        $this->isGradeable = false;
    }

    /**
     * Does this challenge need grading?
     *
     * @return bool
     */
    public function isGradeable()
    {
        return $this->isGradeable;
    }
}
