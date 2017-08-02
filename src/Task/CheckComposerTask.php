<?php

namespace Hexanet\PhpGitHooks\Task;

use Symfony\Component\Console\Style\SymfonyStyle;

class CheckComposerTask
{
    /**
     * @param SymfonyStyle $io
     * @param array        $files
     *
     * @return bool
     */
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

        $io->section('Vérification du composer.lock');

        if ($composerJsonDetected && !$composerLockDetected) {
            $io->error('Le fichier composer.lock doit être commité lorsque le fichier composer.json est modifié');

            return false;
        }

        $io->comment('Fichier composer.json valide');

        return true;
    }
}
