<?php

namespace KnpU\Challenger\Command;

class Challenge
{
    private $challengeKey;

    private $isSuccessful;

    private $errorMessage;

    public function __construct($challengeKey)
    {
        $this->challengeKey = $challengeKey;
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
}
