<?php declare(strict_types=1);namespace otra\cache\php\init;use const otra\cache\php\{BASE_PATH,CONSOLE_PATH,CORE_PATH};const CLASSMAP=['otra\config\Routes'=>BASE_PATH.'config/Routes.php','otra\config\AdditionalClassFiles'=>BASE_PATH.'config/AdditionalClassFiles.php','otra\config\AllConfig'=>BASE_PATH.'config/AllConfig.php','otra\cache\php\Logger'=>CORE_PATH.'Logger.php','otra\tools\compression'=>CORE_PATH.'tools/compression.php','otra\tools\workers\Worker'=>CORE_PATH.'tools/workers/Worker.php','otra\tools\workers\WorkerManager'=>CORE_PATH.'tools/workers/WorkerManager.php','otra\tools\debug\DumpMaster'=>CORE_PATH.'tools/debug/DumpMaster.php','otra\tools\debug\dump'=>CORE_PATH.'tools/debug/dump.php','otra\tools\debug\DumpWeb'=>CORE_PATH.'tools/debug/DumpWeb.php','otra\tools\debug\tailCustom'=>CORE_PATH.'tools/debug/tailCustom.php','otra\tools\debug\getCaller'=>CORE_PATH.'tools/debug/getCaller.php','otra\tools\debug\DumpCli'=>CORE_PATH.'tools/debug/DumpCli.php','otra\tools\deleteTree'=>CORE_PATH.'tools/deleteTree.php','otra\tools\cleanFilesAndFolders'=>CORE_PATH.'tools/cleanFilesAndFolders.php','otra\tools\copyFilesAndFolders'=>CORE_PATH.'tools/copyFilesAndFolders.php','otra\tools\reformatSource'=>CORE_PATH.'tools/reformatSource.php','otra\tools\removeFieldProtection'=>CORE_PATH.'tools/removeFieldProtection.php','otra\tools\translate'=>CORE_PATH.'tools/translate.php','otra\tools\files\showTraceLine'=>CORE_PATH.'tools/files/showTraceLine.php','otra\tools\files\returnLegiblePath'=>CORE_PATH.'tools/files/returnLegiblePath.php','otra\tools\getSourceFromFile'=>CORE_PATH.'tools/getSourceFromFile.php','otra\tools\sqlPrettyPrint'=>CORE_PATH.'tools/sqlPrettyPrint.php','otra\tools\cli'=>CORE_PATH.'tools/cli.php','otra\tools\getOtraCommitNumber'=>CORE_PATH.'tools/getOtraCommitNumber.php','otra\internalServerEntryPoint'=>CORE_PATH.'internalServerEntryPoint.php','otra\bdd\Sql'=>CORE_PATH.'bdd/Sql.php','otra\bdd\Pdomysql'=>CORE_PATH.'bdd/Pdomysql.php','otra\controllers\heavyProfiler\TemplateStructureAction'=>CORE_PATH.'controllers/heavyProfiler/TemplateStructureAction.php','otra\controllers\heavyProfiler\CssAction'=>CORE_PATH.'controllers/heavyProfiler/CssAction.php','otra\controllers\profiler\ClearSQLLogsAction'=>CORE_PATH.'controllers/profiler/ClearSQLLogsAction.php','otra\controllers\profiler\IndexAction'=>CORE_PATH.'controllers/profiler/IndexAction.php','otra\controllers\profiler\RefreshSQLLogsAction'=>CORE_PATH.'controllers/profiler/RefreshSQLLogsAction.php','otra\controllers\errors\Error404Action'=>CORE_PATH.'controllers/errors/Error404Action.php','otra\Session'=>CORE_PATH.'Session.php','otra\DevControllerTrait'=>CORE_PATH.'dev/DevControllerTrait.php','otra\web\indexDev'=>CORE_PATH.'init/web/indexDev.php','otra\loadStaticRoute'=>CORE_PATH.'init/web/loadStaticRoute.php','otra\web\index'=>CORE_PATH.'init/web/index.php','otra\Model'=>CORE_PATH.'Model.php','otra\services\ProfilerService'=>CORE_PATH.'services/ProfilerService.php','otra\services\securityService'=>CORE_PATH.'services/securityService.php','otra\Controller'=>CORE_PATH.'Controller.php','otra\console\deployment\genClassMap\genClassMapHelp'=>CONSOLE_PATH.'deployment/genClassMap/genClassMapHelp.php','otra\console\deployment\genClassMap\genClassMapTask'=>CONSOLE_PATH.'deployment/genClassMap/genClassMapTask.php','otra\console\deployment\genJsRouting\genJsRoutingHelp'=>CONSOLE_PATH.'deployment/genJsRouting/genJsRoutingHelp.php','otra\console\deployment\genJsRouting\genJsRoutingTask'=>CONSOLE_PATH.'deployment/genJsRouting/genJsRoutingTask.php','otra\console\deployment\taskFileInit'=>CONSOLE_PATH.'deployment/taskFileInit.php','otra\src\console\deployment\googleClosureCompile'=>CONSOLE_PATH.'deployment/googleClosureCompile.php','otra\console\deployment\genBootstrap\oneBootstrap'=>CONSOLE_PATH.'deployment/genBootstrap/oneBootstrap.php','otra\console\deployment\genBootstrap\taskFileOperation'=>CONSOLE_PATH.'deployment/genBootstrap/taskFileOperation.php','otra\console\deployment\genBootstrap\genBootstrapHelp'=>CONSOLE_PATH.'deployment/genBootstrap/genBootstrapHelp.php','otra\console\deployment\genBootstrap\genBootstrapTask'=>CONSOLE_PATH.'deployment/genBootstrap/genBootstrapTask.php','otra\console\deployment\updateConf\updateConfTask'=>CONSOLE_PATH.'deployment/updateConf/updateConfTask.php','otra\console\deployment\updateConf\updateConfHelp'=>CONSOLE_PATH.'deployment/updateConf/updateConfHelp.php','otra\console\deployment\clearCache\clearCacheHelp'=>CONSOLE_PATH.'deployment/clearCache/clearCacheHelp.php','otra\console\deployment\clearCache\clearCacheTask'=>CONSOLE_PATH.'deployment/clearCache/clearCacheTask.php','otra\console\deployment\buildDev\buildDevTask'=>CONSOLE_PATH.'deployment/buildDev/buildDevTask.php','otra\console\deployment\buildDev\buildDevHelp'=>CONSOLE_PATH.'deployment/buildDev/buildDevHelp.php','otra\console\deployment\deploy\deployHelp'=>CONSOLE_PATH.'deployment/deploy/deployHelp.php','otra\console\deployment\deploy\deployTask'=>CONSOLE_PATH.'deployment/deploy/deployTask.php','otra\console\deployment\genWatcher\sassTools'=>CONSOLE_PATH.'deployment/genWatcher/sassTools.php','otra\console\deployment\genWatcher\genWatcherTask'=>CONSOLE_PATH.'deployment/genWatcher/genWatcherTask.php','otra\console\deployment\genWatcher\genWatcherHelp'=>CONSOLE_PATH.'deployment/genWatcher/genWatcherHelp.php','otra\console\deployment\genServerConfig\genServerConfigHelp'=>CONSOLE_PATH.'deployment/genServerConfig/genServerConfigHelp.php','otra\console\deployment\genServerConfig\apacheServerConfig'=>CONSOLE_PATH.'deployment/genServerConfig/apacheServerConfig.php','otra\console\deployment\genServerConfig\genServerConfigTask'=>CONSOLE_PATH.'deployment/genServerConfig/genServerConfigTask.php','otra\console\deployment\genServerConfig\nginxServerConfig'=>CONSOLE_PATH.'deployment/genServerConfig/nginxServerConfig.php','otra\console\deployment\generateOptimizedJavaScript'=>CONSOLE_PATH.'deployment/generateOptimizedJavaScript.php','otra\console\deployment\genSitemap\genSitemapTask'=>CONSOLE_PATH.'deployment/genSitemap/genSitemapTask.php','otra\console\deployment\genSitemap\genSitemapHelp'=>CONSOLE_PATH.'deployment/genSitemap/genSitemapHelp.php','otra\console\deployment\genAssets\genAssetsHelp'=>CONSOLE_PATH.'deployment/genAssets/genAssetsHelp.php','otra\console\deployment\genAssets\genAsset'=>CONSOLE_PATH.'deployment/genAssets/genAsset.php','otra\console\deployment\genAssets\genAssetsTask'=>CONSOLE_PATH.'deployment/genAssets/genAssetsTask.php','bundles\HelloWorld\frontend\controllers\index\HomeAction'=>CONSOLE_PATH.'architecture/starters/helloWorld/HomeAction.php','otra\console\architecture\createModule\checkModuleExistence'=>CONSOLE_PATH.'architecture/createModule/checkModuleExistence.php','otra\console\architecture\constants\createModuleTask'=>CONSOLE_PATH.'architecture/createModule/createModuleTask.php','otra\console\architecture\createModule\createModuleHelp'=>CONSOLE_PATH.'architecture/createModule/createModuleHelp.php','otra\console\architecture\createModule\createModule'=>CONSOLE_PATH.'architecture/createModule/createModule.php','otra\console\architecture\doWeCreateIt'=>CONSOLE_PATH.'architecture/doWeCreateIt.php','otra\console\architecture\checkBooleanArgument'=>CONSOLE_PATH.'architecture/checkBooleanArgument.php','otra\console\architecture\createGlobalConstants\createGlobalConstantsTask'=>CONSOLE_PATH.'architecture/createGlobalConstants/createGlobalConstantsTask.php','otra\console\architecture\createGlobalConstants\createGlobalConstantsHelp'=>CONSOLE_PATH.'architecture/createGlobalConstants/createGlobalConstantsHelp.php','otra\console\architecture\constants\createHelloWorldTask'=>CONSOLE_PATH.'architecture/createHelloWorld/createHelloWorldTask.php','otra\console\architecture\createHelloWorld\createHelloWorldHelp'=>CONSOLE_PATH.'architecture/createHelloWorld/createHelloWorldHelp.php','otra\console\architecture\init\initTask'=>CONSOLE_PATH.'architecture/init/initTask.php','otra\console\architecture\init\initHelp'=>CONSOLE_PATH.'architecture/init/initHelp.php','otra\console\architecture\createBundle\createBundleHelp'=>CONSOLE_PATH.'architecture/createBundle/createBundleHelp.php','otra\console\architecture\createBundle\checkBundleExistence'=>CONSOLE_PATH.'architecture/createBundle/checkBundleExistence.php','otra\console\architecture\createBundle\createBundle'=>CONSOLE_PATH.'architecture/createBundle/createBundle.php','otra\console\architecture\createBundle\bundleMaskCreation'=>CONSOLE_PATH.'architecture/createBundle/bundleMaskCreation.php','otra\console\architecture\constants\createBundleTask'=>CONSOLE_PATH.'architecture/createBundle/createBundleTask.php','otra\console\architecture\createFolder'=>CONSOLE_PATH.'architecture/createFolder.php','otra\console\architecture\constants\createControllerTask'=>CONSOLE_PATH.'architecture/createController/createControllerTask.php','otra\console\architecture\createController\checkControllerExistence'=>CONSOLE_PATH.'architecture/createController/checkControllerExistence.php','otra\console\architecture\createController\createControllerHelp'=>CONSOLE_PATH.'architecture/createController/createControllerHelp.php','otra\console\architecture\createModel\createModelHelp'=>CONSOLE_PATH.'architecture/createModel/createModelHelp.php','otra\console\architecture\createModel\common'=>CONSOLE_PATH.'architecture/createModel/common.php','otra\console\architecture\createModel\oneModelFromNothing\common'=>CONSOLE_PATH.'architecture/createModel/oneModelFromNothing/common.php','otra\console\architecture\createModel\oneModelFromNothing\interactive'=>CONSOLE_PATH.'architecture/createModel/oneModelFromNothing/interactive.php','otra\console\architecture\createModel\oneModelFromNothing\notInteractive'=>CONSOLE_PATH.'architecture/createModel/oneModelFromNothing/notInteractive.php','otra\console\architecture\createModel\createModel'=>CONSOLE_PATH.'architecture/createModel/createModel.php','otra\console\architecture\createModel\interactive'=>CONSOLE_PATH.'architecture/createModel/checkParameters/interactive.php','otra\console\architecture\createModel\notInteractive'=>CONSOLE_PATH.'architecture/createModel/checkParameters/notInteractive.php','otra\console\architecture\constants\createModelTask'=>CONSOLE_PATH.'architecture/createModel/createModelTask.php','otra\console\architecture\createModel\allModelsFromYmlSchema\common'=>CONSOLE_PATH.'architecture/createModel/allModelsFromYmlSchema/common.php','otra\console\architecture\createModel\allModelsFromYmlSchema\interactive'=>CONSOLE_PATH.'architecture/createModel/allModelsFromYmlSchema/interactive.php','otra\console\architecture\createModel\allModelsFromYmlSchema\notInteractive'=>CONSOLE_PATH.'architecture/createModel/allModelsFromYmlSchema/notInteractive.php','otra\console\architecture\createModel\oneModelFromYmlSchema\common'=>CONSOLE_PATH.'architecture/createModel/oneModelFromYmlSchema/common.php','otra\console\architecture\createModel\oneModelFromYmlSchema\interactive'=>CONSOLE_PATH.'architecture/createModel/oneModelFromYmlSchema/interactive.php','otra\console\architecture\createModel\oneModelFromYmlSchema\notInteractive'=>CONSOLE_PATH.'architecture/createModel/oneModelFromYmlSchema/notInteractive.php','otra\console\architecture\createAction\createActionHelp'=>CONSOLE_PATH.'architecture/createAction/createActionHelp.php','otra\console\architecture\createAction'=>CONSOLE_PATH.'architecture/createAction/createAction.php','otra\console\architecture\constants\createActionTask'=>CONSOLE_PATH.'architecture/createAction/createActionTask.php','src\console\helpAndTools\convertImages\convertImagesTask'=>CONSOLE_PATH.'helpAndTools/convertImages/convertImagesTask.php','otra\console\helpAndTools\convertImages\convertImagesHelp'=>CONSOLE_PATH.'helpAndTools/convertImages/convertImagesHelp.php','otra\console\helpAndTools\generateTaskMetadata\generateTaskMetadataHelp'=>CONSOLE_PATH.'helpAndTools/generateTaskMetadata/generateTaskMetadataHelp.php','otra\cache\php\generateTaskMetadataTask'=>CONSOLE_PATH.'helpAndTools/generateTaskMetadata/generateTaskMetadataTask.php','otra\console\helpAndTools\version\versionTask'=>CONSOLE_PATH.'helpAndTools/version/versionTask.php','otra\console\helpAndTools\version\versionHelp'=>CONSOLE_PATH.'helpAndTools/version/versionHelp.php','otra\console\helpAndTools\routes\routesHelp'=>CONSOLE_PATH.'helpAndTools/routes/routesHelp.php','otra\console\helpAndTools\routes\routesTask'=>CONSOLE_PATH.'helpAndTools/routes/routesTask.php','otra\console\helpAndTools\requirements\requirementsTask'=>CONSOLE_PATH.'helpAndTools/requirements/requirementsTask.php','otra\console\helpAndTools\requirements\requirementsHelp'=>CONSOLE_PATH.'helpAndTools/requirements/requirementsHelp.php','otra\console\helpAndTools\crypt\cryptHelp'=>CONSOLE_PATH.'helpAndTools/crypt/cryptHelp.php','otra\console\helpAndTools\crypt\cryptTask'=>CONSOLE_PATH.'helpAndTools/crypt/cryptTask.php','otra\console\helpAndTools\serve\serveHelp'=>CONSOLE_PATH.'helpAndTools/serve/serveHelp.php','otra\console\helpAndTools\serve\serveTask'=>CONSOLE_PATH.'helpAndTools/serve/serveTask.php','otra\console\helpAndTools\help\helpHelp'=>CONSOLE_PATH.'helpAndTools/help/helpHelp.php','otra\console\helpAndTools\help\helpTask'=>CONSOLE_PATH.'helpAndTools/help/helpTask.php','otra\console\helpAndTools\checkConfiguration\checkConfigurationHelp'=>CONSOLE_PATH.'helpAndTools/checkConfiguration/checkConfigurationHelp.php','otra\console\helpAndTools\checkConfiguration\checkConfigurationTask'=>CONSOLE_PATH.'helpAndTools/checkConfiguration/checkConfigurationTask.php','otra\console\helpAndTools\hash\hashTask'=>CONSOLE_PATH.'helpAndTools/hash/hashTask.php','otra\console\helpAndTools\hash\hashHelp'=>CONSOLE_PATH.'helpAndTools/hash/hashHelp.php','otra\console\OtraExceptionCli'=>CONSOLE_PATH.'OtraExceptionCli.php','otra\console\database\sqlExecute\sqlExecuteHelp'=>CONSOLE_PATH.'database/sqlExecute/sqlExecuteHelp.php','otra\console\database\sqlExecute\sqlExecuteTask'=>CONSOLE_PATH.'database/sqlExecute/sqlExecuteTask.php','otra\console\database\Database'=>CONSOLE_PATH.'database/Database.php','otra\console\database\sqlCreateFixtures\sqlCreateFixturesHelp'=>CONSOLE_PATH.'database/sqlCreateFixtures/sqlCreateFixturesHelp.php','otra\console\database\sqlCreateFixtures\sqlCreateFixturesTask'=>CONSOLE_PATH.'database/sqlCreateFixtures/sqlCreateFixturesTask.php','otra\console\database\sqlImportSchema\sqlImportSchemaHelp'=>CONSOLE_PATH.'database/sqlImportSchema/sqlImportSchemaHelp.php','otra\console\database\sqlImportSchema\sqlImportSchemaTask'=>CONSOLE_PATH.'database/sqlImportSchema/sqlImportSchemaTask.php','otra\console\database\sqlCreateDatabase\sqlCreateDatabaseTask'=>CONSOLE_PATH.'database/sqlCreateDatabase/sqlCreateDatabaseTask.php','otra\console\database\sqlCreateDatabase\sqlCreateDatabaseHelp'=>CONSOLE_PATH.'database/sqlCreateDatabase/sqlCreateDatabaseHelp.php','otra\console\database\sqlClean\sqlCleanHelp'=>CONSOLE_PATH.'database/sqlClean/sqlCleanHelp.php','otra\console\database\sqlClean\sqlCleanTask'=>CONSOLE_PATH.'database/sqlClean/sqlCleanTask.php','otra\console\database\sqlImportFixtures\sqlImportFixturesHelp'=>CONSOLE_PATH.'database/sqlImportFixtures/sqlImportFixturesHelp.php','otra\console\database\sqlImportFixtures\sqlImportFixturesTask'=>CONSOLE_PATH.'database/sqlImportFixtures/sqlImportFixturesTask.php','otra\console\constants\tools'=>CONSOLE_PATH.'tools.php','otra\console\launchTask'=>CONSOLE_PATH.'launchTask.php','otra\console\TasksManager'=>CONSOLE_PATH.'TasksManager.php','otra\console\launchTaskPosixWay'=>CONSOLE_PATH.'launchTaskPosixWay.php','otra\templating\visualRendering'=>CORE_PATH.'views/heavyProfiler/templateStructure/visualRendering.php','otra\MasterController'=>CORE_PATH.'MasterController.php','otra\ProdControllerTrait'=>CORE_PATH.'prod/ProdControllerTrait.php','otra\Router'=>CORE_PATH.'Router.php','otra\cache\php\blocks'=>CORE_PATH.'templating/blocks.php','otra\OtraException'=>CORE_PATH.'OtraException.php','Composer\InstalledVersions'=>BASE_PATH.'vendor/composer/InstalledVersions.php','Composer\Autoload\ClassLoader'=>BASE_PATH.'vendor/composer/ClassLoader.php','Composer\Autoload\autoload_static'=>BASE_PATH.'vendor/composer/autoload_static.php','Symfony\Component\Yaml\Command\LintCommand'=>BASE_PATH.'vendor/symfony/yaml/Command/LintCommand.php','Symfony\Component\Yaml\Tag\TaggedValue'=>BASE_PATH.'vendor/symfony/yaml/Tag/TaggedValue.php','Symfony\Component\Yaml\Inline'=>BASE_PATH.'vendor/symfony/yaml/Inline.php','Symfony\Component\Yaml\Escaper'=>BASE_PATH.'vendor/symfony/yaml/Escaper.php','Symfony\Component\Yaml\Dumper'=>BASE_PATH.'vendor/symfony/yaml/Dumper.php','Symfony\Component\Yaml\Tests\Command\LintCommandTest'=>BASE_PATH.'vendor/symfony/yaml/Tests/Command/LintCommandTest.php','Symfony\Component\Yaml\Tests\YamlTest'=>BASE_PATH.'vendor/symfony/yaml/Tests/YamlTest.php','Symfony\Component\Yaml\Tests\InlineTest'=>BASE_PATH.'vendor/symfony/yaml/Tests/InlineTest.php','Symfony\Component\Yaml\Tests\DumperTest'=>BASE_PATH.'vendor/symfony/yaml/Tests/DumperTest.php','Symfony\Component\Yaml\Tests\ParserTest'=>BASE_PATH.'vendor/symfony/yaml/Tests/ParserTest.php','Symfony\Component\Yaml\Tests\ParseExceptionTest'=>BASE_PATH.'vendor/symfony/yaml/Tests/ParseExceptionTest.php','Symfony\Component\Yaml\Unescaper'=>BASE_PATH.'vendor/symfony/yaml/Unescaper.php','Symfony\Component\Yaml\Yaml'=>BASE_PATH.'vendor/symfony/yaml/Yaml.php','Symfony\Component\Yaml\Parser'=>BASE_PATH.'vendor/symfony/yaml/Parser.php','Symfony\Component\Yaml\Exception\ExceptionInterface'=>BASE_PATH.'vendor/symfony/yaml/Exception/ExceptionInterface.php','Symfony\Component\Yaml\Exception\ParseException'=>BASE_PATH.'vendor/symfony/yaml/Exception/ParseException.php','Symfony\Component\Yaml\Exception\RuntimeException'=>BASE_PATH.'vendor/symfony/yaml/Exception/RuntimeException.php','Symfony\Component\Yaml\Exception\DumpException'=>BASE_PATH.'vendor/symfony/yaml/Exception/DumpException.php','Symfony\Polyfill\Ctype\Ctype'=>BASE_PATH.'vendor/symfony/polyfill-ctype/Ctype.php','config\AllConfig'=>BASE_PATH.'config/AllConfig.php'];
