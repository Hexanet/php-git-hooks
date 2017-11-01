<?php

namespace Hexanet\PhpGitHooks\Composer;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;

class InstallHooksScript
{
    public static function installHooks(Event $event)
    {
        $fs = new Filesystem();

        $hookPath = '.git/hooks/pre-commit';

        if ($fs->exists($hookPath)) {
            $fs->remove([$hookPath]);
        }

        $binDir = $event->getComposer()->getConfig()->get('bin-dir');

        $hookFile = <<<'HOOK'
#!/bin/sh

exec %s/php-git-hooks pre-commit
HOOK;

        $fs->dumpFile($hookPath, sprintf($hookFile, $binDir));
        $fs->chmod($hookPath, 0775);
    }
}
