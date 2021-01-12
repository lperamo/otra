@echo off
doskey lp_crypt=php console.php crypt $*
doskey lp_ga=php console.php genAssets $*
doskey lp_gb=php console.php genBootstrap $*
doskey lp_gc=php console.php genClassmap $*
doskey lp_routes=php console.php routes $*
doskey lp_sc=php console.php sql_clean $*
doskey lp_sgbd=php console.php sql_gdb $*
doskey lp_sgf=php console.php sql_gf $*
echo Shortcuts created (lp_crypt, lp_ga, lp_gb, lp_gc, lp_routes, lp_sc, lp_sgdb, lp_sgf).
@echo on
