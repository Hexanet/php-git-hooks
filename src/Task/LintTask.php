<?php

namespace Hexanet\PhpGitHooks\Task;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class LintTask
{
    public function check(SymfonyStyle $io, array $files) : bool
    {
        $io->section('Checking the syntax of PHP files');

        $phpLintResult = $this->checkWithPhpLint($files);

        if (!$phpLintResult['result']) {
            $io->error('Syntax errors detected');
            $io->table(['File', 'Error'], $phpLintResult['errors']);

            return false;
        }

        $io->comment('No syntax error detected');

        return true;
    }

    private function checkWithPhpLint($files) : array
    {
        $needle = '/(\.php)|(\.inc)$/';

        $succeed = true;
        $errors = [];

        foreach ($files as $file) {
            if (!preg_match($needle, $file)) {
                continue;
            }

            $process = new Process(['php', '-l', $file]);
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
