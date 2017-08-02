<?php

namespace Hexanet\PhpGitHooks\Task;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\ProcessBuilder;

class LintTask
{
    /**
     * @param SymfonyStyle $io
     * @param array        $files
     *
     * @return bool
     */
    public function check(SymfonyStyle $io, array $files) : bool
    {
        $io->section('Vérification de la syntaxe des fichiers PHP');

        $phpLintResult = $this->checkWithPhpLint($files);

        if (!$phpLintResult['result']) {
            $io->error('Erreurs de syntaxe détectés');
            $io->table(['Fichier', 'Erreur'], $phpLintResult['errors']);

            return false;
        }

        $io->comment('Aucune erreur de syntaxe détectée');

        return true;
    }

    /**
     * @param array $files
     *
     * @return array
     */
    private function checkWithPhpLint($files)
    {
        $needle = '/(\.php)|(\.inc)$/';

        $succeed = true;
        $errors = [];

        foreach ($files as $file) {
            if (!preg_match($needle, $file)) {
                continue;
            }

            $processBuilder = new ProcessBuilder(array('php', '-l', $file));
            $process = $processBuilder->getProcess();
            $process->run();

            if (!$process->isSuccessful()) {
                $errors[] = [
                    'file' => $file,
                    'error' => trim($process->getOutput()),
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
}
