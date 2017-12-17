<?php

namespace Hexanet\PhpGitHooks\Task;

use Symfony\Component\Console\Style\SymfonyStyle;

class CheckComposerTask
{
    public function check(SymfonyStyle $io, array $files) : bool
    {
        $composerJsonDetected = false;
        $composerLockDetected = false;

        foreach ($files as $file) {
            if ($file === 'composer.json') {
                $composerJsonDetected = true;
            }

            if ($file === 'composer.lock') {
                $composerLockDetected = true;
            }
        }

        if (!$composerJsonDetected) {
            return true;
        }

        $io->section('Checking composer.lock');

        if ($composerJsonDetected && !$composerLockDetected) {
            $io->error('The composer.lock file must be committed when the composer.json file is modified');

            return false;
        }

        $io->comment('composer.json is valid');

        return true;
    }
}
