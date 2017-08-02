# PHP Git Hooks

Features :

* Check if composer.lock is synchronized with composer.json
* Lint
* Check and fix coding styles with PHP-CS-FIXER

## Installation

```
composer require hexanet/php-git-hooks:dev-master
```


## Usage

composer.json :

```php
    "scripts": {
        "pre-update-cmd": "Hexanet\\PhpGitHooks\\Composer\\InstallHooksScript::installHooks",
        "pre-install-cmd": "Hexanet\\PhpGitHooks\\Composer\\InstallHooksScript::installHooks"
    }
```
