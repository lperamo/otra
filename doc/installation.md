[Home](../README.md) / Installation
                                
### Installation

You can install OTRA via Composer.

OTRA has its own system to load classes so we can skip autoloader generation.

At the time of writing, we cannot do this with the `require` command so we first disable the update and then we use the
 `update` command with the `--no-autoloader` parameter.
 
```bash
composer create-project otra/skeleton --remove-vcs yourProjectFolderName
```
