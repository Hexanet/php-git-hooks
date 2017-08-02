<?php

namespace Hexanet\PhpGitHooks\Task;

use Eloquent\Composer\Configuration\Element\Configuration;
use Symfony\Component\Console\Style\SymfonyStyle;
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
        $io->section('Vérification des coding styles');

        $phpCsFixerResult = $this->checkWithPhpCsFixer($files);

        if (!$phpCsFixerResult['result']) {
            $io->error('Erreurs de coding styles détectées');
            $io->table(['Fichier', 'Erreur(s)'], $phpCsFixerResult['errors']);

            $this->fixWithPhpCsFixer($files);
            $io->comment('Coding styles corrigés avec succès');
        } else {
            $io->comment('Aucune erreur de coding styles détectée');
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

            $processBuilder = new ProcessBuilder(['php', $this->getComposerConfiguration()->config()->binDir().'/php-cs-fixer', '--config-file=.php_cs', '--dry-run', '--format=json', '-v', 'fix', $file]);
            $processBuilder->setWorkingDirectory($this->projectPath);
            $phpCsFixer = $processBuilder->getProcess();
            $phpCsFixer->run();

            if (!$phpCsFixer->isSuccessful()) {
                $output = json_decode($phpCsFixer->getOutput(), true);

                if (!$output) {
                    continue;
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