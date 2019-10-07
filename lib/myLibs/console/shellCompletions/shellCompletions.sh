#!/usr/bin/env bash
typeset CLI_BOLD_LIGHT_CYAN="\033[1;96m"
typeset CLI_WHITE="\033[0;38m"
typeset CLI_CYAN="\033[0;36m"
typeset END_COLOR="\033[0m"
typeset -a OTRA_COMMANDS=(
  'createbundle'
  'createModel'
  'cc'
  'deploy'
  'genAssets'
  'genBootstrap'
  'genClassMap'
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
  'routes'
  'version'
);

typeset -a OTRA_COMMANDS_DESCRIPTIONS=(
  "${CLI_BOLD_LIGHT_CYAN}[  Architecture  ]${CLI_WHITE} createbundle : ${CLI_CYAN}Creates a bundle. [ PARTIALLY IMPLEMENTED]${END_COLOR}"
  "${CLI_BOLD_LIGHT_CYAN}[  Architecture  ]${CLI_WHITE} createModel  : ${CLI_CYAN}Creates a model. ${END_COLOR}"
  "${CLI_BOLD_LIGHT_CYAN}[   Deployment   ]${CLI_WHITE} cc           : ${CLI_CYAN}Clears the cache ${END_COLOR}"
  "${CLI_BOLD_LIGHT_CYAN}[   Deployment   ]${CLI_WHITE} deploy       : ${CLI_CYAN}Deploy the site. [ WIP - Do not use yet ! ] ${END_COLOR}"
  "${CLI_BOLD_LIGHT_CYAN}[   Deployment   ]${CLI_WHITE} genAssets    : ${CLI_CYAN}Generates one css file and one js file that contain respectively all the minified css files and all the obfuscated minified js files. ${END_COLOR}"
  "${CLI_BOLD_LIGHT_CYAN}[   Deployment   ]${CLI_WHITE} genBootstrap : ${CLI_CYAN}Launch the genClassMap command and generates a file that contains all the necessary php files. ${END_COLOR}"
  "${CLI_BOLD_LIGHT_CYAN}[   Deployment   ]${CLI_WHITE} genClassMap  : ${CLI_CYAN}Generates a class mapping file that will be used to replace the autoloading method. ${END_COLOR}"
  "${CLI_BOLD_LIGHT_CYAN}[   Deployment   ]${CLI_WHITE} upConf       : ${CLI_CYAN}Updates the files related to bundles and routes. ${END_COLOR}"
  "${CLI_BOLD_LIGHT_CYAN}[    Database    ]${CLI_WHITE} sql          : ${CLI_CYAN}Executes the sql script${END_COLOR}"
  "${CLI_BOLD_LIGHT_CYAN}[    Database    ]${CLI_WHITE} sql_clean    : ${CLI_CYAN}Removes sql and yml files in the case where there are problems that had corrupted files.${END_COLOR}"
  "${CLI_BOLD_LIGHT_CYAN}[    Database    ]${CLI_WHITE} sql_gdb      : ${CLI_CYAN}Database creation, tables creation.(sql_generate_basic)${END_COLOR}"
  "${CLI_BOLD_LIGHT_CYAN}[    Database    ]${CLI_WHITE} sql_gf       : ${CLI_CYAN}Generates fixtures sql files and executes them. (sql_generate_fixtures${END_COLOR}"
  "${CLI_BOLD_LIGHT_CYAN}[    Database    ]${CLI_WHITE} sql_is       : ${CLI_CYAN}Creates the database schema from your database. (importSchema)${END_COLOR}"
  "${CLI_BOLD_LIGHT_CYAN}[    Database    ]${CLI_WHITE} sql_if       : ${CLI_CYAN}Import the fixtures from database into config/data/yml/fixtures.${END_COLOR}"
  "${CLI_BOLD_LIGHT_CYAN}[ Help and tools ]${CLI_WHITE} help         : ${CLI_CYAN}Shows the extended help for the specified command.${END_COLOR}"
  "${CLI_BOLD_LIGHT_CYAN}[ Help and tools ]${CLI_WHITE} crypt        : ${CLI_CYAN}Crypts a password and shows it.${END_COLOR}"
  "${CLI_BOLD_LIGHT_CYAN}[ Help and tools ]${CLI_WHITE} hash         : ${CLI_CYAN}Returns a random hash.${END_COLOR}"
  "${CLI_BOLD_LIGHT_CYAN}[ Help and tools ]${CLI_WHITE} routes       : ${CLI_CYAN}Shows the routes and their associated kind of resources in the case they have some. (lightGreen whether they exists, red otherwise)${END_COLOR}"
  "${CLI_BOLD_LIGHT_CYAN}[ Help and tools ]${CLI_WHITE} version      : ${CLI_CYAN}Shows the framework version.${END_COLOR}"
)

export OTRA_COMMANDS
export OTRA_COMMANDS_DESCRIPTIONS