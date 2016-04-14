<?php

namespace KnpU\Challenger\Command;

use GuzzleHttp\Client;
use KnpU\Challenger\Exception\MissingEnvVarsException;
use KnpU\Challenger\TestConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestCommand extends Command
{
    /**
     * @var Client
     */
    private $client;

    protected function configure()
    {
        $this
            ->setName('test')
            ->setDescription('Tests a set of challenges. All configuration is done via environment parameters')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $config = TestConfig::createFromEnvironment();
        } catch (MissingEnvVarsException $e) {
            $lines = [
                'Missing environment variables'
            ];
            foreach ($e->getMissingVars() as $varName) {
                $lines[] = sprintf('export %s=""', $varName);
            }

            $io->error($lines);

            return 1;
        }

        $this->client = new Client([
            'base_uri' => $config->getChallengeRunnerUrl()
        ]);

        // request to challenge runner for list of challenges
        // loop over each challenge and:
        //      A) boot challenge
        //      B) ask for grading
        //      C) shutdown challenge

        $challenges = $this->fetchChallenges($config);
        var_dump($challenges);
    }

    private function fetchChallenges(TestConfig $config)
    {
        $data = [
            'repoUrl' => $config->getRepoUrl(),
            'sha' => $config->getRepoSha(),
            'token' => $config->getChallengeRunnerToken()
        ];
        $response = $this->client->get(
            '/testing/challenges?'.http_build_query($data)
        );

        $data = json_decode($response->getBody(), true);
        $challenges = [];
        foreach ($data['challenges'] as $challengeKey) {
            $challenges[] = new Challenge($challengeKey);
        }

        return $challenges;
    }
}
