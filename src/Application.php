<?php

namespace KnpU\Challenger;

use App\Command\BootCommand;
use App\Command\ConfigureCommand;
use KnpU\Challenger\Command\TestCommand;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    /**
     * Gets the default commands that should always be available.
     *
     * @return array An array of default Command instances
     */
    protected function getDefaultCommands()
    {
        // Keep the core default commands to have the HelpCommand
        // which is used when using the --help option
        $defaultCommands = parent::getDefaultCommands();

        $defaultCommands[] = new TestCommand();

        return $defaultCommands;
    }
}