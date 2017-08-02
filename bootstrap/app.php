<?php

use Hexanet\PhpGitHooks\Command\PreCommitCommand;

$app = new Silly\Edition\PhpDi\Application('PHP Git Hooks', '1.0.0');

$container = $app->getContainer();
$container->set('projectPath', $projectPath);

$app->command('pre-commit', PreCommitCommand::class);

return $app;