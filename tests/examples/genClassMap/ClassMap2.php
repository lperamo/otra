<?php declare(strict_types=1);namespace otra\cache\php;use const otra\cache\php\{BASE_PATH,CONSOLE_PATH,CORE_PATH};const CLASSMAP2=['otra\config\AdditionalClassFiles'=>BASE_PATH.'config/AdditionalClassFiles.php','otra\config\AllConfig'=>BASE_PATH.'config/AllConfig.php','otra\config\Routes'=>BASE_PATH.'config/Routes.php','otra\bdd\Pdomysql'=>CORE_PATH.'bdd/Pdomysql.php','otra\bdd\Sql'=>CORE_PATH.'bdd/Sql.php','otra\console\database\Database'=>CONSOLE_PATH.'database/Database.php','otra\console\OtraExceptionCli'=>CONSOLE_PATH.'OtraExceptionCli.php','otra\console\TasksManager'=>CONSOLE_PATH.'TasksManager.php','otra\Controller'=>CORE_PATH.'Controller.php','otra\controllers\errors\Error404Action'=>CORE_PATH.'controllers/errors/Error404Action.php','otra\controllers\profiler\ClearSQLLogsAction'=>CORE_PATH.'controllers/profiler/ClearSQLLogsAction.php','otra\controllers\profiler\CssAction'=>CORE_PATH.'controllers/profiler/CssAction.php','otra\controllers\profiler\LogsAction'=>CORE_PATH.'controllers/profiler/LogsAction.php','otra\controllers\profiler\RefreshSQLLogsAction'=>CORE_PATH.'controllers/profiler/RefreshSQLLogsAction.php','otra\controllers\profiler\RequestsAction'=>CORE_PATH.'controllers/profiler/RequestsAction.php','otra\controllers\profiler\RoutesAction'=>CORE_PATH.'controllers/profiler/RoutesAction.php','otra\controllers\profiler\SqlAction'=>CORE_PATH.'controllers/profiler/SqlAction.php','otra\controllers\profiler\TemplateStructureAction'=>CORE_PATH.'controllers/profiler/TemplateStructureAction.php','otra\DevControllerTrait'=>CORE_PATH.'dev/DevControllerTrait.php','otra\cache\php\Logger'=>CORE_PATH.'Logger.php','otra\MasterController'=>CORE_PATH.'MasterController.php','otra\Model'=>CORE_PATH.'Model.php','otra\OtraException'=>CORE_PATH.'OtraException.php','otra\ProdControllerTrait'=>CORE_PATH.'prod/ProdControllerTrait.php','otra\Router'=>CORE_PATH.'Router.php','otra\services\ProfilerService'=>CORE_PATH.'services/ProfilerService.php','otra\Session'=>CORE_PATH.'Session.php','otra\templating\HtmlMinifier'=>CORE_PATH.'templating/HtmlMinifier.php','otra\tools\debug\DumpCli'=>CORE_PATH.'tools/debug/DumpCli.php','otra\tools\debug\DumpMaster'=>CORE_PATH.'tools/debug/DumpMaster.php','otra\tools\debug\DumpWeb'=>CORE_PATH.'tools/debug/DumpWeb.php','otra\tools\debug\FakeDateTime'=>CORE_PATH.'tools/debug/FakeDateTime.php','otra\tools\workers\Worker'=>CORE_PATH.'tools/workers/Worker.php','otra\tools\workers\WorkerManager'=>CORE_PATH.'tools/workers/WorkerManager.php','otra\config\AllConfigBadDriver'=>BASE_PATH.'tests/config/AllConfigBadDriver.php','otra\config\AllConfigGood'=>BASE_PATH.'tests/config/AllConfigGood.php','otra\config\AllConfigNoDefaultConnection'=>BASE_PATH.'tests/config/AllConfigNoDefaultConnection.php','otra\config\AllConfigNoDeploymentConfiguration'=>BASE_PATH.'tests/config/AllConfigNoDeploymentConfiguration.php','otra\config\AllConfigNoDeploymentDomainName'=>BASE_PATH.'tests/config/AllConfigNoDeploymentDomainName.php','otra\config\AllConfigNoDeploymentFolder'=>BASE_PATH.'tests/config/AllConfigNoDeploymentFolder.php','otra\config\AllConfigTest'=>BASE_PATH.'tests/config/AllConfigTest.php','otra\tests\ConsoleTest'=>BASE_PATH.'tests/ConsoleTest.php','bundles\Test\test\controllers\test\Action'=>BASE_PATH.'tests/examples/createAction/Action.php','bundles\Test\config\routes\Routes'=>BASE_PATH.'tests/examples/createAction/Routes.php','examples\deployment\BackupFileToCompress'=>BASE_PATH.'tests/examples/deployment/BackupFileToCompress.php','examples\deployment\FileToCompress'=>BASE_PATH.'tests/examples/deployment/FileToCompress.php','examples\deployment\fixFiles\input\TestRequireAllConfig'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireAllConfig.php','examples\deployment\fixFiles\input\TestRequireMasterController'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireMasterController.php','examples\deployment\fixFiles\input\Test2'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/Test2.php','examples\deployment\fixFiles\input\TestDynamicRequire'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestDynamicRequire.php','examples\deployment\fixFiles\input\TestDynamicRequireKnownConstant'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestDynamicRequireKnownConstant.php','examples\deployment\fixFiles\input\TestDynamicRequireSimpleVariable'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestDynamicRequireSimpleVariable.php','examples\deployment\fixFiles\input\TestExtends'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestExtends.php','examples\deployment\fixFiles\input\TestExtendsWithoutUse'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestExtendsWithoutUse.php','examples\deployment\fixFiles\input\TestInclude'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestInclude.php','examples\deployment\fixFiles\input\TestIncludeOnce'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestIncludeOnce.php','examples\deployment\fixFiles\input\TestInlineThreeUse'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestInlineThreeUse.php','examples\deployment\fixFiles\input\TestInlineTwoUse'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestInlineTwoUse.php','examples\deployment\fixFiles\input\TestMinified'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestMinified.php','examples\deployment\fixFiles\input\TestMultipleRequire'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestMultipleRequire.php','examples\deployment\fixFiles\input\TestNestedRequire'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestNestedRequire.php','examples\deployment\fixFiles\input\TestRequire'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequire.php','examples\deployment\fixFiles\input\TestRequireArrayConstants'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireArrayConstants.php','examples\deployment\fixFiles\input\TestRequireClassNameConflict'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireClassNameConflict.php','examples\deployment\fixFiles\input\TestRequireCommented'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireCommented.php','examples\deployment\fixFiles\input\TestRequireCommentedNoSpace'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireCommentedNoSpace.php','examples\deployment\fixFiles\input\TestRequireComplex'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireComplex.php','examples\deployment\fixFiles\input\TestRequireComplexClass'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireComplexClass.php','examples\deployment\fixFiles\input\TestRequireComplexUseCase'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireComplexUseCase.php','examples\deployment\fixFiles\input\TestRequireConditionalFunctions'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireConditionalFunctions.php','examples\deployment\fixFiles\input\TestRequireConstantDuplications'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireConstantDuplications.php','examples\deployment\fixFiles\input\TestRequireDir'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireDir.php','examples\deployment\fixFiles\input\TestRequireFunctionContainsBraces'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireFunctionContainsBraces.php','examples\deployment\fixFiles\input\TestRequireFunctionInNamespace'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireFunctionInNamespace.php','examples\deployment\fixFiles\input\TestRequireFunctionWithUse'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireFunctionWithUse.php','examples\deployment\fixFiles\input\TestRequireInFunction'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireInFunction.php','examples\deployment\fixFiles\input\TestRequireNamespace'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireNamespace.php','examples\deployment\fixFiles\input\TestRequireNamespaceTwoBlocks'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireNamespaceTwoBlocks.php','examples\deployment\fixFiles\input\TestRequireOnce'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireOnce.php','examples\deployment\fixFiles\input\TestRequirePhpConst'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequirePhpConst.php','examples\deployment\fixFiles\input\TestRequirePhpFullExample'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequirePhpFullExample.php','examples\deployment\fixFiles\input\TestRequirePhpFullHtml'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequirePhpFullHtml.php','examples\deployment\fixFiles\input\TestRequirePhpHtmlAndPhp'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequirePhpHtmlAndPhp.php','examples\deployment\fixFiles\input\TestRequirePhpPhpAndHtml'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequirePhpPhpAndHtml.php','examples\deployment\fixFiles\input\TestRequirePhpShortTag'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequirePhpShortTag.php','examples\deployment\fixFiles\input\TestRequireRandom'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireRandom.php','examples\deployment\fixFiles\input\TestRequireReturn'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireReturn.php','examples\deployment\fixFiles\input\TestRequireReturnInFunction'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireReturnInFunction.php','examples\deployment\fixFiles\input\TestRequireTemplate'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireTemplate.php','examples\deployment\fixFiles\input\TestRequireTemplateHtmlAndPhp'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireTemplateHtmlAndPhp.php','examples\deployment\fixFiles\input\TestRequireTemplatePhpAndHtml'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireTemplatePhpAndHtml.php','examples\deployment\fixFiles\input\TestRequireUseConst'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireUseConst.php','examples\deployment\fixFiles\input\TestRequireVendor'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestRequireVendor.php','examples\deployment\fixFiles\input\TestSimpleUse'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestSimpleUse.php','examples\deployment\fixFiles\input\TestStaticCall'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestStaticCall.php','examples\deployment\fixFiles\input\TestUseConst'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestUseConst.php','examples\deployment\fixFiles\input\TestUseConstMultipleRequire'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestUseConstMultipleRequire.php','examples\deployment\fixFiles\input\TestUseFunction'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestUseFunction.php','examples\deployment\fixFiles\input\TestUseInComment'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestUseInComment.php','examples\deployment\fixFiles\input\TestUseInOrAfterComments'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestUseInOrAfterComments.php','examples\deployment\fixFiles\input\TestUseNativeClass'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestUseNativeClass.php','examples\deployment\fixFiles\input\TestUseTrait'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestUseTrait.php','examples\deployment\fixFiles\input\TestVendorUse'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/TestVendorUse.php','tests\examples\deployment\fixFiles\input\vendor\Config'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/vendor/Config.php','examples\deployment\fixFiles\input\vendor\Test'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/vendor/Test.php','examples\deployment\fixFiles\input\vendor\Test2'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/vendor/Test2.php','examples\deployment\fixFiles\input\vendor\Test3'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/vendor/Test3.php','examples\deployment\fixFiles\input\vendor\Test4'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/vendor/Test4.php','examples\deployment\fixFiles\input\vendor\Test5'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/vendor/Test5.php','examples\deployment\fixFiles\input\vendor\TestRequireClassNameConflict'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/vendor/TestRequireClassNameConflict.php','examples\deployment\fixFiles\input\vendor\TestReturn'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/vendor/TestReturn.php','examples\deployment\fixFiles\input\vendor\TestReturnInFunction'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/vendor/TestReturnInFunction.php','examples\deployment\fixFiles\input\vendor\TestStatic'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/vendor/TestStatic.php','examples\deployment\fixFiles\input\vendor\TestTrait'=>BASE_PATH.'tests/examples/deployment/fixFiles/input/vendor/TestTrait.php','otra\cache\php\OutputRequireClassNameConflict'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireClassNameConflict.php','otra\cache\php\OutputRequireNamespace'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireNamespace.php','otra\cache\php\OutputDynamicRequire'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputDynamicRequire.php','otra\cache\php\OutputDynamicRequireKnownConstant'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputDynamicRequireKnownConstant.php','otra\cache\php\OutputDynamicRequireSimpleVariable'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputDynamicRequireSimpleVariable.php','otra\cache\php\OutputExtends'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputExtends.php','otra\cache\php\OutputExtendsWithoutUse'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputExtendsWithoutUse.php','otra\cache\php\OutputInclude'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputInclude.php','otra\cache\php\OutputIncludeOnce'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputIncludeOnce.php','otra\cache\php\OutputInlineThreeUse'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputInlineThreeUse.php','otra\cache\php\OutputInlineTwoUse'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputInlineTwoUse.php','otra\cache\php\OutputMinified'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputMinified.php','otra\cache\php\OutputMultipleRequire'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputMultipleRequire.php','otra\cache\php\OutputNestedRequire'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputNestedRequire.php','otra\cache\php\OutputRequire'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequire.php','otra\cache\php\OutputRequireArrayConstants'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireArrayConstants.php','otra\cache\php\OutputRequireCommented'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireCommented.php','otra\cache\php\OutputRequireCommentedNoSpace'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireCommentedNoSpace.php','otra\cache\php\OutputRequireComplex'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireComplex.php','otra\cache\php\OutputRequireComplexClass'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireComplexClass.php','otra\cache\php\OutputRequireComplexUseCase'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireComplexUseCase.php','otra\cache\php\OutputRequireConditionalFunctions'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireConditionalFunctions.php','otra\cache\php\OutputRequireConstantDuplications'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireConstantDuplications.php','otra\cache\php\OutputRequireDir'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireDir.php','otra\cache\php\OutputRequireFunctionContainsBraces'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireFunctionContainsBraces.php','otra\cache\php\OutputRequireFunctionInNamespace'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireFunctionInNamespace.php','otra\cache\php\OutputRequireFunctionWithUse'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireFunctionWithUse.php','otra\cache\php\OutputRequireInFunction'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireInFunction.php','otra\cache\php\OutputRequireMasterController'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireMasterController.php','otra\cache\php\OutputRequireNamespaceTwoBlocks'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireNamespaceTwoBlocks.php','otra\cache\php\OutputRequireOnce'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireOnce.php','otra\cache\php\OutputRequirePhpConst'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequirePhpConst.php','otra\cache\php\OutputRequirePhpFullExample'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequirePhpFullExample.php','otra\cache\php\OutputRequirePhpFullHtml'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequirePhpFullHtml.php','otra\cache\php\OutputRequirePhpHtmlAndPhp'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequirePhpHtmlAndPhp.php','otra\cache\php\OutputRequirePhpPhpAndHtml'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequirePhpPhpAndHtml.php','otra\cache\php\OutputRequirePhpShortTag'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequirePhpShortTag.php','otra\cache\php\OutputRequireRandom'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireRandom.php','otra\cache\php\OutputRequireReplaceableVariables'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireReplaceableVariables.php','otra\cache\php\OutputRequireReturn'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireReturn.php','otra\cache\php\OutputRequireReturnInFunction'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireReturnInFunction.php','otra\cache\php\OutputRequireTemplate'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireTemplate.php','otra\cache\php\OutputRequireTemplateHtmlAndPhp'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireTemplateHtmlAndPhp.php','otra\cache\php\OutputRequireTemplatePhpAndHtml'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireTemplatePhpAndHtml.php','otra\cache\php\OutputRequireUseConst'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireUseConst.php','otra\cache\php\OutputRequireVendor'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputRequireVendor.php','otra\cache\php\OutputSimpleUse'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputSimpleUse.php','otra\cache\php\OutputStaticCall'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputStaticCall.php','otra\cache\php\OutputUseConst'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputUseConst.php','otra\cache\php\OutputUseConstMultipleRequire'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputUseConstMultipleRequire.php','otra\cache\php\OutputUseFunction'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputUseFunction.php','otra\cache\php\OutputUseInComment'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputUseInComment.php','otra\cache\php\OutputUseInOrAfterComments'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputUseInOrAfterComments.php','otra\cache\php\OutputUseNativeClass'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputUseNativeClass.php','otra\cache\php\OutputUseTrait'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputUseTrait.php','otra\cache\php\OutputVendorUse'=>BASE_PATH.'tests/examples/deployment/fixFiles/output/OutputVendorUse.php','otra\TestExtendsController'=>BASE_PATH.'tests/examples/deployment/TestExtendsController.php','src\console\architecture\CheckBooleanArgumentTest'=>BASE_PATH.'tests/src/console/architecture/CheckBooleanArgumentTest.php','src\console\architecture\createAction\CreateActionHelpTest'=>BASE_PATH.'tests/src/console/architecture/createAction/CreateActionHelpTest.php','src\console\architecture\createAction\CreateActionTaskTest'=>BASE_PATH.'tests/src/console/architecture/createAction/CreateActionTaskTest.php','src\console\architecture\createBundle\CreateBundleHelpTest'=>BASE_PATH.'tests/src/console/architecture/createBundle/CreateBundleHelpTest.php','src\console\architecture\createBundle\CreateBundleTaskTest'=>BASE_PATH.'tests/src/console/architecture/createBundle/CreateBundleTaskTest.php','src\console\architecture\createController\CreateControllerHelpTest'=>BASE_PATH.'tests/src/console/architecture/createController/CreateControllerHelpTest.php','src\console\architecture\createController\CreateControllerTaskTest'=>BASE_PATH.'tests/src/console/architecture/createController/CreateControllerTaskTest.php','src\console\architecture\createGlobalConstants\CreateGlobalConstantsHelpTest'=>BASE_PATH.'tests/src/console/architecture/createGlobalConstants/CreateGlobalConstantsHelpTest.php','src\console\architecture\createHelloWorld\CreateHelloWorldHelpTest'=>BASE_PATH.'tests/src/console/architecture/createHelloWorld/CreateHelloWorldHelpTest.php','src\console\architecture\createHelloWorld\CreateHelloWorldTaskTest'=>BASE_PATH.'tests/src/console/architecture/createHelloWorld/CreateHelloWorldTaskTest.php','src\console\architecture\createModel\CreateModelHelpTest'=>BASE_PATH.'tests/src/console/architecture/createModel/CreateModelHelpTest.php','src\console\architecture\createModel\CreateModelTaskTest'=>BASE_PATH.'tests/src/console/architecture/createModel/CreateModelTaskTest.php','src\console\architecture\createModule\CreateModuleHelpTest'=>BASE_PATH.'tests/src/console/architecture/createModule/CreateModuleHelpTest.php','src\console\architecture\createModule\CreateModuleTaskTest'=>BASE_PATH.'tests/src/console/architecture/createModule/CreateModuleTaskTest.php','src\console\architecture\init\InitHelpTest'=>BASE_PATH.'tests/src/console/architecture/init/InitHelpTest.php','src\console\database\sqlClean\SqlCleanHelpTest'=>BASE_PATH.'tests/src/console/database/sqlClean/SqlCleanHelpTest.php','src\console\database\sqlClean\SqlCleanTaskTest'=>BASE_PATH.'tests/src/console/database/sqlClean/SqlCleanTaskTest.php','src\console\database\sqlCreateDatabase\SqlCreateDatabaseHelpTest'=>BASE_PATH.'tests/src/console/database/sqlCreateDatabase/SqlCreateDatabaseHelpTest.php','src\console\database\sqlCreateDatabase\SqlCreateDatabaseTaskTest'=>BASE_PATH.'tests/src/console/database/sqlCreateDatabase/SqlCreateDatabaseTaskTest.php','src\console\database\sqlCreateFixtures\SqlCreateFixturesHelpTest'=>BASE_PATH.'tests/src/console/database/sqlCreateFixtures/SqlCreateFixturesHelpTest.php','src\console\database\sqlCreateFixtures\SqlCreateFixturesTaskTest'=>BASE_PATH.'tests/src/console/database/sqlCreateFixtures/SqlCreateFixturesTaskTest.php','src\console\database\sqlExecute\SqlExecuteHelpTest'=>BASE_PATH.'tests/src/console/database/sqlExecute/SqlExecuteHelpTest.php','src\console\database\sqlExecute\SqlExecuteTaskTest'=>BASE_PATH.'tests/src/console/database/sqlExecute/SqlExecuteTaskTest.php','src\console\database\sqlImportFixtures\SqlImportFixturesHelpTest'=>BASE_PATH.'tests/src/console/database/sqlImportFixtures/SqlImportFixturesHelpTest.php','src\console\database\sqlImportFixtures\SqlImportFixturesTaskTest'=>BASE_PATH.'tests/src/console/database/sqlImportFixtures/SqlImportFixturesTaskTest.php','src\console\database\sqlImportSchema\SqlImportSchemaHelpTest'=>BASE_PATH.'tests/src/console/database/sqlImportSchema/SqlImportSchemaHelpTest.php','src\console\database\sqlImportSchema\SqlImportSchemaTaskTest'=>BASE_PATH.'tests/src/console/database/sqlImportSchema/SqlImportSchemaTaskTest.php','src\console\database\sqlMigrationExecute\SqlMigrationExecuteHelpTest'=>BASE_PATH.'tests/src/console/database/sqlMigrationExecute/SqlMigrationExecuteHelpTest.php','src\console\database\sqlMigrationExecute\SqlMigrationExecuteTaskTest'=>BASE_PATH.'tests/src/console/database/sqlMigrationExecute/SqlMigrationExecuteTaskTest.php','src\console\database\sqlMigrationGenerate\SqlMigrationGenerateHelpTest'=>BASE_PATH.'tests/src/console/database/sqlMigrationGenerate/SqlMigrationGenerateHelpTest.php','src\console\database\sqlMigrationGenerate\SqlMigrationGenerateTaskTest'=>BASE_PATH.'tests/src/console/database/sqlMigrationGenerate/SqlMigrationGenerateTaskTest.php','src\console\DatabaseTest'=>BASE_PATH.'tests/src/console/DatabaseTest.php','src\console\deployment\buildDev\BuildDevHelpTest'=>BASE_PATH.'tests/src/console/deployment/buildDev/BuildDevHelpTest.php','src\console\deployment\clearCache\ClearCacheHelpTest'=>BASE_PATH.'tests/src/console/deployment/clearCache/ClearCacheHelpTest.php','src\console\deployment\clearCache\ClearCacheTaskTest'=>BASE_PATH.'tests/src/console/deployment/clearCache/ClearCacheTaskTest.php','src\console\deployment\deploy\DeployHelpTest'=>BASE_PATH.'tests/src/console/deployment/deploy/DeployHelpTest.php','src\console\deployment\genAssets\GenAssetsHelpTest'=>BASE_PATH.'tests/src/console/deployment/genAssets/GenAssetsHelpTest.php','src\console\deployment\genAssets\GenAssetsTaskTest'=>BASE_PATH.'tests/src/console/deployment/genAssets/GenAssetsTaskTest.php','src\console\deployment\genBootstrap\GenBootstrapHelpTest'=>BASE_PATH.'tests/src/console/deployment/genBootstrap/GenBootstrapHelpTest.php','src\console\deployment\genBootstrap\taskFileOperation\AnalyzeUseTokenTest'=>BASE_PATH.'tests/src/console/deployment/genBootstrap/taskFileOperation/AnalyzeUseTokenTest.php','src\console\deployment\genBootstrap\taskFileOperation\CompressTest'=>BASE_PATH.'tests/src/console/deployment/genBootstrap/taskFileOperation/CompressTest.php','otra\console\deployment\genBootstrap\ContentToFileTest'=>BASE_PATH.'tests/src/console/deployment/genBootstrap/taskFileOperation/ContentToFileTest.php','src\console\deployment\genBootstrap\taskFileOperation\ContentToFileTest'=>BASE_PATH.'tests/src/console/deployment/genBootstrap/taskFileOperation/ContentToFileTest.php','src\console\deployment\genBootstrap\taskFileOperation\EvalPathVariablesTest'=>BASE_PATH.'tests/src/console/deployment/genBootstrap/taskFileOperation/EvalPathVariablesTest.php','otra\console\deployment\genBootstrap\FixFilesTest'=>BASE_PATH.'tests/src/console/deployment/genBootstrap/taskFileOperation/FixFilesTest.php','src\console\deployment\genBootstrap\taskFileOperation\FixFilesTest'=>BASE_PATH.'tests/src/console/deployment/genBootstrap/taskFileOperation/FixFilesTest.php','src\console\deployment\genBootstrap\taskFileOperation\GetFileInfoFromRequireMatchTest'=>BASE_PATH.'tests/src/console/deployment/genBootstrap/taskFileOperation/GetFileInfoFromRequireMatchTest.php','src\console\deployment\genBootstrap\taskFileOperation\GetFileInfoFromRequiresAndExtendsTest'=>BASE_PATH.'tests/src/console/deployment/genBootstrap/taskFileOperation/GetFileInfoFromRequiresAndExtendsTest.php','otra\console\deployment\genBootstrap\GetFileNamesFromUsesTest'=>BASE_PATH.'tests/src/console/deployment/genBootstrap/taskFileOperation/GetFileNamesFromUsesTest.php','src\console\deployment\genBootstrap\taskFileOperation\GetFileNamesFromUsesTest'=>BASE_PATH.'tests/src/console/deployment/genBootstrap/taskFileOperation/GetFileNamesFromUsesTest.php','src\console\deployment\genBootstrap\taskFileOperation\HasSyntaxErrorsTest'=>BASE_PATH.'tests/src/console/deployment/genBootstrap/taskFileOperation/HasSyntaxErrorsTest.php','src\console\deployment\genBootstrap\taskFileOperation\ProcessReturnTest'=>BASE_PATH.'tests/src/console/deployment/genBootstrap/taskFileOperation/ProcessReturnTest.php','src\console\deployment\genBootstrap\taskFileOperation\ProcessStaticCallsTest'=>BASE_PATH.'tests/src/console/deployment/genBootstrap/taskFileOperation/ProcessStaticCallsTest.php','src\console\deployment\genBootstrap\taskFileOperation\ResolveInclusionPathTest'=>BASE_PATH.'tests/src/console/deployment/genBootstrap/taskFileOperation/ResolveInclusionPathTest.php','src\console\deployment\genBootstrap\taskFileOperation\SearchForClassTest'=>BASE_PATH.'tests/src/console/deployment/genBootstrap/taskFileOperation/SearchForClassTest.php','src\console\deployment\genBootstrap\taskFileOperation\SeparateInsideAndOutsidePhpTest'=>BASE_PATH.'tests/src/console/deployment/genBootstrap/taskFileOperation/SeparateInsideAndOutsidePhpTest.php','src\console\deployment\genBootstrap\taskFileOperation\ShowFileTest'=>BASE_PATH.'tests/src/console/deployment/genBootstrap/taskFileOperation/ShowFileTest.php','src\console\deployment\genClassMap\GenClassMapHelpTest'=>BASE_PATH.'tests/src/console/deployment/genClassMap/GenClassMapHelpTest.php','src\console\deployment\genClassMap\GenClassMapTaskTest'=>BASE_PATH.'tests/src/console/deployment/genClassMap/GenClassMapTaskTest.php','src\console\deployment\GenerateOptimizedJavascriptTest'=>BASE_PATH.'tests/src/console/deployment/GenerateOptimizedJavascriptTest.php','src\console\deployment\genJsRouting\GenJsRoutingHelpTest'=>BASE_PATH.'tests/src/console/deployment/genJsRouting/GenJsRoutingHelpTest.php','src\console\deployment\genJsRouting\GenJsRoutingTaskTest'=>BASE_PATH.'tests/src/console/deployment/genJsRouting/GenJsRoutingTaskTest.php','src\console\deployment\genServerConfig\GenServerConfigHelpTest'=>BASE_PATH.'tests/src/console/deployment/genServerConfig/GenServerConfigHelpTest.php','src\console\deployment\genServerConfig\GenServerConfigTaskTest'=>BASE_PATH.'tests/src/console/deployment/genServerConfig/GenServerConfigTaskTest.php','src\console\deployment\genSitemap\GenSitemapHelpTest'=>BASE_PATH.'tests/src/console/deployment/genSitemap/GenSitemapHelpTest.php','src\console\deployment\genSitemap\GenSitemapTaskTest'=>BASE_PATH.'tests/src/console/deployment/genSitemap/GenSitemapTaskTest.php','src\console\deployment\genWatcher\GenWatcherHelpTest'=>BASE_PATH.'tests/src/console/deployment/genWatcher/GenWatcherHelpTest.php','src\console\deployment\genWatcher\SassToolsTest'=>BASE_PATH.'tests/src/console/deployment/genWatcher/SassToolsTest.php','src\console\deployment\TaskFileInitTest'=>BASE_PATH.'tests/src/console/deployment/TaskFileInitTest.php','src\console\deployment\updateConf\UpdateConfHelpTest'=>BASE_PATH.'tests/src/console/deployment/updateConf/UpdateConfHelpTest.php','src\console\helpAndTools\checkConfiguration\CheckConfigurationHelpTest'=>BASE_PATH.'tests/src/console/helpAndTools/checkConfiguration/CheckConfigurationHelpTest.php','src\console\helpAndTools\checkConfiguration\CheckConfigurationTaskTest'=>BASE_PATH.'tests/src/console/helpAndTools/checkConfiguration/CheckConfigurationTaskTest.php','src\console\helpAndTools\clearSession\ClearSessionHelpTest'=>BASE_PATH.'tests/src/console/helpAndTools/clearSession/ClearSessionHelpTest.php','src\console\helpAndTools\clearSession\ClearSessionTaskTest'=>BASE_PATH.'tests/src/console/helpAndTools/clearSession/ClearSessionTaskTest.php','src\console\helpAndTools\convertImages\ConvertImagesHelpTest'=>BASE_PATH.'tests/src/console/helpAndTools/convertImages/ConvertImagesHelpTest.php','src\console\helpAndTools\convertImages\ConvertTaskTest'=>BASE_PATH.'tests/src/console/helpAndTools/convertImages/ConvertTaskTest.php','src\console\helpAndTools\crypt\CryptHelpTest'=>BASE_PATH.'tests/src/console/helpAndTools/crypt/CryptHelpTest.php','src\console\helpAndTools\crypt\CryptTaskTest'=>BASE_PATH.'tests/src/console/helpAndTools/crypt/CryptTaskTest.php','src\console\helpAndTools\generateTaskMetadata\GenerateTaskMetadataHelpTest'=>BASE_PATH.'tests/src/console/helpAndTools/generateTaskMetadata/GenerateTaskMetadataHelpTest.php','src\console\helpAndTools\generateTaskMetadata\GenerateTaskMetadataTaskTest'=>BASE_PATH.'tests/src/console/helpAndTools/generateTaskMetadata/GenerateTaskMetadataTaskTest.php','src\console\helpAndTools\hash\HashHelpTest'=>BASE_PATH.'tests/src/console/helpAndTools/hash/HashHelpTest.php','src\console\helpAndTools\hash\HashTaskTest'=>BASE_PATH.'tests/src/console/helpAndTools/hash/HashTaskTest.php','src\console\helpAndTools\help\HelpTaskTest'=>BASE_PATH.'tests/src/console/helpAndTools/help/HelpTaskTest.php','src\console\helpAndTools\requirements\RequirementsHelpTest'=>BASE_PATH.'tests/src/console/helpAndTools/requirements/RequirementsHelpTest.php','src\console\helpAndTools\requirements\RequirementsTaskTest'=>BASE_PATH.'tests/src/console/helpAndTools/requirements/RequirementsTaskTest.php','src\console\helpAndTools\routes\RoutesHelpTest'=>BASE_PATH.'tests/src/console/helpAndTools/routes/RoutesHelpTest.php','src\console\helpAndTools\routes\RoutesTaskTest'=>BASE_PATH.'tests/src/console/helpAndTools/routes/RoutesTaskTest.php','src\console\helpAndTools\serve\ServeHelpTest'=>BASE_PATH.'tests/src/console/helpAndTools/serve/ServeHelpTest.php','src\console\helpAndTools\serve\ServeTaskTest'=>BASE_PATH.'tests/src/console/helpAndTools/serve/ServeTaskTest.php','src\console\helpAndTools\version\VersionHelpTest'=>BASE_PATH.'tests/src/console/helpAndTools/version/VersionHelpTest.php','src\console\helpAndTools\version\VersionTaskTest'=>BASE_PATH.'tests/src/console/helpAndTools/version/VersionTaskTest.php','src\console\LaunchTaskPosixWayTest'=>BASE_PATH.'tests/src/console/LaunchTaskPosixWayTest.php','src\console\LaunchTaskTest'=>BASE_PATH.'tests/src/console/LaunchTaskTest.php','src\console\OtraExceptionCliTest'=>BASE_PATH.'tests/src/console/OtraExceptionCliTest.php','src\console\ToolsTest'=>BASE_PATH.'tests/src/console/ToolsTest.php','src\controllers\errors\Error404ActionTest'=>BASE_PATH.'tests/src/controllers/errors/Error404ActionTest.php','src\controllers\profiler\ClearSqlLogsActionTest'=>BASE_PATH.'tests/src/controllers/profiler/ClearSqlLogsActionTest.php','src\controllers\profiler\CssActionTest'=>BASE_PATH.'tests/src/controllers/profiler/CssActionTest.php','src\controllers\profiler\LogsActionTest'=>BASE_PATH.'tests/src/controllers/profiler/LogsActionTest.php','src\controllers\profiler\RequestsActionTest'=>BASE_PATH.'tests/src/controllers/profiler/RequestsActionTest.php','src\controllers\profiler\RoutesActionTest'=>BASE_PATH.'tests/src/controllers/profiler/RoutesActionTest.php','src\controllers\profiler\SqlActionTest'=>BASE_PATH.'tests/src/controllers/profiler/SqlActionTest.php','src\controllers\profiler\TemplateStructureActionTest'=>BASE_PATH.'tests/src/controllers/profiler/TemplateStructureActionTest.php','src\database\PdomysqlTest'=>BASE_PATH.'tests/src/database/PdomysqlTest.php','src\database\SqlTest'=>BASE_PATH.'tests/src/database/SqlTest.php','src\LoggerTest'=>BASE_PATH.'tests/src/LoggerTest.php','tests\src\MasterControllerTest'=>BASE_PATH.'tests/src/MasterControllerTest.php','src\OtraExceptionTest'=>BASE_PATH.'tests/src/OtraExceptionTest.php','src\RouterTest'=>BASE_PATH.'tests/src/RouterTest.php','src\services\ProfilerServiceTest'=>BASE_PATH.'tests/src/services/ProfilerServiceTest.php','src\services\SecurityServiceTest'=>BASE_PATH.'tests/src/services/SecurityServiceTest.php','src\SessionTest'=>BASE_PATH.'tests/src/SessionTest.php','src\templating\BlocksTest'=>BASE_PATH.'tests/src/templating/BlocksTest.php','src\templating\HtmlMinifierTest'=>BASE_PATH.'tests/src/templating/HtmlMinifierTest.php','src\tools\debug\DumpTest'=>BASE_PATH.'tests/src/tools/debug/DumpTest.php','src\tools\debug\GetCallerTest'=>BASE_PATH.'tests/src/tools/debug/GetCallerTest.php','src\tools\debug\TailCustomTest'=>BASE_PATH.'tests/src/tools/debug/TailCustomTest.php','src\tools\GetOtraCommitNumberTest'=>BASE_PATH.'tests/src/tools/GetOtraCommitNumberTest.php','src\tools\ReformatSourceTest'=>BASE_PATH.'tests/src/tools/ReformatSourceTest.php','src\tools\workers\WorkerManagerTest'=>BASE_PATH.'tests/src/tools/workers/WorkerManagerTest.php','src\tools\workers\WorkerTest'=>BASE_PATH.'tests/src/tools/workers/WorkerTest.php','src\views\profiler\MacrosTest'=>BASE_PATH.'tests/src/views/profiler/MacrosTest.php','Composer\Autoload\ClassLoader'=>BASE_PATH.'vendor/composer/ClassLoader.php','Composer\InstalledVersions'=>BASE_PATH.'vendor/composer/InstalledVersions.php','Symfony\Polyfill\Ctype\Ctype'=>BASE_PATH.'vendor/symfony/polyfill-ctype/Ctype.php','Symfony\Component\Yaml\Command\LintCommand'=>BASE_PATH.'vendor/symfony/yaml/Command/LintCommand.php','Symfony\Component\Yaml\Dumper'=>BASE_PATH.'vendor/symfony/yaml/Dumper.php','Symfony\Component\Yaml\Escaper'=>BASE_PATH.'vendor/symfony/yaml/Escaper.php','Symfony\Component\Yaml\Exception\DumpException'=>BASE_PATH.'vendor/symfony/yaml/Exception/DumpException.php','Symfony\Component\Yaml\Exception\ExceptionInterface'=>BASE_PATH.'vendor/symfony/yaml/Exception/ExceptionInterface.php','Symfony\Component\Yaml\Exception\ParseException'=>BASE_PATH.'vendor/symfony/yaml/Exception/ParseException.php','Symfony\Component\Yaml\Exception\RuntimeException'=>BASE_PATH.'vendor/symfony/yaml/Exception/RuntimeException.php','Symfony\Component\Yaml\Inline'=>BASE_PATH.'vendor/symfony/yaml/Inline.php','Symfony\Component\Yaml\Parser'=>BASE_PATH.'vendor/symfony/yaml/Parser.php','Symfony\Component\Yaml\Tag\TaggedValue'=>BASE_PATH.'vendor/symfony/yaml/Tag/TaggedValue.php','Symfony\Component\Yaml\Tests\Command\LintCommandTest'=>BASE_PATH.'vendor/symfony/yaml/Tests/Command/LintCommandTest.php','Symfony\Component\Yaml\Tests\DumperTest'=>BASE_PATH.'vendor/symfony/yaml/Tests/DumperTest.php','Symfony\Component\Yaml\Tests\Fixtures\FooUnitEnum'=>BASE_PATH.'vendor/symfony/yaml/Tests/Fixtures/FooUnitEnum.php','Symfony\Component\Yaml\Tests\InlineTest'=>BASE_PATH.'vendor/symfony/yaml/Tests/InlineTest.php','Symfony\Component\Yaml\Tests\ParseExceptionTest'=>BASE_PATH.'vendor/symfony/yaml/Tests/ParseExceptionTest.php','Symfony\Component\Yaml\Tests\ParserTest'=>BASE_PATH.'vendor/symfony/yaml/Tests/ParserTest.php','Symfony\Component\Yaml\Tests\YamlTest'=>BASE_PATH.'vendor/symfony/yaml/Tests/YamlTest.php','Symfony\Component\Yaml\Unescaper'=>BASE_PATH.'vendor/symfony/yaml/Unescaper.php','Symfony\Component\Yaml\Yaml'=>BASE_PATH.'vendor/symfony/yaml/Yaml.php','config\AllConfig'=>BASE_PATH.'config/AllConfig.php'];
