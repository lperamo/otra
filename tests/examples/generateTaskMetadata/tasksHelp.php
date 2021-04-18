<?php declare(strict_types=1);return ['createAction'=>[0=>'Creates actions.',1=>['bundle'=>'The bundle where you want to put actions','module'=>'The module where you want to put actions','controller'=>'The controller where you want to put actions','action'=>'The name of the action!','interactive'=>'If set to false, no question will be asked but the status messages are shown. Defaults to true.'],2=>[0=>'required',1=>'required',2=>'required',3=>'required',4=>'optional'],3=>'Architecture'],'createBundle'=>[0=>'Creates a bundle.',1=>['bundle name'=>'The name of the bundle!','mask'=>'In addition to the module, it will create a folder for :0=>nothing1=>config2=>models4=>resources8=>views','interactive'=>'If set to false, no question will be asked but the status messages are shown. Defaults to true.'],2=>[0=>'optional',1=>'optional',2=>'optional'],3=>'Architecture'],'createController'=>[0=>'Creates controllers.',1=>['bundle'=>'The bundle where you want to put controllers','module'=>'The module where you want to put controllers','controller'=>'The name of the controller!','interactive'=>'If set to false, no question will be asked but the status messages are shown. Defaults to true.'],2=>[0=>'required',1=>'required',2=>'required',3=>'optional'],3=>'Architecture'],'createGlobalConstants'=>[0=>'Creates OTRA global constants. [38;2;214;191;85mOnly use it if you have changed the project folder or OTRA vendor folder location.[38;2;41;153;153m',1=>[],2=>[],3=>'Architecture'],'createHelloWorld'=>[0=>'Creates a hello world starter application.',1=>[],2=>[],3=>'Architecture'],'createModel'=>[0=>'Creates a model. [38;2;108;218;218mhow[38;2;41;153;153m parameter is ignored in interactive mode',1=>['bundle name'=>'The bundle in which the model have to be created','method'=>'1=>Creates from nothing2=>One model from [38;2;108;218;218mschema.yml[38;2;41;153;153m3=>All models from [38;2;108;218;218mschema.yml[38;2;41;153;153mUseless with the [38;2;108;218;218minteractive[38;2;41;153;153m mode.','interactive'=>'If set to false, no question will be asked but the status messages are shown.Defaults to true.','model location'=>'Location of the model to create :0=>in the [38;2;108;218;218mbundle (default)[38;2;41;153;153m folder1=>in the [38;2;108;218;218mmodule[38;2;41;153;153m folder.Only useful (and required) for the [38;2;108;218;218minteractive[38;2;41;153;153m mode.','module name'=>'Name of the module in which the model have to be created.Only useful (and required) for the [38;2;108;218;218minteractive[38;2;41;153;153m mode.','model name'=>'Name of the model to create.Only useful (and required) for the [38;2;108;218;218minteractive[38;2;41;153;153m mode.Required for mode 1 and mode 2, useless for mode 3.If the model exists or the model is not specified we import it.If it does not exist, we create it.','model properties'=>'Names of the model properties separated by commas. Eg : [38;2;108;218;218mid,name,age[38;2;41;153;153m.Only useful (and required) for the [38;2;108;218;218minteractive[38;2;41;153;153m mode when we want to create a new modelfrom nothing.','model SQL types'=>'SQL types of the model properties separated by commas. Eg : [38;2;108;218;218mint,string,bool,bool[38;2;41;153;153m.Only useful (and required) for the [38;2;108;218;218minteractive[38;2;41;153;153m mode when we want to create a new modelfrom nothing.'],2=>[0=>'optional',1=>'optional',2=>'optional',3=>'optional',4=>'optional',5=>'optional',6=>'optional',7=>'optional'],3=>'Architecture'],'createModule'=>[0=>'Creates modules.',1=>['bundle'=>'The name of the bundle in which you want to put modules','module'=>'The name of the module!','interactive'=>'If set to false, no question will be asked but the status messages are shown. Defaults to true.'],2=>[0=>'required',1=>'required',2=>'optional'],3=>'Architecture'],'init'=>[0=>'Initializes the OTRA project.',1=>[],2=>[],3=>'Architecture'],'sqlClean'=>[0=>'Removes sql and yml files in the case where there are problems that had corrupted files.',1=>['cleaningLevel'=>'Type 1 in order to also remove the file that describes the tables order.'],2=>[0=>'optional'],3=>'Database'],'sqlCreateDatabase'=>[0=>'Database creation, tables creation.(sql_generate_basic)',1=>['databaseName'=>'The database name !','force'=>'If true, we erase the database !'],2=>[0=>'required',1=>'optional'],3=>'Database'],'sqlCreateFixtures'=>[0=>'Generates fixtures sql files and executes them. (sql_generate_fixtures)',1=>['databaseName'=>'The database name !','mask'=>'1=>We erase the database2=>We clean the fixtures sql files and we erase the database.'],2=>[0=>'required',1=>'optional'],3=>'Database'],'sqlExecute'=>[0=>'Executes the sql script',1=>['file'=>'File that will be executed','database'=>'Database to use for this script'],2=>[0=>'required',1=>'optional'],3=>'Database'],'sqlImportFixtures'=>[0=>'Import the fixtures from database into [38;2;214;191;85mconfig/data/yml/fixtures[38;2;41;153;153m.',1=>['databaseName'=>'The database name ! If not specified, we use the database specified in the configuration file.','configuration'=>'The configuration that you want to use from your configuration file.'],2=>[0=>'optional',1=>'optional'],3=>'Database'],'sqlImportSchema'=>[0=>'Creates the database schema from your database. (importSchema)',1=>['databaseName'=>'The database name ! If not specified, we use the database specified in the configuration file.','configuration'=>'The configuration that you want to use from your configuration file.'],2=>[0=>'optional',1=>'optional'],3=>'Database'],'buildDev'=>[0=>'Compiles the typescripts, sass and php configuration files (modulo the binary mask).',1=>['verbose'=>'0=>Quite silent, 1=>Tells which file has been updated.','mask'=>'1=>SCSS, 2=>TS, ..., 4=>routes, ..., 8=>PHP, 15=>ALL. Default to 15.','gcc'=>'Should we use Google Closure Compiler for javascript/typescript files ? Defaults to false.','scope'=>'0=>project files (default), 1=>OTRA files, 2=>All the files'],2=>[0=>'optional',1=>'optional',2=>'optional',3=>'optional',4=>'optional'],3=>'Deployment'],'clearCache'=>[0=>'Clears whatever cache you want to clear.',1=>['mask'=>'1=>PHP OTRA internal cache2=>PHP bootstraps4=>CSS8=>JS 16=>Templates 32=>Route management 64=>Class mapping (development & production)128=>Console tasks metadata256=>Security files511=>All files from the cache (default)','route name'=>'If you want to clear cache for only one route. (useful only for bits 2, 4, 8 of the [38;2;108;218;218mmask[38;2;41;153;153m parameter)'],2=>[0=>'optional',1=>'optional'],3=>'Deployment'],'deploy'=>[0=>'Deploy the site. [38;2;214;191;85m[Currently only works for unix systems !][0m',1=>['mask'=>'0=>Nothing to do (default)1=>Generates PHP production files.2=>JS production files.4=>CSS production files8=>Templates, JSON manifest and SVGs15=>all production files','verbose'=>'If set to 1=>we print all the warnings during the production php files generation'],2=>[0=>'optional',1=>'optional'],3=>'Deployment'],'genAssets'=>[0=>'Generates one css file and one js file that contain respectively all the minified css files and all the obfuscated minified js files. Gzips the SVGs.',1=>['mask'=>'1=>templates2=>CSS4=>JS8=>JSON manifest16=>SVG31=>all (default)','js_level_compilation'=>'Optimization level for Google Closure Compiler0 for WHITESPACE_ONLY1 for SIMPLE_OPTIMIZATIONS (default)2 for ADVANCED_OPTIMIZATIONS','route'=>'The route for which you want to generate resources.'],2=>[0=>'optional',1=>'optional',2=>'optional'],3=>'Deployment'],'genBootstrap'=>[0=>'Launch the genClassMap command and generates a file that contains all the necessary php files.',1=>['genClassmap'=>'If set to 0, it prevents the generation/override of the class mapping file.','verbose'=>'If set to 1, we print all the main warnings when the task fails. Put 2 to get every warning.','lint'=>'Checks for syntax errors. 0 disabled, 1 enabled (defaults to 0)','route'=>'The route for which you want to generate the micro bootstrap.'],2=>[0=>'optional',1=>'optional',2=>'optional',3=>'optional'],3=>'Deployment'],'genClassMap'=>[0=>'Generates a class mapping file that will be used to replace the autoloading method.',1=>['verbose'=>'If set to 1=>Show all the classes that will be used. Default to 0.'],2=>[0=>'optional'],3=>'Deployment',4=>'/var/www/html/perso/otra/src/console/deployment/genClassMap/genClassMapHelp.php'],'genJsRouting'=>[0=>'Generates a route mapping that can be used by JavaScript files.',1=>[],2=>[],3=>'Deployment'],'genServerConfig'=>[0=>'Generates a server configuration adapted to OTRA.',1=>['file name'=>'Name of the file to put the generated configuration','environment'=>'[38;2;108;218;218mdev[38;2;41;153;153m (default) or [38;2;108;218;218mprod','serverTechnology'=>'[38;2;108;218;218mnginx[38;2;41;153;153m (default) or [38;2;108;218;218mapache[38;2;214;191;85m (but works only for Nginx for now)'],2=>[0=>'required',1=>'optional',2=>'optional'],3=>'Deployment'],'genSitemap'=>[0=>'Generates a sitemap based on routes configuration.',1=>[],2=>[],3=>'Deployment'],'genWatcher'=>[0=>'Launches a watcher that will update the PHP class mapping, the ts files and the scss files.',1=>['verbose'=>'0=>Only tells that the watcher is started.1=>Tells which file has been updated (default).2=>Tells which file has been updated and the most important events that have been triggered.Default to 1.','mask'=>'1=>SCSS, 2=>TS, ..., 4=>routes, ..., 8=>PHP, 15=>ALL. Default to 15.','gcc'=>'Should we use Google Closure Compiler for javascript/typescript files ?'],2=>[0=>'optional',1=>'optional',2=>'optional'],3=>'Deployment'],'updateConf'=>[0=>'Updates the files related to bundles and routes : schemas, routes, securities.',1=>['route'=>'To update only security files related to one specific route'],2=>[0=>'optional'],3=>'Deployment'],'crypt'=>[0=>'Crypts a password and shows it.',1=>['password'=>'The password to crypt.','iterations'=>'The number of internal iterations to perform for the derivation.'],2=>[0=>'required',1=>'optional'],3=>'Help and tools'],'generateTaskMetadata'=>[0=>'Generates files that are used to show the help, finds quickly all the tasks and gives shell completions.',1=>[],2=>[],3=>'Help and tools'],'hash'=>[0=>'Returns a random hash.',1=>['rounds'=>'The numbers of round for the blowfish salt. Default: 7.'],2=>[0=>'optional'],3=>'Help and tools'],'help'=>[0=>'Shows the extended help for the specified command.',1=>['command'=>'The command which you need help for.'],2=>[0=>'required'],3=>'Help and tools'],'requirements'=>[0=>'Shows the requirements to use OTRA at its maximum capabilities.',1=>[],2=>[],3=>'Help and tools'],'routes'=>[0=>'Shows the routes and their associated kind of resources in the case they have some. (lightGreen whether they exists, red otherwise)',1=>['route'=>'The name of the route that we want information from, if we wish only one route description.'],2=>[0=>'optional'],3=>'Help and tools'],'serve'=>[0=>'Creates a PHP web internal server.',1=>['port'=>'The port used by the server ... Defaults to 8000','env'=>'Environment mode [dev,prod]. Defaults to \'dev\'.'],2=>[0=>'optional',1=>'optional'],3=>'Help and tools'],'version'=>[0=>'Shows the framework version.',1=>NULL,2=>NULL,3=>'Help and tools']];
