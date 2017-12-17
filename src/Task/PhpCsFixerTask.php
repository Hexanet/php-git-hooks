<?php

namespace Hexanet\PhpGitHooks\Task;

use Eloquent\Composer\Configuration\Element\Configuration;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Eloquent\Composer\Configuration\ConfigurationReader;

class PhpCsFixerTask
{
    /**
     * @var string
     */
    private $projectPath;

    /**
     * @param string $projectPath
     */
    public function __construct(string $projectPath)
    {
        $this->projectPath = $projectPath;
    }

    /**
     * @param SymfonyStyle $io
     * @param array        $files
     *
     * @return bool
     */
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
    /**
     * @param array $files
     *
     * @return array
     */
    private function checkWithPhpCsFixer(array $files)
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

    /**
     * @param array $files
     */
    private function fixWithPhpCsFixer(array $files)
    {
        $phpCsFixerProcessBuilder = new ProcessBuilder(['php', $this->getComposerConfiguration()->config()->binDir().'/php-cs-fixer', '--config-file=.php_cs', 'fix']);
        $phpCsFixerProcessBuilder->setWorkingDirectory($this->projectPath);

        $gitAddProcessBuilder = new ProcessBuilder(['git', 'add']);
        $gitAddProcessBuilder->setWorkingDirectory($this->projectPath);

        foreach ($files as $file) {
            if (strpos($file, '.php') === false) {
                continue;
            }

            $filePhpCsFixerProcessBuilder = clone $phpCsFixerProcessBuilder;
            $filePhpCsFixerProcessBuilder->add($file);
            $filePhpCsFixerProcessBuilder->getProcess()->run();

            $fileGitAddProcessBuilder = clone $gitAddProcessBuilder;
            $fileGitAddProcessBuilder->add($file);
            $fileGitAddProcessBuilder->getProcess()->run();
        }
    }

    /**
     * @return Configuration
     */
    private function getComposerConfiguration()
    {
        $reader = new ConfigurationReader();
        $reader = $reader->read('composer.json');

        return $reader;
    }
}
