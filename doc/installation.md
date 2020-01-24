[Home](../README.md) / Installation
                                
### Installation

You can install OTRA via Composer.

OTRA has its own system to load classes so we can skip autoloader generation but not, at the time of writing, for the
`require` command.
 
```bash
composer require otra/otra:dev-develop
```

If you wish to remove useless autoloader files, you can still remove the vendor folder afterwards and do this command :
```bash
composer update otra/otra --no-autoloader
```
