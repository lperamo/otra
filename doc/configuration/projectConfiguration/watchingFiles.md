[Home](../../../README.md) / [Installation](../../configuration.md) /
[Project configuration](../projectConfiguration.md) / Watching files

Previous section : [Routes configuration file](routesConfiguration.md)

#### Watching files

#### On unix systems
You can initiate a watcher to listening for class mapping, typescript files, scss files and route configuration updates
by typing this command :

```bash
php console.php genWatcher
```

This command only work on unix systems because it uses the _inotify_ command.

Do not hesitate to look the [following section](synchronizationTips.md) to improve synchronization
 speed if needed.
 
As usual, you can filter what you want to watch with parameters, see the command help for more 
 information.

#### On Windows
If you use PHPStorm, I have put some things to help you to configure file watchers in the folder `ideConfiguration` like 
scopes and file watchers presets.

For the scopes, you need to copy the folder `scopes` in your related `.idea` folder.

For the file watchers, you can import them via _File > Settings > Tools > File Watchers > Import_.

Next section : [Tips for faster synchronization](synchronizationTips.md)
