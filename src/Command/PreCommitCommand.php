<?php

namespace Hexanet\PhpGitHooks\Command;

use Hexanet\PhpGitHooks\Task\CheckComposerTask;
use Hexanet\PhpGitHooks\Task\LintTask;
use Hexanet\PhpGitHooks\Task\PhpCsFixerTask;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PreCommitCommand
{
    /**
     * @param string          $projectPath
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function __invoke(string $projectPath, InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('PHP Git Hooks');

        $files = $this->extractCommitedFiles();

        if (!$files) {
            $io->comment('No files to check');

            return 0;
        }

        $io->comment(sprintf(
            '%d files to check',
            count($files)
        ));

        if (!(new CheckComposerTask())->check($io, $files)) {
            return 1;
        }

        if (!(new LintTask())->check($io, $files)) {
            return 1;
        }

        if (!(new PhpCsFixerTask($projectPath))->check($io, $files)) {
            return 1;
        }
    }

    /**
     * @return array
     */
    private function extractCommitedFiles() : array
    {
        $output = [];
        $rc = 0;

        exec('git rev-parse --verify HEAD 2> /dev/null', $output, $rc);

        $against = '4b825dc642cb6eb9a060e54bf8d69288fbee4904';
        if ($rc == 0) {
            $against = 'HEAD';
        }

        $output = [];
        exec("git diff-index --cached --name-status $against | egrep '^(A|M)' | awk '{print $2;}'", $output);

        return $output;
    }
}
