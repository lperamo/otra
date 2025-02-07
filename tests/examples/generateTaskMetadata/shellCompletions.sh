#!/usr/bin/env bash
typeset BLC="\033[1;96m"
typeset WHI="\033[0;38m"
typeset CYA="\033[0;36m"
typeset ECO="\033[0m"
typeset -a OTRA_COMMANDS=(
  'createAction'
  'createBundle'
  'createController'
  'createGlobalConstants'
  'createHelloWorld'
  'createModel'
  'createModule'
  'init'
  'sqlClean'
  'sqlCreateDatabase'
  'sqlCreateFixtures'
  'sqlExecute'
  'sqlImportFixtures'
  'sqlImportSchema'
  'sqlMigrationExecute'
  'sqlMigrationGenerate'
  'buildDev'
  'clearCache'
  'deploy'
  'genAssets'
  'genBootstrap'
  'genClassMap'
  'genJsRouting'
  'genServerConfig'
  'genSitemap'
  'genWatcher'
  'updateConf'
  'gptInstructions'
  'checkConfiguration'
  'clearSession'
  'convertImages'
  'crypt'
  'generateTaskMetadata'
  'hash'
  'help'
  'requirements'
  'routes'
  'serve'
  'version'
);

typeset CAT_ARCHITECTURE="${BLC}[  Architecture  ]${WHI}";
typeset CAT_DATABASE="${BLC}[    Database    ]${WHI}";
typeset CAT_DEPLOYMENT="${BLC}[   Deployment   ]${WHI}";
typeset CAT_GPT="${BLC}[      GPT       ]${WHI}";
typeset CAT_HELP_AND_TOOLS="${BLC}[ Help and tools ]${WHI}";

typeset -a OTRA_COMMANDS_DESCRIPTIONS=(
  "${CAT_ARCHITECTURE} createAction                : ${CYA}Creates actions.${ECO}"
  "${CAT_ARCHITECTURE} createBundle                : ${CYA}Creates a bundle.${ECO}"
  "${CAT_ARCHITECTURE} createController            : ${CYA}Creates controllers.${ECO}"
  "${CAT_ARCHITECTURE} createGlobalConstants       : ${CYA}Creates OTRA global constants. [38;2;190;190;100mOnly use it if you have changed the project folder or OTRA vendor folder location.[38;2;100;150;200m${ECO}"
  "${CAT_ARCHITECTURE} createHelloWorld            : ${CYA}Creates a hello world starter application.${ECO}"
  "${CAT_ARCHITECTURE} createModel                 : ${CYA}Creates a model. [38;2;100;200;200mhow[38;2;100;150;200m parameter is ignored in interactive mode${ECO}"
  "${CAT_ARCHITECTURE} createModule                : ${CYA}Creates modules.${ECO}"
  "${CAT_ARCHITECTURE} init                        : ${CYA}Initializes the OTRA project.${ECO}"
  "${CAT_DATABASE} sqlClean                    : ${CYA}Removes sql and yml files in the case where there are problems that had corrupted files.${ECO}"
  "${CAT_DATABASE} sqlCreateDatabase           : ${CYA}Database creation, tables creation.(sql_generate_basic)${ECO}"
  "${CAT_DATABASE} sqlCreateFixtures           : ${CYA}Generates fixtures sql files and executes them. (sql_generate_fixtures)${ECO}"
  "${CAT_DATABASE} sqlExecute                  : ${CYA}Executes the sql script${ECO}"
  "${CAT_DATABASE} sqlImportFixtures           : ${CYA}Import the fixtures from database into [38;2;190;190;100mconfig/data/yml/fixtures[38;2;100;150;200m.${ECO}"
  "${CAT_DATABASE} sqlImportSchema             : ${CYA}Creates the database schema from your database. (importSchema)${ECO}"
  "${CAT_DATABASE} sqlMigrationExecute         : ${CYA}Execute a single migration version up or down manually.${ECO}"
  "${CAT_DATABASE} sqlMigrationGenerate        : ${CYA}Creates a new blank database migration file${ECO}"
  "${CAT_DEPLOYMENT} buildDev                    : ${CYA}Compiles the typescripts, sass and php configuration files (modulo the binary mask).${ECO}"
  "${CAT_DEPLOYMENT} clearCache                  : ${CYA}Clears whatever cache you want to clear.${ECO}"
  "${CAT_DEPLOYMENT} deploy                      : ${CYA}Deploy the site. [38;2;190;190;100m[Currently only works for unix systems !][0m${ECO}"
  "${CAT_DEPLOYMENT} genAssets                   : ${CYA}Generates one css file and one js file that contain respectively all the minified css files and all the obfuscated minified js files. Compresses the SVGs.${ECO}"
  "${CAT_DEPLOYMENT} genBootstrap                : ${CYA}Launch the genClassMap command and generates a file that contains all the necessary php files.${ECO}"
  "${CAT_DEPLOYMENT} genClassMap                 : ${CYA}Generates a class mapping file that will be used to replace the autoloading method.${ECO}"
  "${CAT_DEPLOYMENT} genJsRouting                : ${CYA}Generates a route mapping that can be used by JavaScript files.${ECO}"
  "${CAT_DEPLOYMENT} genServerConfig             : ${CYA}Generates a server configuration adapted to OTRA.${ECO}"
  "${CAT_DEPLOYMENT} genSitemap                  : ${CYA}Generates a sitemap based on routes configuration.${ECO}"
  "${CAT_DEPLOYMENT} genWatcher                  : ${CYA}Launches a watcher that will update the PHP class mapping, the ts files and the scss files.${ECO}"
  "${CAT_DEPLOYMENT} updateConf                  : ${CYA}Updates the files related to bundles and routes : schemas, routes, securities.${ECO}"
  "${CAT_GPT} gptInstructions             : ${CYA}Generates CLI commands list for the GPT 'OTRA Mentor'.${ECO}"
  "${CAT_HELP_AND_TOOLS} checkConfiguration          : ${CYA}Checks route configuration files structure.${ECO}"
  "${CAT_HELP_AND_TOOLS} clearSession                : ${CYA}Clears all things related to the session : PHP session and OTRA session.${ECO}"
  "${CAT_HELP_AND_TOOLS} convertImages               : ${CYA}Converts images to another format.${ECO}"
  "${CAT_HELP_AND_TOOLS} crypt                       : ${CYA}Crypts a password and shows it.${ECO}"
  "${CAT_HELP_AND_TOOLS} generateTaskMetadata        : ${CYA}Generates files that are used to show the help, finds quickly all the tasks and gives shell completions.${ECO}"
  "${CAT_HELP_AND_TOOLS} hash                        : ${CYA}Returns a random hash.${ECO}"
  "${CAT_HELP_AND_TOOLS} help                        : ${CYA}Shows the extended help for the specified command.${ECO}"
  "${CAT_HELP_AND_TOOLS} requirements                : ${CYA}Shows the requirements to use OTRA at its maximum capabilities.${ECO}"
  "${CAT_HELP_AND_TOOLS} routes                      : ${CYA}Shows the routes and their associated kind of resources in the case they have some. (lightGreen whether they exists, red otherwise)${ECO}"
  "${CAT_HELP_AND_TOOLS} serve                       : ${CYA}Creates a PHP web internal server.${ECO}"
  "${CAT_HELP_AND_TOOLS} version                     : ${CYA}Shows the framework version.${ECO}"
)

export OTRA_COMMANDS
export OTRA_COMMANDS_DESCRIPTIONS
