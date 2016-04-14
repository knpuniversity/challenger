<?php

namespace KnpU\Challenger\Command;

use GuzzleHttp\Client;
use KnpU\Challenger\Exception\ApplicationBootTookTooLongException;
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

        $io->title(sprintf('Testing %s challenges', count($challenges)));
        $errors = [];
        foreach ($challenges as $challenge) {
            $this->testChallenge($challenge, $config);
            if ($challenge->isSuccessful()) {
                $io->write('.');
            } else {
                $io->write('F');
                $errors[] = [$challenge->getChallengeKey(), $challenge->getErrorMessage()];
            }
        }

        // yay!
        if (empty($errors)) {
            $sun = '\xF0\x9F\x8C\x9E';
            $io->success('All challenges were graded successfully! Have a sunny afternoon! '.$sun);

            return 0;
        }

        $io->table(['Challenge', 'Error'], $errors);

        return 1;
    }

    /**
     * @param TestConfig $config
     * @return Challenge[]
     */
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

    private function testChallenge(Challenge $challenge, TestConfig $config)
    {
        $this->bootMachine($challenge, $config);

        try {
            $this->waitForAppToBoot($challenge);
        } catch (ApplicationBootTookTooLongException $e) {
            $challenge->markAsFailed('Application took too long to boot!');

            return;
        }

        if (!$challenge->isGradeable()) {
            $challenge->isSuccessful();

            return;
        }

        $this->gradeChallenge($challenge, $config);
        $this->shutdownChallenge($challenge, $config);
    }

    private function bootMachine(Challenge $challenge, TestConfig $config)
    {
        $data = [
            'repoUrl' => $config->getRepoUrl(),
            'sha' => $config->getRepoSha(),
            'challengeKey' => $challenge->getChallengeKey(),
            'shouldBoot' => true
        ];

        $response = $this->client->post('/applications?token=' . $config->getChallengeRunnerToken(), [
            'body' => json_encode($data)
        ]);
        $responseData = json_decode($response->getBody(), true);
        $challenge->setAppIdAndToken(
            $responseData['id'],
            $responseData['token']
        );
    }

    private function waitForAppToBoot(Challenge $challenge)
    {
        $status = null;
        $bootedStatuses = ['booted', 'no_boot_needed'];
        $i = 0;
        while (!in_array($status, $bootedStatuses)) {
            if ($i > 60) {
                throw new ApplicationBootTookTooLongException();
            }

            $response = $this->client->get(sprintf(
                '/applications/%s/status?token='.$challenge->getAppToken(),
                $challenge->getAppId()
            ));

            $responseData = json_decode($response->getBody(), true);
            $status = $responseData['status'];

            sleep(1);
            $i++;
        }

        // currently, these are multiple choice challenges
        if ($status == 'no_boot_needed') {
            $challenge->setAsNotGradeable();
        }
    }

    private function gradeChallenge(Challenge $challenge, TestConfig $config)
    {
        $response = $this->client->post(sprintf('/testing/applications/%s/test?token='.$config->getChallengeRunnerToken(), $challenge->getAppId()));

        $responseData = json_decode($response->getBody(), true);
        $isSuccessful = $responseData['isSuccessful'];
        if ($isSuccessful) {
            $challenge->markAsSuccessful();
        } else {
            $challenge->markAsFailed($responseData['errorMessage']);
        }
    }

    private function shutdownChallenge(Challenge $challenge, TestConfig $config)
    {
        $this->client->post(sprintf(
            '/admin/applications/%s/shutdown?token=%s',
            $challenge->getAppId(),
            $config->getChallengeRunnerToken()
        ));
    }
}
