[Home](../README.md) / [Console](../console.md) / Generating all you needed

Previous section : [Generating all you needed](codeGeneration.md)

### Your own console tasks
## Configuration
First, configure where you will put your OTRA tasks in the file `config/dev/AllConfig.php`. Example :

```php
public static array $taskFolders = [
  BASE_PATH . 'bundles/tasks/'
];
```
## Creating the files
Then to make work your task, you will need two files. The first must end with `Task.php`, the second with `Help.php`.
For example, `EchoTask.php` and `EchoHelp.php`.\
You can organize your tasks as you like regarding folders.

To create the task file, you can put whatever you like in it.\
To create the help file, you must follow this kind of structure :
```php
<?php
declare(strict_types=1);

use otra\console\TasksManager;

return [
  'This is the task description.',
  [
    'firstParameter' => 'The first parameter description.',
    'secondParameter' => 'The second parameter description'   
  ],
  [
    TasksManager::REQUIRED_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER
  ],
  'My task category'
];
```

Do not hesitate to look at OTRA task files to know how to handle other things.\
Colors can be used thanks to the defined
constants in `console/colors.php`.
