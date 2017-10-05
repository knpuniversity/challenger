<?php

namespace KnpU\Challenger;

use KnpU\Challenger\Exception\MissingEnvVarsException;

class TestConfig
{
    private $repoUrl;

    private $repoSha;

    private $challengeRunnerUrl;

    private $challengeRunnerToken;

    public function __construct($repoUrl, $repoSha, $challengeRunnerUrl, $challengeRunnerToken)
    {
        $this->repoUrl = $repoUrl;
        $this->repoSha = $repoSha;
        $this->challengeRunnerUrl = $challengeRunnerUrl;
        $this->challengeRunnerToken = $challengeRunnerToken;
    }

    static public function createFromEnvironment()
    {
        $envKeys = [
            'CHALLENGER_REPO_URL',
            'CHALLENGER_REPO_SHA',
            'CHALLENGER_CHALLENGE_RUNNER_URL',
            'CHALLENGER_CHALLENGE_RUNNER_TOKEN',
        ];

        $vars = [];
        $missingVars = [];
        foreach ($envKeys as $envVarName) {
            $vars[$envVarName] = getenv($envVarName);

            if (!$vars[$envVarName]) {
                $missingVars[] = $envVarName;
            }
        }

        if (!empty($missingVars)) {
            throw new MissingEnvVarsException($missingVars);
        }

        echo 'repo_URL: ' . $vars['CHALLENGER_REPO_URL'];
        echo ' ch_URL: ' . $vars['CHALLENGER_CHALLENGE_RUNNER_URL'];
        
        return new static(
            $vars['CHALLENGER_REPO_URL'],
            $vars['CHALLENGER_REPO_SHA'],
            $vars['CHALLENGER_CHALLENGE_RUNNER_URL'],
            $vars['CHALLENGER_CHALLENGE_RUNNER_TOKEN']
        );
    }

    public function getRepoUrl()
    {
        return $this->repoUrl;
    }

    public function getRepoSha()
    {
        return $this->repoSha;
    }

    public function getChallengeRunnerUrl()
    {
        return $this->challengeRunnerUrl;
    }

    public function getChallengeRunnerToken()
    {
        return $this->challengeRunnerToken;
    }
}
