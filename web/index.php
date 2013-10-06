<?php
/** Bootstrap of the framework (redirection)
 *
 * @author Lionel Péramo */
session_start();
require ('Dev' == $_SESSION['debuglp_'] || (isset($_GET['debuglp_']) && 'Dev' == $_GET['debuglp_'])) ? '../lib/myLibs/core/Bootstrap_Dev.php' : '../lib/myLibs/core/Bootstrap_Prod.php'; ?>
