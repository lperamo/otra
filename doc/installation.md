[Home](../README.md) / Installation
                                
### Installation

You can install OTRA via Composer.

OTRA has its own system to load classes, so we can skip autoloader generation.

At the time of writing, we cannot do this with the `require` command, so we first disable the update, and then we use the
 `update` command with the `--no-autoloader` parameter.
 
```bash
composer create-project otra/skeleton --remove-vcs yourProjectFolderName
```

To get the very last version of OTRA in development, you have to type right after the first command has finished :
```bash
composer update otra/otra:dev-develop
```
