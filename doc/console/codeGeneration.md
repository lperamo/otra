[Home](../README.md) / [Console](../console.md) / Generating all you needed

## Generating all you needed
### Updating routes
Once you have configured your application, don't forget to do :
```bash
php console.php upConf
```

It will optimize your configuration by generating an aggregated minified configuration file.

### Generating the class mapping

You can generate a class mapping via this command :

```bash
php console.php genClassMap
```

You can pass 1 as parameter to directly show the classes of the class mapping. <br>
This command can be used via a file watcher when adding/modifying a file.

### Optimizing your server code for production

You can generate optimized versions of your routes (php code mostly) like this :

```bash
php console.php genBootstrap
```

### Generating assets for production

You can generate optimized versions of your assets ... :

```bash
php console.php genAssets
```

Next section : [Your own console tasks](yourOwnConsoleTasks.md)
