<?php

namespace Hexanet\PhpGitHooks\Task;

use Eloquent\Composer\Configuration\Element\Configuration;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Eloquent\Composer\Configuration\ConfigurationReader;

class PhpCsFixerTask
{
    /**
     * @var string
     */
    private $projectPath;

    public function __construct(string $projectPath)
    {
        $this->projectPath = $projectPath;
    }

    public function check(SymfonyStyle $io, array $files) : bool
    {
        $io->section('Checking coding standards');

        $phpCsFixerResult = $this->checkWithPhpCsFixer($files);

        if (!$phpCsFixerResult['result']) {
            $io->error('Coding standards errors detected');
            $io->table(['File', 'Error(s)'], $phpCsFixerResult['errors']);

            $this->fixWithPhpCsFixer($files);
            $io->comment('Coding standards successfully fixed');
        } else {
            $io->comment('No coding standard error detected');
        }

        return true;
    }

    private function checkWithPhpCsFixer(array $files) : array
    {
        $succeed = true;
        $errors = [];

        foreach ($files as $file) {
            if (strpos($file, '.php') === false) {
                continue;
            }

            $phpCsFixer = new Process(['php', $this->getComposerConfiguration()->config()->binDir().'/php-cs-fixer', '--dry-run', '--format=json', '-v', 'fix', $file]);
            $phpCsFixer->setWorkingDirectory($this->projectPath);
            $phpCsFixer->run();

            if (!$phpCsFixer->isSuccessful()) {
                $output = json_decode($phpCsFixer->getOutput(), true);


                if (!$output) {
                    throw new \RuntimeException(sprintf(
                        'PHP-CS-Fixer failed to check the files, to have more informations execute: %s',
                        $phpCsFixer->getCommandLine()
                    ));
                }

                $resultForFile = reset($output['files']);

                $errors[] = [
                    'file' => str_replace(realpath($phpCsFixer->getWorkingDirectory()), '', $resultForFile['name']),
                    'fixers' => implode(', ', $resultForFile['appliedFixers']),
                ];

                if ($succeed) {
                    $succeed = false;
                }
            }
        }

        return [
            'result' => $succeed,
            'errors' => $errors,
        ];
    }

    private function fixWithPhpCsFixer(array $files)
    {
        foreach ($files as $file) {
            if (strpos($file, '.php') === false) {
                continue;
            }

            $filePhpCsFixerProcessBuilder = new Process(['php', $this->getComposerConfiguration()->config()->binDir().'/php-cs-fixer', 'fix', $file]);
            $filePhpCsFixerProcessBuilder->setWorkingDirectory($this->projectPath);
            $filePhpCsFixerProcessBuilder->run();

            $fileGitAddProcessBuilder = new Process(['git', 'add', $file]);
            $fileGitAddProcessBuilder->setWorkingDirectory($this->projectPath);
            $fileGitAddProcessBuilder->run();
        }
    }

    private function getComposerConfiguration() : Configuration
    {
        $reader = new ConfigurationReader();
        $reader = $reader->read('composer.json');

        return $reader;
    }
}
