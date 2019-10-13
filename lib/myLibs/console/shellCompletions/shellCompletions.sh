#!/usr/bin/env bash
typeset CLI_BOLD_LIGHT_CYAN="\033[1;96m"
typeset CLI_WHITE="\033[0;38m"
typeset CLI_CYAN="\033[0;36m"
typeset END_COLOR="\033[0m"
typeset -a OTRA_COMMANDS=(
  'createAction'
  'createBundle'
  'createController'
  'createHelloWorld'
  'createModel'
  'createModule'
  'buildDev'
  'cc'
  'deploy'
  'genAssets'
  'genBootstrap'
  'genClassMap'
  'genWatcher'
  'upConf'
  'sql'
  'sql_clean'
  'sql_gdb'
  'sql_gf'
  'sql_is'
  'sql_if'
  'help'
  'crypt'
  'hash'
  'requirements'
  'routes'
  'version'
);

typeset OTRA_ARCHITECTURE="${CLI_BOLD_LIGHT_CYAN}[  Architecture  ]${CLI_WHITE}";
typeset OTRA_DEPLOYMENT="${CLI_BOLD_LIGHT_CYAN}[   Deployment   ]${CLI_WHITE}";
typeset OTRA_DATABASE="${CLI_BOLD_LIGHT_CYAN}[    Database    ]${CLI_WHITE}";
typeset HELP_AND_TOOLS="${CLI_BOLD_LIGHT_CYAN}[ Help and tools ]${CLI_WHITE}";

typeset -a OTRA_COMMANDS_DESCRIPTIONS=(
  "${OTRA_ARCHITECTURE} createAction     : ${CLI_CYAN}Creates actions.${END_COLOR}"
  "${OTRA_ARCHITECTURE} createBundle     : ${CLI_CYAN}Creates a bundle.${END_COLOR}"
  "${OTRA_ARCHITECTURE} createController : ${CLI_CYAN}Creates controllers.${END_COLOR}"
  "${OTRA_ARCHITECTURE} createHelloWorld : ${CLI_CYAN}Creates a hello world starter application.${END_COLOR}"
  "${OTRA_ARCHITECTURE} createModel      : ${CLI_CYAN}Creates a model.${END_COLOR}"
  "${OTRA_ARCHITECTURE} createModule     : ${CLI_CYAN}Creates modules.${END_COLOR}"
  "${OTRA_DEPLOYMENT} buildDev         : ${CLI_CYAN}Compiles the typescripts, sass and php configuration files (modulo the binary mask).${END_COLOR}"
  "${OTRA_DEPLOYMENT} cc               : ${CLI_CYAN}Clears the cache${END_COLOR}"
  "${OTRA_DEPLOYMENT} deploy           : ${CLI_CYAN}Deploy the site. [ WIP - Do not use yet ! ] ${END_COLOR}"
  "${OTRA_DEPLOYMENT} genAssets        : ${CLI_CYAN}Generates one css file and one js file that contain respectively all the minified css files and all the obfuscated minified js files.${END_COLOR}"
  "${OTRA_DEPLOYMENT} genBootstrap     : ${CLI_CYAN}Launch the genClassMap command and generates a file that contains all the necessary php files.${END_COLOR}"
  "${OTRA_DEPLOYMENT} genClassMap      : ${CLI_CYAN}Generates a class mapping file that will be used to replace the autoloading method.${END_COLOR}"
  "${OTRA_DEPLOYMENT} genWatcher       : ${CLI_CYAN}Launches a watcher that will update the PHP class mapping, the ts files and the scss files.${END_COLOR}"
  "${OTRA_DEPLOYMENT} upConf           : ${CLI_CYAN}Updates the files related to bundles and routes. ${END_COLOR}"
  "${OTRA_DATABASE} sql              : ${CLI_CYAN}Executes the sql script${END_COLOR}"
  "${OTRA_DATABASE} sql_clean        : ${CLI_CYAN}Removes sql and yml files in the case where there are problems that had corrupted files.${END_COLOR}"
  "${OTRA_DATABASE} sql_gdb          : ${CLI_CYAN}Database creation, tables creation.(sql_generate_basic)${END_COLOR}"
  "${OTRA_DATABASE} sql_gf           : ${CLI_CYAN}Generates fixtures sql files and executes them. (sql_generate_fixtures${END_COLOR}"
  "${OTRA_DATABASE} sql_is           : ${CLI_CYAN}Creates the database schema from your database. (importSchema)${END_COLOR}"
  "${OTRA_DATABASE} sql_if           : ${CLI_CYAN}Import the fixtures from database into config/data/yml/fixtures.${END_COLOR}"
  "${HELP_AND_TOOLS} help             : ${CLI_CYAN}Shows the extended help for the specified command.${END_COLOR}"
  "${HELP_AND_TOOLS} crypt            : ${CLI_CYAN}Crypts a password and shows it.${END_COLOR}"
  "${HELP_AND_TOOLS} hash             : ${CLI_CYAN}Returns a random hash.${END_COLOR}"
  "${HELP_AND_TOOLS} requirements     : ${CLI_CYAN}Shows the requirements to use OTRA at its maximum capabilities.${END_COLOR}"
  "${HELP_AND_TOOLS} routes           : ${CLI_CYAN}Shows the routes and their associated kind of resources in the case they have some. (lightGreen whether they exists, red otherwise)${END_COLOR}"
  "${HELP_AND_TOOLS} version          : ${CLI_CYAN}Shows the framework version.${END_COLOR}"
)

export OTRA_COMMANDS
export OTRA_COMMANDS_DESCRIPTIONS
