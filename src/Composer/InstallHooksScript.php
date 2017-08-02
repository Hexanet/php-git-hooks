<?php

namespace Hexanet\PhpGitHooks\Composer;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;

class InstallHooksScript
{
    public static function installHooks(Event $event)
    {
        $fs = new Filesystem();

        $hook = 'pre-commit';
        $hookPath = '.git/hooks/'.$hook;

        if ($fs->exists($hookPath)) {
            $fs->remove([$hookPath]);
        }

        $hookFile = <<<'HOOK'
#!/bin/sh

exec ./bin/php-git-hooks pre-commit
HOOK;

        $fs->dumpFile($hookPath, $hookFile);
        $fs->chmod($hookPath, 0775);
    }
}
