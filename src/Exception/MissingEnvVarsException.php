<?php

namespace KnpU\Challenger\Exception;

use Exception;

class MissingEnvVarsException extends \Exception
{
    private $missingVars;

    public function __construct(array $missingVars, $code = 0, Exception $previous = null)
    {
        $this->missingVars = $missingVars;
        $message = sprintf('The following environment variables must be set: %s', implode(', ', $missingVars));

        parent::__construct($message, $code, $previous);
    }

    public function getMissingVars()
    {
        return $this->missingVars;
    }
}
