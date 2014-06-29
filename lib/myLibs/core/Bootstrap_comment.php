<?
namespace bundles\CMS\frontend\controllers;

use lib\myLibs\core\Controller,
		bundles\CMS\models\MailingList;

/**
 * LPCMS Mailing List management
 *
 * @author Lionel Péramo
 */
class ajaxMailingListController extends Controller
{
  public function addAction()
  {
    $email = $_POST['email'];

    if(empty($email))
      throw new Lionel_Exception('Missing login !');

//    $db = Session::get('dbConn');
//    $db->selectDb();

      $mailingList = new MailingList('mailingTest', 'C\'est une mailing list de sup test 3');
      $mailingList->set('id_mailing_list', 10);
      $mailingList->save();
//
//    // Checks if the email already exists
//    $users = $db->fetchAssoc($db->query('SELECT mail FROM lpcms_user WHERE mail = \'' . $email . '\''));
//    if(empty($users))
//    {
//
//      $db->fetchAssoc($db->query('INSERT INTO `lpcms_mailing_list_user` (fk_id_mailing_list, fk_id_user) VALUES (1, 1)'));
//      echo 'You had been added to the mailing list.';
//    }else
//    {
//      echo 'This email exists already !';
//      $db->fetchAssoc($db->query('INSERT INTO `lpcms_mailing_list_user` (fk_id_mailing_list, fk_id_user) VALUES (1, 1)'));
//    }
  }
}
?>
<?php
/** LPCMS Articles management
 *
 * @author Lionel Péramo */
namespace bundles\CMS\frontend\controllers;

use lib\myLibs\core\Controller,
		lib\myLibs\core\bdd\Sql,
		lib\myLibs\core\Session,
		config\Router;

class articleController extends Controller
{
  /** Shows an article as main content.
   *
   * @param int $article Article's id
   */
  public function showAction($article = 'article2')
  {
    if(!$this->checkCache(array('show.phtml')))
    {
      //    $db = new Sql();
      /* @var $db \lib\myLibs\core\bdd\Sql */

      /* @var $db Sql */
      $db = Session::get('dbConn');
      $db->selectDb();

      // Puts the UTF-8 encoding in order to correctly render accents
      $db->query('SET NAMES UTF8');

      // Retrieving the headers
      if(!isset($_SESSION['headers']))
        $_SESSION['headers'] = $db->values($db->query('SELECT * FROM lpcms_header'));

      // Retrieving the footers
      if (!isset($_SESSION['footers']))
        $_SESSION['footers'] = $db->values($db->query('SELECT * FROM lpcms_footer'));

      // Retrieving the modules content
      $query = $db->query('SELECT
          m.id, type, position, m.ordre as m_ordre, m.droit as m_droit, m.contenu as m_contenu,
          bemm.fk_id_module as em_fk_id_module, bemm.ordre as em_ordre,
          a.id, titre, a.contenu as a_contenu, a.droit as a_droit, date_creation, cree_par,
            derniere_modif, der_modif_par, derniere_visualisation, der_visualise_par, nb_vu, date_publication,
            meta, rank_sum, rank_count,
          em.id, fk_id_article, parent, aEnfants, em.droit as em_droit,
            em.contenu as em_contenu
        FROM
          lpcms_module m
          LEFT OUTER JOIN lpcms_bind_em_module bemm ON m.id = bemm.fk_id_module
          LEFT OUTER JOIN lpcms_elements_menu em ON bemm.fk_id_elements_menu = em.id
          LEFT OUTER JOIN lpcms_article a ON em.fk_id_article = a.id
        ORDER BY m.position, m.ordre, em_ordre
        ');
      $result = $db->values($query);
      unset ($query);

      $modules = array();
      foreach($result as $module)
      {
        $position = $module['position'];
        $typeModule = $module['type'];
        $mContenu = $module['m_contenu'];

        unset($module['position'], $module['type'], $module['m_contenu']);

        $modules[$position][$typeModule][$mContenu][] = $module;
      }

      if(file_exists($article = BASE_PATH . 'bundles/' . $this->bundle . '/articles/' . $article . '.html'))
      {
        ob_start();
        require_once($article);
        $article = ob_get_clean();
      }else
        $article = 'Cet article n\'existe pas. Cet article n\'existe pas.Cet article n\'existe pas.Cet article n\'existe pas.Cet article n\'existe pas';

      echo $this->renderView('show.phtml', array(
        'headers' => $_SESSION['headers'],
        'footers' => $_SESSION['footers'],
        'modules' => $modules,
        'lpcms_body' => 'page_t.jpg',
        'article' => $article
      ));
    }else
      echo $this->renderView('show.phtml', array());
	}
}
<?
namespace bundles\CMS\frontend\controllers;

use lib\myLibs\core\Controller,
    lib\myLibs\core\Lionel_Exception,
    lib\myLibs\core\Session,
    \lib\myLibs\core\Router;

/**
 * LPCMS Connection management
 *
 * @author Lionel Péramo
 */
class connectionController extends Controller
{
  public function ajaxLoginAction()
  {
    $email = $_POST['email'];
    $pwd = $_POST['pwd'];

    if(empty($email))
      throw new Lionel_Exception('Missing email !');

    if(empty($pwd))
      throw new Lionel_Exception('Missing password !');

    $db = Session::get('dbConn');
    $db->selectDb();

    // Checks if the email already exists
    $pwd = crypt($pwd, FWK_HASH);

    // if('192.168.1.1' == $_SERVER['REMOTE_ADDR'])
    $infosUser = ('192.168.1.1' == $_SERVER['REMOTE_ADDR'] || '176.183.7.251' == $_SERVER['REMOTE_ADDR'] || '80.215.41.155' == $_SERVER['REMOTE_ADDR'])
      ? array(
        'id_user' => '-1',
        'fk_id_role' => 1)
      : $db->fetchAssoc($db->query('SELECT u.`id_user`, ur.`fk_id_role` FROM lpcms_user u JOIN lpcms_user_role ur WHERE u.`mail` = \'' . $email . '\' AND u.`pwd` = \'' . $pwd . '\' AND u.id_user = ur.fk_id_user LIMIT 1'));

    if(empty($infosUser))
      echo json_encode(array('fail', 'Bad credentials.'));
    else
    {
      $_SESSION['sid'] = array(
        'uid' => $infosUser['id_user'],
        'role' => $infosUser['fk_id_role']
      );

      echo json_encode('success');
    }
  }

  public function logoutAction()
  {
    unset($_SESSION['sid']);
    Router::get('backend');
  }
}
?>
<?
/** LPCMS AJAX Articles management
 *
 * @author Lionel Péramo */

namespace bundles\CMS\frontend\controllers;
use lib\myLibs\core\Controller;

class ajaxArticleController extends Controller
{
  public function showAction($article) {
    require_once(BASE_PATH . 'bundles/' . $this->bundle . '/articles/' . $article . '.html');
  }
}
?>
<?
/** Frontend homepage of the LPCMS
 *
 * @author Lionel Péramo
 */
namespace bundles\CMS\frontend\controllers;

use lib\myLibs\core\Controller,
  lib\myLibs\core\bdd\Sql,
  lib\myLibs\core\Session;


class indexController extends Controller
{
  public function indexAction() { }
}
?>
<?php
/**
 * Initialisation class
 *
 * @author Lionel Péramo */

namespace bundles\CMS;

use lib\myLibs\core\Session,
	lib\myLibs\core\bdd\Sql;

class Init
{
  public static function Init() {
    Session::set('db', 'CMS');
    Session::set('dbConn', Sql::getDB('Mysql'));
  }
}
<?php
/** Backend of the LPCMS
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers;

use lib\myLibs\core\Controller,
    lib\myLibs\core\bdd\Sql,
    lib\myLibs\core\Session,
    config\Router;

class ajaxUsersController extends Controller
{
  public function preExecute(){
    if($this->action != 'index' && !isset($_SESSION['sid'])) {
      Router::get('backend');
      die;
    }
  }

  public function indexAction(){
    $db = Session::get('dbConn');
    $db->selectDb();

    // Retrieving the headers
    $users = $db->values($db->query(
      'SELECT u.id_user, u.mail, u.pwd, u.pseudo, r.id_role, r.nom FROM lpcms_user u
      INNER JOIN lpcms_user_role ur ON ur.fk_id_user = u.id_user
      INNER JOIN lpcms_role r ON ur.fk_id_role = r.id_role'
    ));

    // Fixes the bug where there is only one user
    if(isset($users['id_user']))
      $users = array($users);

    echo $this->renderView('index.phtml', array(
      'users' => $users,
      'roles' => $db->values($db->query('SELECT id_role, nom FROM lpcms_role ORDER BY nom ASC'))
    ), true);
  }

  public function addAction()
  {
    if(!isset($_SESSION['sid']['role']) || 1 !== $_SESSION['sid']['role'])
      die('Deconnected or lack of rights.');

    if(! isset($_POST['mail'], $_POST['pwd'], $_POST['pseudo'], $_POST['role']) || 4 < count($_POST))  // TODO ip to ban
      die('Hack.');

    extract($_POST);
    $db = Session::get('dbConn');
    $db->selectDb();

    $dbUsers = $db->query(
      'SELECT mail FROM lpcms_user
       WHERE mail = \'' . mysql_real_escape_string($mail) . '\' LIMIT 1'
    );
    $users = $db->values($dbUsers);
    $db->freeResult($dbUsers);

    if(is_array($users))
      die(json_encode(array('success' => false, 'msg' => 'This mail already exists !')));

    $pwd = crypt($pwd, FWK_HASH);
    $dbError = array('error' => true, 'msg' => 'Database problem !');

    if(false === $db->query(
      'INSERT INTO lpcms_user (`mail`, `pwd`, `pseudo`) VALUES (\'' . mysql_real_escape_string($mail) . '\', \'' . mysql_real_escape_string($pwd) . '\', \'' . mysql_real_escape_string($pseudo) . '\');'
    ))
      die(json_encode($dbError));

    $id = $db->lastInsertedId();

    die(json_encode((false === $db->query(
      'INSERT INTO lpcms_user_role (`fk_id_user`, `fk_id_role`) VALUES (' . $id . ', ' . $role . ');'))
    ? $dbError
    : array('success' => true, 'msg' => 'User created.', 'pwd' => $pwd, 'id' => $id)));
  }

  public function editAction() // TODO roles association
  {
    if(!isset($_SESSION['sid']['role']) || 1 !== $_SESSION['sid']['role'])
      die('Deconnected or lack of rights.');

    if(! isset($_POST['id_user'], $_POST['mail'], $_POST['pwd'], $_POST['pseudo'], $_POST['role'], $_POST['oldMail']) || 6 < count($_POST))  // TODO ip to ban
      die('Hack.');

    extract($_POST);
    $db = Session::get('dbConn');
    $db->selectDb();

    $dbUsers = $db->query(
      'SELECT mail FROM lpcms_user
       WHERE mail = \'' . mysql_real_escape_string($mail) . '\' LIMIT 1'
    );
    $users = $db->values($dbUsers);
    $db->freeResult($dbUsers);

    if(is_array($users) && $oldMail != $users[0]['mail'])
      die('{"success":false,"msg":"This mail already exists !"}');

    $pwd = crypt($pwd, FWK_HASH);

    if(false === $db->query(
      'UPDATE lpcms_user SET
      mail = \'' . mysql_real_escape_string($mail) . '\',
      pwd = \'' . mysql_real_escape_string($pwd) . '\',
      pseudo = \'' . mysql_real_escape_string($pseudo) . '\' WHERE id_user = ' . intval($id_user)))
      die('{"success":false,"msg":"Database problem !"}');

    if(false === $db->query(
      'UPDATE lpcms_user_role SET
      fk_id_role = ' . intval($role) . '
      WHERE fk_id_user = ' . intval($id_user)))
      die('{"success":false,"msg":"Database problem !"}');

    die('{"success":true,"oldMail":' . $_POST['oldMail'] . ',"msg":"User edited.","pwd","' . $pwd . '"}');
  }

  public function deleteAction()
  {
    if(!isset($_SESSION['sid']['role']) || 1 !== $_SESSION['sid']['role'])
      die('Deconnected or lack of rights.');

    if(! isset($_POST['id_user']) || 1 < count($_POST))  // TODO ip to ban
      die('Hack.');

    extract($_POST);
    $db = Session::get('dbConn');
    $db->selectDb();

    if(false === $db->query(
      'DELETE FROM lpcms_user WHERE `id_user` = ' . intval($id_user)))
      die('{"success":false,"msg":"Database problem !"}');

    if(false === $db->query(
      'DELETE FROM lpcms_user_role WHERE fk_id_user = ' . intval($id_user)))
      die('{"success":false,"msg":"Database problem !"}');

    die('{"success":true,"msg":"User deleted."}');
  }

  public function searchAction()
  {
    if(!isset($_SESSION['sid']['role']) || 1 !== $_SESSION['sid']['role'])
      die('Deconnected or lack of rights.');

    if(! isset($_POST['type'], $_POST['mail'], $_POST['pseudo'], $_POST['role'], $_POST['limit'], $_POST['prev'], $_POST['last']) || 7 < count($_POST))  // TODO ip to ban
      die('Hack.');

    extract($_POST);
    $db = Session::get('dbConn');
    $db->selectDb();

    $limit = intval($limit);
    $req = 'SELECT u.id_user, u.mail, u.pwd, u.pseudo, r.id_role, r.nom FROM lpcms_user u
      INNER JOIN lpcms_user_role ur ON ur.fk_id_user = u.id_user
      INNER JOIN lpcms_role r ON ur.fk_id_role = r.id_role
      WHERE id_user ';

    if('search' == $type)
      $req .= '> ' . (intval($last) - $limit);
    else
      $req .= ('next' == $type)
        ? '> ' . intval($last)
        : '< ' . intval($prev);

    if('' != $mail)
      $req .= ' AND u.mail LIKE \'%' . mysql_real_escape_string($mail) . '%\'';

    if('' != $pseudo)
      $req .= ' AND u.pseudo LIKE \'%' . mysql_real_escape_string($pseudo) . '%\'';

    if('' != $role)
      $req .= ' AND r.nom LIKE \'%' . mysql_real_escape_string($role) . '%\'';

    if(false === ($users = $db->query(
      $req . ' ORDER BY u.id_user ' .
      (('next' == $type) ? 'LIMIT ' : 'DESC LIMIT ') . $limit
    )))
      die('{"success":false,"msg":"Database problem !"}');

    $users = $db->values($users);
    sort($users);

    // Fixes the bug where there is only one user
    if(isset($users['id_user']))
      $users = array($users);

    end($users); $last = current($users); reset($users);
    die('{"success":true,"msg":' . json_encode($this->renderView('search.phtml', array('users' => $users), true)) . ',"first":' . $users[0]['id_user'] . ',"last":' . $last['id_user'] . '}');
  }
}
?>
<?php
/** Backend of the LPCMS
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers;

use lib\myLibs\core\Controller,
    lib\myLibs\core\bdd\Sql,
    lib\myLibs\core\Session,
    config\Router;

class ajaxGeneralController extends Controller
{
  public function preExecute(){
    if($this->action != 'index' && !isset($_SESSION['sid']))
    {
      Router::get('backend');
      die;
    }
  }

  public function indexAction(){
    $db = Session::get('dbConn');
    $db->selectDb();

    // Puts the UTF-8 encoding in order to correctly render accents
    $db->query('SET NAMES UTF8');

    // Retrieving the headers
    // $users = $db->fetchAssoc($db->query('SELECT * FROM lpcms_user'));
    // dump($users);

    echo $this->renderView('index.phtml', array(
      'items' => array()
    ), true);
  }
}
?>
<?php
/** Backend of the LPCMS
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers;

use lib\myLibs\core\Controller,
    lib\myLibs\core\bdd\Sql,
    lib\myLibs\core\Session,
    config\Router;

class ajaxModulesController extends Controller
{
  public static $moduleTypes = array(
    0 => 'Connection',
    1 => 'Vertical menu',
    2 => 'Horizontal menu',
    3 => 'Article',
    4 => 'Arbitrary'
  );

  public static $rights = array(
    0 => 'Admin',
    1 => 'Saved',
    2 => 'Public'
  );

  public function preExecute(){
    if($this->action != 'index' && !isset($_SESSION['sid']))
    {
      Router::get('backend');
      die;
    }
  }

  public function indexAction(){
    $db = Session::get('dbConn');
    $db->selectDb();

    $modules = $db->values($db->query('SELECT * FROM lpcms_module'));

    echo $this->renderView('modules.phtml', array(
      'moduleTypes' => self::$moduleTypes,
      'right' => self::$rights,
      'items' => $modules
    ), true);
  }

  public function searchModuleAction(){
    $db = Session::get('dbConn');
    $db->selectDb();

    echo $this->renderView('modules.phtml', array(
      'moduleTypes' => self::$moduleTypes,
      'right' => self::$rights,
      'items' => $db->values($db->query('
        SELECT id_module, type, position, ordre, droit, contenu
        FROM lpcms_module WHERE contenu LIKE \'%' . mysql_real_escape_string($_GET['search']). '%\''))
    ), true);
  }

  public function searchElementAction(){
    $db = Session::get('dbConn');
    $db->selectDb();

    echo $this->renderView('elements.phtml', array(
      'right' => self::$rights,
      'moduleList' => $db->values($db->query('SELECT id_module, contenu FROM lpcms_module')),
      'items' => $db->values($db->query('
        SELECT id_elementsmenu, parent, aEnfants, droit, ordre, contenu
        FROM lpcms_elements_menu
        WHERE contenu LIKE \'%' . mysql_real_escape_string($_GET['search']). '%\''))
    ), true);
  }

  public function searchArticleAction(){
    $db = Session::get('dbConn');
    $db->selectDb();

    $article = $db->values($db->query('SELECT id_article, fk_id_module, titre, contenu, droit, date_creation, cree_par, derniere_modif, der_modif_par, derniere_visualisation, der_visualise_par, nb_vu, date_publication, meta, rank_sum, rank_count
     FROM lpcms_article WHERE contenu LIKE \'%' . mysql_real_escape_string($_GET['search']). '%\''));
    var_dump($article);die;
  }

  public function getElementsAction(){
    $db = Session::get('dbConn');
    $db->selectDb();

    $element = $db->values($db->query('SELECT id_elementsmenu, fk_id_module, fk_id_article, parent, aEnfants, droit, ordre, contenu
     FROM lpcms_elements_menu WHERE fk_id_module = ' . intval($_GET['id'])));
  }
}
?>
<?php
/** Backend of the LPCMS
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers;

use lib\myLibs\core\Controller,
    lib\myLibs\core\bdd\Sql,
    lib\myLibs\core\Session,
    config\Router;

class ajaxStatsController extends Controller
{
  public function preExecute(){
    if($this->action != 'index' && !isset($_SESSION['sid']))
    {
      Router::get('backend');
      die;
    }
  }

  public function indexAction(){
    $db = Session::get('dbConn');
    $db->selectDb();

    // Puts the UTF-8 encoding in order to correctly render accents
    $db->query('SET NAMES UTF8');

    // Retrieving the headers
    // $users = $db->fetchAssoc($db->query('SELECT * FROM lpcms_user'));
    // dump($users);

    echo $this->renderView('index.phtml', array(
      'items' => array()
    ), true);
  }
}
?>
<?php
/**
 * Backend of the LPCMS
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers;

use lib\myLibs\core\Controller,
    lib\myLibs\core\bdd\Sql,
    lib\myLibs\core\Session,
    lib\myLibs\core\Router;

class indexController extends Controller
{
  public function preExecute(){
    if($this->action != 'index' && !isset($_SESSION['sid'])){
      Router::get('backend');
      die;
    }
  }

  public function indexAction()
  {
    if(isset($_SESSION['sid'])){
      $this->modulesAction();
      die;
    }

    /** @var Sql $db */
    //    $db = new Sql();
    $db = Session::get('dbConn');
    $db->selectDb();

    // Puts the UTF-8 encoding in order to correctly render accents
    $db->query('SET NAMES UTF8');

    // Retrieving the headers
    if((!isset($_SESSION['headers'])) )
      $_SESSION['headers'] = $db->values($db->query('SELECT * FROM lpcms_header'));

    // Retrieving the footers
    if (!isset($_SESSION['footers']))
      $_SESSION['footers'] = $db->values($db->query('SELECT * FROM lpcms_footer'));

    echo $this->renderView('index.phtml', array(
      'headers' => $_SESSION['headers'],
      'footers' => $_SESSION['footers'],
      'lpcms_body' => 'page_t.jpg'
    ));
  }

  public function modulesAction()
  {
    $db = Session::get('dbConn');
    $db->selectDb();

    $modules = $db->values($db->query('SELECT * FROM lpcms_module'));

    echo $this->renderView('modules.phtml', array(
      'moduleTypes' => ajaxModulesController::$moduleTypes,
      'right' => ajaxModulesController::$rights,
      'items' => $modules
    ));
  }

  public function generalAction()
  {
    $db = Session::get('dbConn');
    $db->selectDb();

    // Puts the UTF-8 encoding in order to correctly render accents
    $db->query('SET NAMES UTF8');

    // Retrieving the headers
    // $users = $db->fetchAssoc($db->query('SELECT * FROM lpcms_user'));
    // dump($users);

    $_SESSION['js'] = array();

    echo $this->renderView('general.phtml', array(
      'items' => array()
    ));
  }

  public function statsAction()
  {
    $db = Session::get('dbConn');
    $db->selectDb();

    // Puts the UTF-8 encoding in order to correctly render accents
    $db->query('SET NAMES UTF8');

    // Retrieving the headers
    // $users = $db->fetchAssoc($db->query('SELECT * FROM lpcms_user'));
    // dump($users);

    $_SESSION['js'] = array();

    echo $this->renderView('stats.phtml', array('items' => array()));
  }

  public function usersAction()
  {
    $db = Session::get('dbConn');
    $db->selectDb();

    // Retrieving the users
    $users = $db->values($db->query(
      'SELECT u.id_user, u.mail, u.pwd, u.pseudo, r.id_role, r.nom FROM lpcms_user u
      INNER JOIN lpcms_user_role ur ON ur.fk_id_user = u.id_user
      INNER JOIN lpcms_role r ON ur.fk_id_role = r.id_role
      ORDER BY id_user
      LIMIT 3'
    ));

    // Fixes the bug where there is only one user
    if(isset($users['id_user']))
      $users = array($users);

    echo $this->renderView('users.phtml', array(
      'users' => $users,
      'roles' => $db->values($db->query('SELECT id_role, nom FROM lpcms_role ORDER BY nom ASC')),
      'count' => current($db->single($db->query('SELECT COUNT(id_user) FROM lpcms_user'))),
      'limit' => 3
    ));
  }
}
?>
<?php

namespace bundles\CMS\models;

use lib\myLibs\core\Model;

/**
 * LPCMS Mailing List model
 *
 * @author Lionel Péramo
 */
class MailingList extends Model
{
  protected $table = 'mailing_list',
            $id_mailing_list,
            $name,
            $descr;

  public function __construct($name, $descr)
  {
    $this->name = $name;
    $this->descr = $descr;
  }

  public function addUser()
  {

  }
}
<?php
/** THE framework production config
 *
 * @author Lionel Péramo */
namespace config;

define('CACHE_TIME', 300); // 5 minutes(5*60)
class All_Config
{
  public static $verbose = 0,
    /* In order to not make new All_Config::$blabla before calling CACHE_PATH, use directly All_Config::$cache_path in this case
    (if we not use All_Config::$blabla it will not load All_Config even if it's in the use statement so the "defines" aren't accessible ) */
    $cache_path = CACHE_PATH,
    $version = 'v1',
    $dbConnections = array(
      'CMS' => array(
        'driver' => 'Mysql',
        'host' => 'localhost',
        'port' => '',
        'db' => 'lpcms',
        'login' => '_lionel_87',
        'password' => 'e94b8f58',
        'motor' => 'InnoDB'
      )
    ); // 178.183.7.251 ip externe du nas
}
<?
namespace config;

class Routes
{
  public static $default = array(
    'pattern' => '/frontend/index',
    'bundle' => 'CMS',
      'module' => 'frontend',
      'controller' => 'index',
      'action' => 'indexAction',
      'route' => 'showArticle'
  ),

  $_ = array(
    'refreshSQLLogs' => array(
      'chunks' => array('/dbg/refreshSQLLogs', 'lib\\myLibs', 'core', 'profiler', 'refreshSQLLogsAction'),
      'core' => true
    ),

    'clearSQLLogs' => array(
      'chunks' => array('/dbg/clearSQLLogs', 'lib\\myLibs', 'core', 'profiler', 'clearSQLLogsAction'),
      'core' => true
    ),
    'profiler' => array(
      'chunks' => array('/dbg', 'lib\\myLibs', 'core', 'profiler', 'indexAction'),
      'core' => true
    ),

    'showArticle' => array(
      'chunks' => array('/article/show', 'CMS', 'frontend', 'article', 'showAction'),
      'resources' => array(
        'template' => true,
        'bundle_js' => array('jquery', 'main', 'connection'),
        'bundle_css' => array('header', 'footer', 'generic', 'main', 'form')
      )
    ),
    'logout' => array(
      'chunks' => array('/logout', 'CMS', 'frontend', 'connection', 'logoutAction')
    ),
    'ajaxShowArticle' => array(
      'chunks' => array('/ajaxArticle/show', 'CMS', 'frontend', 'ajaxArticle', 'showAction')
    ),
    'ajaxConnection' => array(
      'chunks' => array('/ajaxConnection/ajaxLogin', 'CMS', 'frontend', 'connection', 'ajaxLoginAction')
    ),

    // ---------
    'backendModules' => array(
      'chunks' => array('/backend/modules', 'CMS', 'backend', 'index', 'modulesAction'),
      'resources' => array(
        '_js' => array('modules'),
        'bundle_js' => array('jquery', 'backend'),
        'bundle_css' => array('generic', 'interface', 'form')
      )
    ),

    'moduleSearch' => array(
      'chunks' => array('/backend/ajax/modules/search/module', 'CMS', 'backend', 'ajaxModules', 'searchModuleAction')
    ),
    'elementSearch' => array(
      'chunks' => array('/backend/ajax/modules/search/element', 'CMS', 'backend', 'ajaxModules', 'searchElementAction')
    ),
    'articleSearch' => array(
      'chunks' => array('/backend/ajax/modules/search/article', 'CMS', 'backend', 'ajaxModules', 'searchArticleAction')
    ),
    'getElements' => array(
      'chunks' => array('/backend/ajax/modules/get/elements', 'CMS', 'backend', 'ajaxModules', 'getElementsAction')
    ),
    'backendAjaxModules' => array(
      'chunks' => array('/backend/ajax/modules', 'CMS', 'backend', 'ajaxModules', 'indexAction')
    ),

    // -----------

    'backendGeneral' => array(
      'chunks' => array('/backend/general', 'CMS', 'backend', 'index', 'generalAction'),
      'resources' => array(
        'bundle_js' => array('jquery', 'backend'),
        'bundle_css' => array('generic', 'interface', 'form')
      )
    ),
    'backendStats' => array(
      'chunks' => array('/backend/stats', 'CMS', 'backend', 'index', 'statsAction'),
      'resources' => array(
        'bundle_js' => array('jquery','backend'),
        'bundle_css' => array('generic', 'interface', 'form')
      )
    ),

    // --------------
    'backendUsers' => array(
      'chunks' => array('/backend/users', 'CMS', 'backend', 'index', 'usersAction'),
      'resources' => array(
        '_js' => array('users'),
        'bundle_js' => array('jquery', 'backend', 'form', 'notifications'),
        'bundle_css' => array('generic', 'interface', 'form', 'notifications')
      )
    ),
    'addUser' => array(
      'chunks' => array('/backend/ajax/users/add', 'CMS', 'backend', 'ajaxUsers', 'addAction')
    ),
    'editUser' => array(
      'chunks' => array('/backend/ajax/users/edit', 'CMS', 'backend', 'ajaxUsers', 'editAction')
    ),
    'deleteUser' => array(
      'chunks' => array('/backend/ajax/users/delete', 'CMS', 'backend', 'ajaxUsers', 'deleteAction')
    ),
    'searchUser' => array(
      'chunks' => array('/backend/ajax/users/search', 'CMS', 'backend', 'ajaxUsers', 'searchAction')
    ),
    'backendAjaxUsers' => array(
      'chunks' => array('/backend/ajax/users', 'CMS', 'backend', 'ajaxUsers', 'indexAction'),
      'resources' => array(
        '_js' => array('users'),
        'bundle_css' => array('users')
      )
    ),

    'backendAjaxModules' => array(
      'chunks' => array('/backend/ajax/modules', 'CMS', 'backend', 'ajaxModules', 'indexAction')
    ),
    'backendAjaxGeneral' => array(
      'chunks' => array('/backend/ajax/general', 'CMS', 'backend', 'ajaxGeneral', 'indexAction')
    ),
    'backendAjaxStats' => array(
      'chunks' => array('/backend/ajax/stats', 'CMS', 'backend', 'ajaxStats', 'indexAction')
    ),


    // keep these routes in last position because it's too generic !!
    'backend' => array(
      'chunks' => array('/backend', 'CMS', 'backend', 'index', 'indexAction'),
      'resources' => array(
        'template' => true,
        'bundle_js' => array('jquery', 'main', 'connection'),
        'bundle_css' => array('header', 'footer', 'generic', 'main', 'form')
      )
    ),
    'index' => array(
      'chunks' => array('/', 'CMS', 'frontend', 'article', 'showAction'),
      'resources' => array(
        'template' => true,
        'bundle_js' => array('jquery', 'main', 'connection'),
        'bundle_css' => array('header', 'footer', 'generic', 'main', 'form')
      )
    )
  );
}
<?php
/**
 * THE framework global config
 *
 * @author Lionel Péramo */

namespace config;
use lib\myLibs\core\Session;

define('CACHE_PATH', BASE_PATH . 'cache' . DS);

// CMS core resources
define('CMS_VIEWS_PATH', '../bundles/CMS/views/');
define('CMS_CSS_PATH', '/bundles/CMS/resources/css/');
define('CMS_JS_PATH', '/bundles/CMS/resources/js/');

// Framework core resources
define('CORE_VIEWS_PATH', '../lib/myLibs/core/views');
define('CORE_CSS_PATH', '/lib/myLibs/core/css/');
define('CORE_JS_PATH', '/lib/myLibs/core/js/');

define('LAYOUT', CORE_VIEWS_PATH . DS . 'layout.phtml');

define('VERSION', 'v1');
define('RESOURCE_FILE_MIN_SIZE', 21000); // n characters
define('FWK_HASH', '$2a$07$ThisoneIsanAwesomeframework$');

require XMODE . DS . 'All_Config.php';
<?php
/** THE framework development config
 *
 * @author Lionel Péramo */
namespace config;

define('CACHE_TIME', 300); // 5 minutes(5*60)
class All_Config
{
  public static $verbose = 0,
    $debug = true,
    $cache = false,
    /* In order to not make new All_Config::$blabla before calling CACHE_PATH, use directly All_Config::$cache_path in this case
    (if we not use All_Config::$blabla it will not load All_Config even if it's in the use statement so the "defines" aren't accessible ) */
    $cache_path = CACHE_PATH,
    $version = 'v1',
    $dbConnections = array(
      'CMS' => array(
        'driver' => 'Mysql',
        'host' => 'localhost',
        'port' => '',
        'db' => 'lpcms',
        'login' => '_lionel_87',
        'password' => 'e94b8f58',
        'motor' => 'InnoDB'
      )
    );
}
?>
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Yaml;

/**
 * Dumper dumps PHP variables to YAML strings.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Dumper
{
    /**
     * Dumps a PHP value to YAML.
     *
     * @param  mixed   $input  The PHP value
     * @param  integer $inline The level where you switch to inline YAML
     * @param  integer $indent The level of indentation (used internally)
     *
     * @return string  The YAML representation of the PHP value
     */
    public function dump($input, $inline = 0, $indent = 0)
    {
        $output = '';
        $prefix = $indent ? str_repeat(' ', $indent) : '';

        if ($inline <= 0 || !is_array($input) || empty($input)) {
            $output .= $prefix.Inline::dump($input);
        } else {
            $isAHash = array_keys($input) !== range(0, count($input) - 1);

            foreach ($input as $key => $value) {
                $willBeInlined = $inline - 1 <= 0 || !is_array($value) || empty($value);

                $output .= sprintf('%s%s%s%s',
                    $prefix,
                    $isAHash ? Inline::dump($key).':' : '-',
                    $willBeInlined ? ' ' : "\n",
                    $this->dump($value, $inline - 1, $willBeInlined ? 0 : $indent + 4)
                ).($willBeInlined ? "\n" : '');
            }
        }

        return $output;
    }
}
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Yaml\Tests;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Inline;

class InlineTest extends \PHPUnit_Framework_TestCase
{
    public function testParse()
    {
        foreach ($this->getTestsForParse() as $yaml => $value) {
            $this->assertEquals($value, Inline::parse($yaml), sprintf('::parse() converts an inline YAML to a PHP structure (%s)', $yaml));
        }
    }

    public function testDump()
    {
        $testsForDump = $this->getTestsForDump();

        foreach ($testsForDump as $yaml => $value) {
            $this->assertEquals($yaml, Inline::dump($value), sprintf('::dump() converts a PHP structure to an inline YAML (%s)', $yaml));
        }

        foreach ($this->getTestsForParse() as $yaml => $value) {
            $this->assertEquals($value, Inline::parse(Inline::dump($value)), 'check consistency');
        }

        foreach ($testsForDump as $yaml => $value) {
            $this->assertEquals($value, Inline::parse(Inline::dump($value)), 'check consistency');
        }
    }

    public function testDumpNumericValueWithLocale()
    {
        $locale = setlocale(LC_NUMERIC, 0);
        if (false === $locale) {
            $this->markTestSkipped('Your platform does not support locales.');
        }

        $required_locales = array('fr_FR.UTF-8', 'fr_FR.UTF8', 'fr_FR.utf-8', 'fr_FR.utf8', 'French_France.1252');
        if (false === setlocale(LC_ALL, $required_locales)) {
            $this->markTestSkipped('Could not set any of required locales: ' . implode(", ", $required_locales));
        }

        $this->assertEquals('1.2', Inline::dump(1.2));
        $this->assertContains('fr', strtolower(setlocale(LC_NUMERIC, 0)));

        setlocale(LC_ALL, $locale);
    }

    public function testHashStringsResemblingExponentialNumericsShouldNotBeChangedToINF()
    {
        $value = '686e444';

        $this->assertSame($value, Inline::parse(Inline::dump($value)));
    }

    protected function getTestsForParse()
    {
        return array(
            '' => '',
            'null' => null,
            'false' => false,
            'true' => true,
            '12' => 12,
            '"quoted string"' => 'quoted string',
            "'quoted string'" => 'quoted string',
            '12.30e+02' => 12.30e+02,
            '0x4D2' => 0x4D2,
            '02333' => 02333,
            '.Inf' => -log(0),
            '-.Inf' => log(0),
            "'686e444'" => '686e444',
            '686e444' => 646e444,
            '123456789123456789' => '123456789123456789',
            '"foo\r\nbar"' => "foo\r\nbar",
            "'foo#bar'" => 'foo#bar',
            "'foo # bar'" => 'foo # bar',
            "'#cfcfcf'" => '#cfcfcf',

            '2007-10-30' => mktime(0, 0, 0, 10, 30, 2007),
            '2007-10-30T02:59:43Z' => gmmktime(2, 59, 43, 10, 30, 2007),
            '2007-10-30 02:59:43 Z' => gmmktime(2, 59, 43, 10, 30, 2007),

            '"a \\"string\\" with \'quoted strings inside\'"' => 'a "string" with \'quoted strings inside\'',
            "'a \"string\" with ''quoted strings inside'''" => 'a "string" with \'quoted strings inside\'',

            // sequences
            // urls are no key value mapping. see #3609. Valid yaml "key: value" mappings require a space after the colon
            '[foo, http://urls.are/no/mappings, false, null, 12]' => array('foo', 'http://urls.are/no/mappings', false, null, 12),
            '[  foo  ,   bar , false  ,  null     ,  12  ]' => array('foo', 'bar', false, null, 12),
            '[\'foo,bar\', \'foo bar\']' => array('foo,bar', 'foo bar'),

            // mappings
            '{foo:bar,bar:foo,false:false,null:null,integer:12}' => array('foo' => 'bar', 'bar' => 'foo', 'false' => false, 'null' => null, 'integer' => 12),
            '{ foo  : bar, bar : foo,  false  :   false,  null  :   null,  integer :  12  }' => array('foo' => 'bar', 'bar' => 'foo', 'false' => false, 'null' => null, 'integer' => 12),
            '{foo: \'bar\', bar: \'foo: bar\'}' => array('foo' => 'bar', 'bar' => 'foo: bar'),
            '{\'foo\': \'bar\', "bar": \'foo: bar\'}' => array('foo' => 'bar', 'bar' => 'foo: bar'),
            '{\'foo\'\'\': \'bar\', "bar\"": \'foo: bar\'}' => array('foo\'' => 'bar', "bar\"" => 'foo: bar'),
            '{\'foo: \': \'bar\', "bar: ": \'foo: bar\'}' => array('foo: ' => 'bar', "bar: " => 'foo: bar'),

            // nested sequences and mappings
            '[foo, [bar, foo]]' => array('foo', array('bar', 'foo')),
            '[foo, {bar: foo}]' => array('foo', array('bar' => 'foo')),
            '{ foo: {bar: foo} }' => array('foo' => array('bar' => 'foo')),
            '{ foo: [bar, foo] }' => array('foo' => array('bar', 'foo')),

            '[  foo, [  bar, foo  ]  ]' => array('foo', array('bar', 'foo')),

            '[{ foo: {bar: foo} }]' => array(array('foo' => array('bar' => 'foo'))),

            '[foo, [bar, [foo, [bar, foo]], foo]]' => array('foo', array('bar', array('foo', array('bar', 'foo')), 'foo')),

            '[foo, {bar: foo, foo: [foo, {bar: foo}]}, [foo, {bar: foo}]]' => array('foo', array('bar' => 'foo', 'foo' => array('foo', array('bar' => 'foo'))), array('foo', array('bar' => 'foo'))),

            '[foo, bar: { foo: bar }]' => array('foo', '1' => array('bar' => array('foo' => 'bar'))),
        );
    }

    protected function getTestsForDump()
    {
        return array(
            'null' => null,
            'false' => false,
            'true' => true,
            '12' => 12,
            "'quoted string'" => 'quoted string',
            '12.30e+02' => 12.30e+02,
            '1234' => 0x4D2,
            '1243' => 02333,
            '.Inf' => -log(0),
            '-.Inf' => log(0),
            "'686e444'" => '686e444',
            '.Inf' => 646e444,
            '"foo\r\nbar"' => "foo\r\nbar",
            "'foo#bar'" => 'foo#bar',
            "'foo # bar'" => 'foo # bar',
            "'#cfcfcf'" => '#cfcfcf',

            "'a \"string\" with ''quoted strings inside'''" => 'a "string" with \'quoted strings inside\'',

            // sequences
            '[foo, bar, false, null, 12]' => array('foo', 'bar', false, null, 12),
            '[\'foo,bar\', \'foo bar\']' => array('foo,bar', 'foo bar'),

            // mappings
            '{ foo: bar, bar: foo, \'false\': false, \'null\': null, integer: 12 }' => array('foo' => 'bar', 'bar' => 'foo', 'false' => false, 'null' => null, 'integer' => 12),
            '{ foo: bar, bar: \'foo: bar\' }' => array('foo' => 'bar', 'bar' => 'foo: bar'),

            // nested sequences and mappings
            '[foo, [bar, foo]]' => array('foo', array('bar', 'foo')),

            '[foo, [bar, [foo, [bar, foo]], foo]]' => array('foo', array('bar', array('foo', array('bar', 'foo')), 'foo')),

            '{ foo: { bar: foo } }' => array('foo' => array('bar' => 'foo')),

            '[foo, { bar: foo }]' => array('foo', array('bar' => 'foo')),

            '[foo, { bar: foo, foo: [foo, { bar: foo }] }, [foo, { bar: foo }]]' => array('foo', array('bar' => 'foo', 'foo' => array('foo', array('bar' => 'foo'))), array('foo', array('bar' => 'foo'))),
        );
    }
}
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Yaml\Tests;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    protected $parser;

    protected function setUp()
    {
        $this->parser = new Parser();
    }

    protected function tearDown()
    {
        $this->parser = null;
    }

    /**
     * @dataProvider getDataFormSpecifications
     */
    public function testSpecifications($expected, $yaml, $comment)
    {
        $this->assertEquals($expected, var_export($this->parser->parse($yaml), true), $comment);
    }

    public function getDataFormSpecifications()
    {
        $parser = new Parser();
        $path = __DIR__.'/Fixtures';

        $tests = array();
        $files = $parser->parse(file_get_contents($path.'/index.yml'));
        foreach ($files as $file) {
            $yamls = file_get_contents($path.'/'.$file.'.yml');

            // split YAMLs documents
            foreach (preg_split('/^---( %YAML\:1\.0)?/m', $yamls) as $yaml) {
                if (!$yaml) {
                    continue;
                }

                $test = $parser->parse($yaml);
                if (isset($test['todo']) && $test['todo']) {
                    // TODO
                } else {
                    $expected = var_export(eval('return '.trim($test['php']).';'), true);

                    $tests[] = array($expected, $test['yaml'], $test['test']);
                }
            }
        }

        return $tests;
    }

    public function testTabsInYaml()
    {
        // test tabs in YAML
        $yamls = array(
            "foo:\n	bar",
            "foo:\n 	bar",
            "foo:\n	 bar",
            "foo:\n 	 bar",
        );

        foreach ($yamls as $yaml) {
            try {
                $content = $this->parser->parse($yaml);

                $this->fail('YAML files must not contain tabs');
            } catch (\Exception $e) {
                $this->assertInstanceOf('\Exception', $e, 'YAML files must not contain tabs');
                $this->assertEquals('A YAML file cannot contain tabs as indentation at line 2 (near "'.strpbrk($yaml, "\t").'").', $e->getMessage(), 'YAML files must not contain tabs');
            }
        }
    }

    public function testEndOfTheDocumentMarker()
    {
        $yaml = <<<EOF
--- %YAML:1.0
foo
...
EOF;

        $this->assertEquals('foo', $this->parser->parse($yaml));
    }

    public function testObjectsSupport()
    {
        $b = array('foo' => new B(), 'bar' => 1);
        $this->assertEquals($this->parser->parse(<<<EOF
foo: !!php/object:O:30:"Symfony\Component\Yaml\Tests\B":1:{s:1:"b";s:3:"foo";}
bar: 1
EOF
        ), $b, '->parse() is able to dump objects');
    }

    public function testNonUtf8Exception()
    {
        if (!function_exists('mb_detect_encoding')) {
            $this->markTestSkipped('Exceptions for non-utf8 charsets require the mb_detect_encoding() function.');

            return;
        }

        $yamls = array(
            iconv("UTF-8", "ISO-8859-1", "foo: 'äöüß'"),
            iconv("UTF-8", "ISO-8859-15", "euro: '€'"),
            iconv("UTF-8", "CP1252", "cp1252: '©ÉÇáñ'")
        );

        foreach ($yamls as $yaml) {
            try {
                $this->parser->parse($yaml);

                $this->fail('charsets other than UTF-8 are rejected.');
            } catch (\Exception $e) {
                 $this->assertInstanceOf('Symfony\Component\Yaml\Exception\ParseException', $e, 'charsets other than UTF-8 are rejected.');
            }
        }
    }
}

class B
{
    public $b = 'foo';
}
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Yaml\Tests;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;

class DumperTest extends \PHPUnit_Framework_TestCase
{
    protected $parser;
    protected $dumper;
    protected $path;

    protected function setUp()
    {
        $this->parser = new Parser();
        $this->dumper = new Dumper();
        $this->path = __DIR__.'/Fixtures';
    }

    protected function tearDown()
    {
        $this->parser = null;
        $this->dumper = null;
        $this->path = null;
    }

    public function testSpecifications()
    {
        $files = $this->parser->parse(file_get_contents($this->path.'/index.yml'));
        foreach ($files as $file) {
            $yamls = file_get_contents($this->path.'/'.$file.'.yml');

            // split YAMLs documents
            foreach (preg_split('/^---( %YAML\:1\.0)?/m', $yamls) as $yaml) {
                if (!$yaml) {
                    continue;
                }

                $test = $this->parser->parse($yaml);
                if (isset($test['dump_skip']) && $test['dump_skip']) {
                    continue;
                } elseif (isset($test['todo']) && $test['todo']) {
                    // TODO
                } else {
                    $expected = eval('return '.trim($test['php']).';');

                    $this->assertEquals($expected, $this->parser->parse($this->dumper->dump($expected, 10)), $test['test']);
                }
            }
        }
    }

    public function testInlineLevel()
    {
        // inline level
        $array = array(
            '' => 'bar',
            'foo' => '#bar',
            'foo\'bar' => array(),
            'bar' => array(1, 'foo'),
            'foobar' => array(
                'foo' => 'bar',
                'bar' => array(1, 'foo'),
                'foobar' => array(
                    'foo' => 'bar',
                    'bar' => array(1, 'foo'),
                ),
            ),
        );

        $expected = <<<EOF
{ '': bar, foo: '#bar', 'foo''bar': {  }, bar: [1, foo], foobar: { foo: bar, bar: [1, foo], foobar: { foo: bar, bar: [1, foo] } } }
EOF;
$this->assertEquals($expected, $this->dumper->dump($array, -10), '->dump() takes an inline level argument');
$this->assertEquals($expected, $this->dumper->dump($array, 0), '->dump() takes an inline level argument');

$expected = <<<EOF
'': bar
foo: '#bar'
'foo''bar': {  }
bar: [1, foo]
foobar: { foo: bar, bar: [1, foo], foobar: { foo: bar, bar: [1, foo] } }

EOF;
        $this->assertEquals($expected, $this->dumper->dump($array, 1), '->dump() takes an inline level argument');

        $expected = <<<EOF
'': bar
foo: '#bar'
'foo''bar': {  }
bar:
    - 1
    - foo
foobar:
    foo: bar
    bar: [1, foo]
    foobar: { foo: bar, bar: [1, foo] }

EOF;
        $this->assertEquals($expected, $this->dumper->dump($array, 2), '->dump() takes an inline level argument');

        $expected = <<<EOF
'': bar
foo: '#bar'
'foo''bar': {  }
bar:
    - 1
    - foo
foobar:
    foo: bar
    bar:
        - 1
        - foo
    foobar:
        foo: bar
        bar: [1, foo]

EOF;
        $this->assertEquals($expected, $this->dumper->dump($array, 3), '->dump() takes an inline level argument');

        $expected = <<<EOF
'': bar
foo: '#bar'
'foo''bar': {  }
bar:
    - 1
    - foo
foobar:
    foo: bar
    bar:
        - 1
        - foo
    foobar:
        foo: bar
        bar:
            - 1
            - foo

EOF;
        $this->assertEquals($expected, $this->dumper->dump($array, 4), '->dump() takes an inline level argument');
        $this->assertEquals($expected, $this->dumper->dump($array, 10), '->dump() takes an inline level argument');
    }

    public function testObjectsSupport()
    {
        $a = array('foo' => new A(), 'bar' => 1);

        $this->assertEquals('{ foo: !!php/object:O:30:"Symfony\Component\Yaml\Tests\A":1:{s:1:"a";s:3:"foo";}, bar: 1 }', $this->dumper->dump($a), '->dump() is able to dump objects');
    }
}

class A
{
    public $a = 'foo';
}
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

spl_autoload_register(function ($class) {
    if (0 === strpos(ltrim($class, '/'), 'Symfony\Component\Yaml')) {
        if (file_exists($file = __DIR__.'/../'.substr(str_replace('\\', '/', $class), strlen('Symfony\Component\Yaml')).'.php')) {
            require_once $file;
        }
    }
});
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Yaml\Exception;

/**
 * Exception class thrown when an error occurs during parsing.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class ParseException extends \RuntimeException implements ExceptionInterface
{
    private $parsedFile;
    private $parsedLine;
    private $snippet;
    private $rawMessage;

    /**
     * Constructor.
     *
     * @param string    $message    The error message
     * @param integer   $parsedLine The line where the error occurred
     * @param integer   $snippet    The snippet of code near the problem
     * @param string    $parsedFile The file name where the error occurred
     * @param Exception $previous   The previous exception
     */
    public function __construct($message, $parsedLine = -1, $snippet = null, $parsedFile = null, Exception $previous = null)
    {
        $this->parsedFile = $parsedFile;
        $this->parsedLine = $parsedLine;
        $this->snippet = $snippet;
        $this->rawMessage = $message;

        $this->updateRepr();

        parent::__construct($this->message, 0, $previous);
    }

    /**
     * Gets the snippet of code near the error.
     *
     * @return string The snippet of code
     */
    public function getSnippet()
    {
        return $this->snippet;
    }

    /**
     * Sets the snippet of code near the error.
     *
     * @param string $snippet The code snippet
     */
    public function setSnippet($snippet)
    {
        $this->snippet = $snippet;

        $this->updateRepr();
    }

    /**
     * Gets the filename where the error occurred.
     *
     * This method returns null if a string is parsed.
     *
     * @return string The filename
     */
    public function getParsedFile()
    {
        return $this->parsedFile;
    }

    /**
     * Sets the filename where the error occurred.
     *
     * @param string $parsedFile The filename
     */
    public function setParsedFile($parsedFile)
    {
        $this->parsedFile = $parsedFile;

        $this->updateRepr();
    }

    /**
     * Gets the line where the error occurred.
     *
     * @return integer The file line
     */
    public function getParsedLine()
    {
        return $this->parsedLine;
    }

    /**
     * Sets the line where the error occurred.
     *
     * @param integer $parsedLine The file line
     */
    public function setParsedLine($parsedLine)
    {
        $this->parsedLine = $parsedLine;

        $this->updateRepr();
    }

    private function updateRepr()
    {
        $this->message = $this->rawMessage;

        $dot = false;
        if ('.' === substr($this->message, -1)) {
            $this->message = substr($this->message, 0, -1);
            $dot = true;
        }

        if (null !== $this->parsedFile) {
            $this->message .= sprintf(' in %s', json_encode($this->parsedFile));
        }

        if ($this->parsedLine >= 0) {
            $this->message .= sprintf(' at line %d', $this->parsedLine);
        }

        if ($this->snippet) {
            $this->message .= sprintf(' (near "%s")', $this->snippet);
        }

        if ($dot) {
            $this->message .= '.';
        }
    }
}
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Yaml\Exception;

/**
 * Exception class thrown when an error occurs during dumping.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class DumpException extends \RuntimeException implements ExceptionInterface
{
}
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Yaml\Exception;

/**
 * Exception interface for all exceptions thrown by the component.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
interface ExceptionInterface
{
}
<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Yaml;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Exception\DumpException;

/**
 * Inline implements a YAML parser/dumper for the YAML inline syntax.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Inline
{
    const REGEX_QUOTED_STRING = '(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\']*(?:\'\'[^\']*)*)\')';

    /**
     * Converts a YAML string to a PHP array.
     *
     * @param string $value A YAML string
     *
     * @return array A PHP array representing the YAML string
     */
    static public function parse($value)
    {
        $value = trim($value);

        if (0 == strlen($value)) {
            return '';
        }

        if (function_exists('mb_internal_encoding') && ((int) ini_get('mbstring.func_overload')) & 2) {
            $mbEncoding = mb_internal_encoding();
            mb_internal_encoding('ASCII');
        }

        switch ($value[0]) {
            case '[':
                $result = self::parseSequence($value);
                break;
            case '{':
                $result = self::parseMapping($value);
                break;
            default:
                $result = self::parseScalar($value);
        }

        if (isset($mbEncoding)) {
            mb_internal_encoding($mbEncoding);
        }

        return $result;
    }

    /**
     * Dumps a given PHP variable to a YAML string.
     *
     * @param mixed $value The PHP variable to convert
     *
     * @return string The YAML string representing the PHP array
     *
     * @throws DumpException When trying to dump PHP resource
     */
    static public function dump($value)
    {
        switch (true) {
            case is_resource($value):
                throw new DumpException(sprintf('Unable to dump PHP resources in a YAML file ("%s").', get_resource_type($value)));
            case is_object($value):
                return '!!php/object:'.serialize($value);
            case is_array($value):
                return self::dumpArray($value);
            case null === $value:
                return 'null';
            case true === $value:
                return 'true';
            case false === $value:
                return 'false';
            case ctype_digit($value):
                return is_string($value) ? "'$value'" : (int) $value;
            case is_numeric($value):
                $locale = setlocale(LC_NUMERIC, 0);
                if (false !== $locale) {
                    setlocale(LC_NUMERIC, 'C');
                }
                $repr = is_string($value) ? "'$value'" : (is_infinite($value) ? str_ireplace('INF', '.Inf', strval($value)) : strval($value));

                if (false !== $locale) {
                    setlocale(LC_NUMERIC, $locale);
                }

                return $repr;
            case Escaper::requiresDoubleQuoting($value):
                return Escaper::escapeWithDoubleQuotes($value);
            case Escaper::requiresSingleQuoting($value):
                return Escaper::escapeWithSingleQuotes($value);
            case '' == $value:
                return "''";
            case preg_match(self::getTimestampRegex(), $value):
            case in_array(strtolower($value), array('null', '~', 'true', 'false')):
                return "'$value'";
            default:
                return $value;
        }
    }

    /**
     * Dumps a PHP array to a YAML string.
     *
     * @param array $value The PHP array to dump
     *
     * @return string The YAML string representing the PHP array
     */
    static private function dumpArray($value)
    {
        // array
        $keys = array_keys($value);
        if ((1 == count($keys) && '0' == $keys[0])
            || (count($keys) > 1 && array_reduce($keys, function ($v, $w) { return (integer) $v + $w; }, 0) == count($keys) * (count($keys) - 1) / 2)
        ) {
            $output = array();
            foreach ($value as $val) {
                $output[] = self::dump($val);
            }

            return sprintf('[%s]', implode(', ', $output));
        }

        // mapping
        $output = array();
        foreach ($value as $key => $val) {
            $output[] = sprintf('%s: %s', self::dump($key), self::dump($val));
        }

        return sprintf('{ %s }', implode(', ', $output));
    }

    /**
     * Parses a scalar to a YAML string.
     *
     * @param scalar  $scalar
     * @param string  $delimiters
     * @param array   $stringDelimiters
     * @param integer &$i
     * @param Boolean $evaluate
     *
     * @return string A YAML string
     *
     * @throws ParseException When malformed inline YAML string is parsed
     */
    static public function parseScalar($scalar, $delimiters = null, $stringDelimiters = array('"', "'"), &$i = 0, $evaluate = true)
    {
        if (in_array($scalar[$i], $stringDelimiters)) {
            // quoted scalar
            $output = self::parseQuotedScalar($scalar, $i);
        } else {
            // "normal" string
            if (!$delimiters) {
                $output = substr($scalar, $i);
                $i += strlen($output);

                // remove comments
                if (false !== $strpos = strpos($output, ' #')) {
                    $output = rtrim(substr($output, 0, $strpos));
                }
            } elseif (preg_match('/^(.+?)('.implode('|', $delimiters).')/', substr($scalar, $i), $match)) {
                $output = $match[1];
                $i += strlen($output);
            } else {
                throw new ParseException(sprintf('Malformed inline YAML string (%s).', $scalar));
            }

            $output = $evaluate ? self::evaluateScalar($output) : $output;
        }

        return $output;
    }

    /**
     * Parses a quoted scalar to YAML.
     *
     * @param string  $scalar
     * @param integer &$i
     *
     * @return string A YAML string
     *
     * @throws ParseException When malformed inline YAML string is parsed
     */
    static private function parseQuotedScalar($scalar, &$i)
    {
        if (!preg_match('/'.self::REGEX_QUOTED_STRING.'/Au', substr($scalar, $i), $match)) {
            throw new ParseException(sprintf('Malformed inline YAML string (%s).', substr($scalar, $i)));
        }

        $output = substr($match[0], 1, strlen($match[0]) - 2);

        $unescaper = new Unescaper();
        if ('"' == $scalar[$i]) {
            $output = $unescaper->unescapeDoubleQuotedString($output);
        } else {
            $output = $unescaper->unescapeSingleQuotedString($output);
        }

        $i += strlen($match[0]);

        return $output;
    }

    /**
     * Parses a sequence to a YAML string.
     *
     * @param string  $sequence
     * @param integer &$i
     *
     * @return string A YAML string
     *
     * @throws ParseException When malformed inline YAML string is parsed
     */
    static private function parseSequence($sequence, &$i = 0)
    {
        $output = array();
        $len = strlen($sequence);
        $i += 1;

        // [foo, bar, ...]
        while ($i < $len) {
            switch ($sequence[$i]) {
                case '[':
                    // nested sequence
                    $output[] = self::parseSequence($sequence, $i);
                    break;
                case '{':
                    // nested mapping
                    $output[] = self::parseMapping($sequence, $i);
                    break;
                case ']':
                    return $output;
                case ',':
                case ' ':
                    break;
                default:
                    $isQuoted = in_array($sequence[$i], array('"', "'"));
                    $value = self::parseScalar($sequence, array(',', ']'), array('"', "'"), $i);

                    if (!$isQuoted && false !== strpos($value, ': ')) {
                        // embedded mapping?
                        try {
                            $value = self::parseMapping('{'.$value.'}');
                        } catch (\InvalidArgumentException $e) {
                            // no, it's not
                        }
                    }

                    $output[] = $value;

                    --$i;
            }

            ++$i;
        }

        throw new ParseException(sprintf('Malformed inline YAML string %s', $sequence));
    }

    /**
     * Parses a mapping to a YAML string.
     *
     * @param string  $mapping
     * @param integer &$i
     *
     * @return string A YAML string
     *
     * @throws ParseException When malformed inline YAML string is parsed
     */
    static private function parseMapping($mapping, &$i = 0)
    {
        $output = array();
        $len = strlen($mapping);
        $i += 1;

        // {foo: bar, bar:foo, ...}
        while ($i < $len) {
            switch ($mapping[$i]) {
                case ' ':
                case ',':
                    ++$i;
                    continue 2;
                case '}':
                    return $output;
            }

            // key
            $key = self::parseScalar($mapping, array(':', ' '), array('"', "'"), $i, false);

            // value
            $done = false;
            while ($i < $len) {
                switch ($mapping[$i]) {
                    case '[':
                        // nested sequence
                        $output[$key] = self::parseSequence($mapping, $i);
                        $done = true;
                        break;
                    case '{':
                        // nested mapping
                        $output[$key] = self::parseMapping($mapping, $i);
                        $done = true;
                        break;
                    case ':':
                    case ' ':
                        break;
                    default:
                        $output[$key] = self::parseScalar($mapping, array(',', '}'), array('"', "'"), $i);
                        $done = true;
                        --$i;
                }

                ++$i;

                if ($done) {
                    continue 2;
                }
            }
        }

        throw new ParseException(sprintf('Malformed inline YAML string %s', $mapping));
    }

    /**
     * Evaluates scalars and replaces magic values.
     *
     * @param string $scalar
     *
     * @return string A YAML string
     */
    static private function evaluateScalar($scalar)
    {
        $scalar = trim($scalar);

        switch (true) {
            case 'null' == strtolower($scalar):
            case '' == $scalar:
            case '~' == $scalar:
                return null;
            case 0 === strpos($scalar, '!str'):
                return (string) substr($scalar, 5);
            case 0 === strpos($scalar, '! '):
                return intval(self::parseScalar(substr($scalar, 2)));
            case 0 === strpos($scalar, '!!php/object:'):
                return unserialize(substr($scalar, 13));
            case ctype_digit($scalar):
                $raw = $scalar;
                $cast = intval($scalar);

                return '0' == $scalar[0] ? octdec($scalar) : (((string) $raw == (string) $cast) ? $cast : $raw);
            case 'true' === strtolower($scalar):
                return true;
            case 'false' === strtolower($scalar):
                return false;
            case is_numeric($scalar):
                return '0x' == $scalar[0].$scalar[1] ? hexdec($scalar) : floatval($scalar);
            case 0 == strcasecmp($scalar, '.inf'):
            case 0 == strcasecmp($scalar, '.NaN'):
                return -log(0);
            case 0 == strcasecmp($scalar, '-.inf'):
                return log(0);
            case preg_match('/^(-|\+)?[0-9,]+(\.[0-9]+)?$/', $scalar):
                return floatval(str_replace(',', '', $scalar));
            case preg_match(self::getTimestampRegex(), $scalar):
                return strtotime($scalar);
            default:
                return (string) $scalar;
        }
    }

    /**
     * Gets a regex that matches an unix timestamp
     *
     * @return string The regular expression
     */
    static private function getTimestampRegex()
    {
        return <<<EOF
        ~^
        (?P<year>[0-9][0-9][0-9][0-9])
        -(?P<month>[0-9][0-9]?)
        -(?P<day>[0-9][0-9]?)
        (?:(?:[Tt]|[ \t]+)
        (?P<hour>[0-9][0-9]?)
        :(?P<minute>[0-9][0-9])
        :(?P<second>[0-9][0-9])
        (?:\.(?P<fraction>[0-9]*))?
        (?:[ \t]*(?P<tz>Z|(?P<tz_sign>[-+])(?P<tz_hour>[0-9][0-9]?)
        (?::(?P<tz_minute>[0-9][0-9]))?))?)?
        $~x
EOF;
    }
}
<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Yaml;

use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Parser parses YAML strings to convert them to PHP arrays.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Parser
{
    private $offset         = 0;
    private $lines          = array();
    private $currentLineNb  = -1;
    private $currentLine    = '';
    private $refs           = array();

    /**
     * Constructor
     *
     * @param integer $offset The offset of YAML document (used for line numbers in error messages)
     */
    public function __construct($offset = 0)
    {
        $this->offset = $offset;
    }

    /**
     * Parses a YAML string to a PHP value.
     *
     * @param  string $value A YAML string
     *
     * @return mixed  A PHP value
     *
     * @throws ParseException If the YAML is not valid
     */
    public function parse($value)
    {
        $this->currentLineNb = -1;
        $this->currentLine = '';
        $this->lines = explode("\n", $this->cleanup($value));

        if (function_exists('mb_detect_encoding') && false === mb_detect_encoding($value, 'UTF-8', true)) {
            throw new ParseException('The YAML value does not appear to be valid UTF-8.');
        }

        if (function_exists('mb_internal_encoding') && ((int) ini_get('mbstring.func_overload')) & 2) {
            $mbEncoding = mb_internal_encoding();
            mb_internal_encoding('UTF-8');
        }

        $data = array();
        while ($this->moveToNextLine()) {
            if ($this->isCurrentLineEmpty()) {
                continue;
            }

            // tab?
            if ("\t" === $this->currentLine[0]) {
                throw new ParseException('A YAML file cannot contain tabs as indentation.', $this->getRealCurrentLineNb() + 1, $this->currentLine);
            }

            $isRef = $isInPlace = $isProcessed = false;
            if (preg_match('#^\-((?P<leadspaces>\s+)(?P<value>.+?))?\s*$#u', $this->currentLine, $values)) {
                if (isset($values['value']) && preg_match('#^&(?P<ref>[^ ]+) *(?P<value>.*)#u', $values['value'], $matches)) {
                    $isRef = $matches['ref'];
                    $values['value'] = $matches['value'];
                }

                // array
                if (!isset($values['value']) || '' == trim($values['value'], ' ') || 0 === strpos(ltrim($values['value'], ' '), '#')) {
                    $c = $this->getRealCurrentLineNb() + 1;
                    $parser = new Parser($c);
                    $parser->refs =& $this->refs;
                    $data[] = $parser->parse($this->getNextEmbedBlock());
                } else {
                    if (isset($values['leadspaces'])
                        && ' ' == $values['leadspaces']
                        && preg_match('#^(?P<key>'.Inline::REGEX_QUOTED_STRING.'|[^ \'"\{\[].*?) *\:(\s+(?P<value>.+?))?\s*$#u', $values['value'], $matches)
                    ) {
                        // this is a compact notation element, add to next block and parse
                        $c = $this->getRealCurrentLineNb();
                        $parser = new Parser($c);
                        $parser->refs =& $this->refs;

                        $block = $values['value'];
                        if (!$this->isNextLineIndented()) {
                            $block .= "\n".$this->getNextEmbedBlock($this->getCurrentLineIndentation() + 2);
                        }

                        $data[] = $parser->parse($block);
                    } else {
                        $data[] = $this->parseValue($values['value']);
                    }
                }
            } elseif (preg_match('#^(?P<key>'.Inline::REGEX_QUOTED_STRING.'|[^ \'"\[\{].*?) *\:(\s+(?P<value>.+?))?\s*$#u', $this->currentLine, $values)) {
                try {
                    $key = Inline::parseScalar($values['key']);
                } catch (ParseException $e) {
                    $e->setParsedLine($this->getRealCurrentLineNb() + 1);
                    $e->setSnippet($this->currentLine);

                    throw $e;
                }

                if ('<<' === $key) {
                    if (isset($values['value']) && 0 === strpos($values['value'], '*')) {
                        $isInPlace = substr($values['value'], 1);
                        if (!array_key_exists($isInPlace, $this->refs)) {
                            throw new ParseException(sprintf('Reference "%s" does not exist.', $isInPlace), $this->getRealCurrentLineNb() + 1, $this->currentLine);
                        }
                    } else {
                        if (isset($values['value']) && $values['value'] !== '') {
                            $value = $values['value'];
                        } else {
                            $value = $this->getNextEmbedBlock();
                        }
                        $c = $this->getRealCurrentLineNb() + 1;
                        $parser = new Parser($c);
                        $parser->refs =& $this->refs;
                        $parsed = $parser->parse($value);

                        $merged = array();
                        if (!is_array($parsed)) {
                            throw new ParseException('YAML merge keys used with a scalar value instead of an array.', $this->getRealCurrentLineNb() + 1, $this->currentLine);
                        } elseif (isset($parsed[0])) {
                            // Numeric array, merge individual elements
                            foreach (array_reverse($parsed) as $parsedItem) {
                                if (!is_array($parsedItem)) {
                                    throw new ParseException('Merge items must be arrays.', $this->getRealCurrentLineNb() + 1, $parsedItem);
                                }
                                $merged = array_merge($parsedItem, $merged);
                            }
                        } else {
                            // Associative array, merge
                            $merged = array_merge($merged, $parsed);
                        }

                        $isProcessed = $merged;
                    }
                } elseif (isset($values['value']) && preg_match('#^&(?P<ref>[^ ]+) *(?P<value>.*)#u', $values['value'], $matches)) {
                    $isRef = $matches['ref'];
                    $values['value'] = $matches['value'];
                }

                if ($isProcessed) {
                    // Merge keys
                    $data = $isProcessed;
                // hash
                } elseif (!isset($values['value']) || '' == trim($values['value'], ' ') || 0 === strpos(ltrim($values['value'], ' '), '#')) {
                    // if next line is less indented or equal, then it means that the current value is null
                    if ($this->isNextLineIndented()) {
                        $data[$key] = null;
                    } else {
                        $c = $this->getRealCurrentLineNb() + 1;
                        $parser = new Parser($c);
                        $parser->refs =& $this->refs;
                        $data[$key] = $parser->parse($this->getNextEmbedBlock());
                    }
                } else {
                    if ($isInPlace) {
                        $data = $this->refs[$isInPlace];
                    } else {
                        $data[$key] = $this->parseValue($values['value']);
                    }
                }
            } else {
                // 1-liner followed by newline
                if (2 == count($this->lines) && empty($this->lines[1])) {
                    try {
                        $value = Inline::parse($this->lines[0]);
                    } catch (ParseException $e) {
                        $e->setParsedLine($this->getRealCurrentLineNb() + 1);
                        $e->setSnippet($this->currentLine);

                        throw $e;
                    }

                    if (is_array($value)) {
                        $first = reset($value);
                        if (is_string($first) && 0 === strpos($first, '*')) {
                            $data = array();
                            foreach ($value as $alias) {
                                $data[] = $this->refs[substr($alias, 1)];
                            }
                            $value = $data;
                        }
                    }

                    if (isset($mbEncoding)) {
                        mb_internal_encoding($mbEncoding);
                    }

                    return $value;
                }

                switch (preg_last_error()) {
                    case PREG_INTERNAL_ERROR:
                        $error = 'Internal PCRE error.';
                        break;
                    case PREG_BACKTRACK_LIMIT_ERROR:
                        $error = 'pcre.backtrack_limit reached.';
                        break;
                    case PREG_RECURSION_LIMIT_ERROR:
                        $error = 'pcre.recursion_limit reached.';
                        break;
                    case PREG_BAD_UTF8_ERROR:
                        $error = 'Malformed UTF-8 data.';
                        break;
                    case PREG_BAD_UTF8_OFFSET_ERROR:
                        $error = 'Offset doesn\'t correspond to the begin of a valid UTF-8 code point.';
                        break;
                    default:
                        $error = 'Unable to parse.';
                }

                throw new ParseException($error, $this->getRealCurrentLineNb() + 1, $this->currentLine);
            }

            if ($isRef) {
                $this->refs[$isRef] = end($data);
            }
        }

        if (isset($mbEncoding)) {
            mb_internal_encoding($mbEncoding);
        }

        return empty($data) ? null : $data;
    }

    /**
     * Returns the current line number (takes the offset into account).
     *
     * @return integer The current line number
     */
    private function getRealCurrentLineNb()
    {
        return $this->currentLineNb + $this->offset;
    }

    /**
     * Returns the current line indentation.
     *
     * @return integer The current line indentation
     */
    private function getCurrentLineIndentation()
    {
        return strlen($this->currentLine) - strlen(ltrim($this->currentLine, ' '));
    }

    /**
     * Returns the next embed block of YAML.
     *
     * @param integer $indentation The indent level at which the block is to be read, or null for default
     *
     * @return string A YAML string
     *
     * @throws ParseException When indentation problem are detected
     */
    private function getNextEmbedBlock($indentation = null)
    {
        $this->moveToNextLine();

        if (null === $indentation) {
            $newIndent = $this->getCurrentLineIndentation();

            if (!$this->isCurrentLineEmpty() && 0 == $newIndent) {
                throw new ParseException('Indentation problem.', $this->getRealCurrentLineNb() + 1, $this->currentLine);
            }
        } else {
            $newIndent = $indentation;
        }

        $data = array(substr($this->currentLine, $newIndent));

        while ($this->moveToNextLine()) {
            if ($this->isCurrentLineEmpty()) {
                if ($this->isCurrentLineBlank()) {
                    $data[] = substr($this->currentLine, $newIndent);
                }

                continue;
            }

            $indent = $this->getCurrentLineIndentation();

            if (preg_match('#^(?P<text> *)$#', $this->currentLine, $match)) {
                // empty line
                $data[] = $match['text'];
            } elseif ($indent >= $newIndent) {
                $data[] = substr($this->currentLine, $newIndent);
            } elseif (0 == $indent) {
                $this->moveToPreviousLine();

                break;
            } else {
                throw new ParseException('Indentation problem.', $this->getRealCurrentLineNb() + 1, $this->currentLine);
            }
        }

        return implode("\n", $data);
    }

    /**
     * Moves the parser to the next line.
     *
     * @return Boolean
     */
    private function moveToNextLine()
    {
        if ($this->currentLineNb >= count($this->lines) - 1) {
            return false;
        }

        $this->currentLine = $this->lines[++$this->currentLineNb];

        return true;
    }

    /**
     * Moves the parser to the previous line.
     */
    private function moveToPreviousLine()
    {
        $this->currentLine = $this->lines[--$this->currentLineNb];
    }

    /**
     * Parses a YAML value.
     *
     * @param  string $value A YAML value
     *
     * @return mixed  A PHP value
     *
     * @throws ParseException When reference does not exist
     */
    private function parseValue($value)
    {
        if (0 === strpos($value, '*')) {
            if (false !== $pos = strpos($value, '#')) {
                $value = substr($value, 1, $pos - 2);
            } else {
                $value = substr($value, 1);
            }

            if (!array_key_exists($value, $this->refs)) {
                throw new ParseException(sprintf('Reference "%s" does not exist.', $value), $this->currentLine);
            }

            return $this->refs[$value];
        }

        if (preg_match('/^(?P<separator>\||>)(?P<modifiers>\+|\-|\d+|\+\d+|\-\d+|\d+\+|\d+\-)?(?P<comments> +#.*)?$/', $value, $matches)) {
            $modifiers = isset($matches['modifiers']) ? $matches['modifiers'] : '';

            return $this->parseFoldedScalar($matches['separator'], preg_replace('#\d+#', '', $modifiers), intval(abs($modifiers)));
        }

        try {
            return Inline::parse($value);
        } catch (ParseException $e) {
            $e->setParsedLine($this->getRealCurrentLineNb() + 1);
            $e->setSnippet($this->currentLine);

            throw $e;
        }
    }

    /**
     * Parses a folded scalar.
     *
     * @param  string  $separator   The separator that was used to begin this folded scalar (| or >)
     * @param  string  $indicator   The indicator that was used to begin this folded scalar (+ or -)
     * @param  integer $indentation The indentation that was used to begin this folded scalar
     *
     * @return string  The text value
     */
    private function parseFoldedScalar($separator, $indicator = '', $indentation = 0)
    {
        $separator = '|' == $separator ? "\n" : ' ';
        $text = '';

        $notEOF = $this->moveToNextLine();

        while ($notEOF && $this->isCurrentLineBlank()) {
            $text .= "\n";

            $notEOF = $this->moveToNextLine();
        }

        if (!$notEOF) {
            return '';
        }

        if (!preg_match('#^(?P<indent>'.($indentation ? str_repeat(' ', $indentation) : ' +').')(?P<text>.*)$#u', $this->currentLine, $matches)) {
            $this->moveToPreviousLine();

            return '';
        }

        $textIndent = $matches['indent'];
        $previousIndent = 0;

        $text .= $matches['text'].$separator;
        while ($this->currentLineNb + 1 < count($this->lines)) {
            $this->moveToNextLine();

            if (preg_match('#^(?P<indent> {'.strlen($textIndent).',})(?P<text>.+)$#u', $this->currentLine, $matches)) {
                if (' ' == $separator && $previousIndent != $matches['indent']) {
                    $text = substr($text, 0, -1)."\n";
                }
                $previousIndent = $matches['indent'];

                $text .= str_repeat(' ', $diff = strlen($matches['indent']) - strlen($textIndent)).$matches['text'].($diff ? "\n" : $separator);
            } elseif (preg_match('#^(?P<text> *)$#', $this->currentLine, $matches)) {
                $text .= preg_replace('#^ {1,'.strlen($textIndent).'}#', '', $matches['text'])."\n";
            } else {
                $this->moveToPreviousLine();

                break;
            }
        }

        if (' ' == $separator) {
            // replace last separator by a newline
            $text = preg_replace('/ (\n*)$/', "\n$1", $text);
        }

        switch ($indicator) {
            case '':
                $text = preg_replace('#\n+$#s', "\n", $text);
                break;
            case '+':
                break;
            case '-':
                $text = preg_replace('#\n+$#s', '', $text);
                break;
        }

        return $text;
    }

    /**
     * Returns true if the next line is indented.
     *
     * @return Boolean Returns true if the next line is indented, false otherwise
     */
    private function isNextLineIndented()
    {
        $currentIndentation = $this->getCurrentLineIndentation();
        $notEOF = $this->moveToNextLine();

        while ($notEOF && $this->isCurrentLineEmpty()) {
            $notEOF = $this->moveToNextLine();
        }

        if (false === $notEOF) {
            return false;
        }

        $ret = false;
        if ($this->getCurrentLineIndentation() <= $currentIndentation) {
            $ret = true;
        }

        $this->moveToPreviousLine();

        return $ret;
    }

    /**
     * Returns true if the current line is blank or if it is a comment line.
     *
     * @return Boolean Returns true if the current line is empty or if it is a comment line, false otherwise
     */
    private function isCurrentLineEmpty()
    {
        return $this->isCurrentLineBlank() || $this->isCurrentLineComment();
    }

    /**
     * Returns true if the current line is blank.
     *
     * @return Boolean Returns true if the current line is blank, false otherwise
     */
    private function isCurrentLineBlank()
    {
        return '' == trim($this->currentLine, ' ');
    }

    /**
     * Returns true if the current line is a comment line.
     *
     * @return Boolean Returns true if the current line is a comment line, false otherwise
     */
    private function isCurrentLineComment()
    {
        //checking explicitly the first char of the trim is faster than loops or strpos
        $ltrimmedLine = ltrim($this->currentLine, ' ');

        return $ltrimmedLine[0] === '#';
    }

    /**
     * Cleanups a YAML string to be parsed.
     *
     * @param  string $value The input YAML string
     *
     * @return string A cleaned up YAML string
     */
    private function cleanup($value)
    {
        $value = str_replace(array("\r\n", "\r"), "\n", $value);

        if (!preg_match("#\n$#", $value)) {
            $value .= "\n";
        }

        // strip YAML header
        $count = 0;
        $value = preg_replace('#^\%YAML[: ][\d\.]+.*\n#su', '', $value, -1, $count);
        $this->offset += $count;

        // remove leading comments
        $trimmedValue = preg_replace('#^(\#.*?\n)+#s', '', $value, -1, $count);
        if ($count == 1) {
            // items have been removed, update the offset
            $this->offset += substr_count($value, "\n") - substr_count($trimmedValue, "\n");
            $value = $trimmedValue;
        }

        // remove start of the document marker (---)
        $trimmedValue = preg_replace('#^\-\-\-.*?\n#s', '', $value, -1, $count);
        if ($count == 1) {
            // items have been removed, update the offset
            $this->offset += substr_count($value, "\n") - substr_count($trimmedValue, "\n");
            $value = $trimmedValue;

            // remove end of the document marker (...)
            $value = preg_replace('#\.\.\.\s*$#s', '', $value);
        }

        return $value;
    }
}
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Yaml;

use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Yaml offers convenience methods to load and dump YAML.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class Yaml
{
    static public $enablePhpParsing = false;

    static public function enablePhpParsing()
    {
        self::$enablePhpParsing = true;
    }

    /**
     * Parses YAML into a PHP array.
     *
     * The parse method, when supplied with a YAML stream (string or file),
     * will do its best to convert YAML in a file into a PHP array.
     *
     *  Usage:
     *  <code>
     *   $array = Yaml::parse('config.yml');
     *   print_r($array);
     *  </code>
     *
     * @param string $input Path to a YAML file or a string containing YAML
     *
     * @return array The YAML converted to a PHP array
     *
     * @throws \InvalidArgumentException If the YAML is not valid
     *
     * @api
     */
    static public function parse($input)
    {
        // if input is a file, process it
        $file = '';
        if (strpos($input, "\n") === false && is_file($input)) {
            if (false === is_readable($input)) {
                throw new ParseException(sprintf('Unable to parse "%s" as the file is not readable.', $input));
            }

            $file = $input;
            if (self::$enablePhpParsing) {
                ob_start();
                $retval = include($file);
                $content = ob_get_clean();

                // if an array is returned by the config file assume it's in plain php form else in YAML
                $input = is_array($retval) ? $retval : $content;

                // if an array is returned by the config file assume it's in plain php form else in YAML
                if (is_array($input)) {
                    return $input;
                }
            } else {
                $input = file_get_contents($file);
            }
        }

        $yaml = new Parser();

        try {
            return $yaml->parse($input);
        } catch (ParseException $e) {
            if ($file) {
                $e->setParsedFile($file);
            }

            throw $e;
        }
    }

    /**
     * Dumps a PHP array to a YAML string.
     *
     * The dump method, when supplied with an array, will do its best
     * to convert the array into friendly YAML.
     *
     * @param array   $array PHP array
     * @param integer $inline The level where you switch to inline YAML
     *
     * @return string A YAML string representing the original PHP array
     *
     * @api
     */
    static public function dump($array, $inline = 2)
    {
        $yaml = new Dumper();

        return $yaml->dump($array, $inline);
    }
}
<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Yaml;

/**
 * Unescaper encapsulates unescaping rules for single and double-quoted
 * YAML strings.
 *
 * @author Matthew Lewinski <matthew@lewinski.org>
 */
class Unescaper
{
    // Parser and Inline assume UTF-8 encoding, so escaped Unicode characters
    // must be converted to that encoding.
    const ENCODING = 'UTF-8';

    // Regex fragment that matches an escaped character in a double quoted
    // string.
    const REGEX_ESCAPED_CHARACTER = "\\\\([0abt\tnvfre \\\"\\/\\\\N_LP]|x[0-9a-fA-F]{2}|u[0-9a-fA-F]{4}|U[0-9a-fA-F]{8})";

    /**
     * Unescapes a single quoted string.
     *
     * @param string $value A single quoted string.
     *
     * @return string The unescaped string.
     */
    public function unescapeSingleQuotedString($value)
    {
        return str_replace('\'\'', '\'', $value);
    }

    /**
     * Unescapes a double quoted string.
     *
     * @param string $value A double quoted string.
     *
     * @return string The unescaped string.
     */
    public function unescapeDoubleQuotedString($value)
    {
        $self = $this;
        $callback = function($match) use($self) {
            return $self->unescapeCharacter($match[0]);
        };

        // evaluate the string
        return preg_replace_callback('/'.self::REGEX_ESCAPED_CHARACTER.'/u', $callback, $value);
    }

    /**
     * Unescapes a character that was found in a double-quoted string
     *
     * @param string $value An escaped character
     *
     * @return string The unescaped character
     */
    public function unescapeCharacter($value)
    {
        switch ($value{1}) {
            case '0':
                return "\x0";
            case 'a':
                return "\x7";
            case 'b':
                return "\x8";
            case 't':
                return "\t";
            case "\t":
                return "\t";
            case 'n':
                return "\n";
            case 'v':
                return "\xb";
            case 'f':
                return "\xc";
            case 'r':
                return "\xd";
            case 'e':
                return "\x1b";
            case ' ':
                return ' ';
            case '"':
                return '"';
            case '/':
                return '/';
            case '\\':
                return '\\';
            case 'N':
                // U+0085 NEXT LINE
                return $this->convertEncoding("\x00\x85", self::ENCODING, 'UCS-2BE');
            case '_':
                // U+00A0 NO-BREAK SPACE
                return $this->convertEncoding("\x00\xA0", self::ENCODING, 'UCS-2BE');
            case 'L':
                // U+2028 LINE SEPARATOR
                return $this->convertEncoding("\x20\x28", self::ENCODING, 'UCS-2BE');
            case 'P':
                // U+2029 PARAGRAPH SEPARATOR
                return $this->convertEncoding("\x20\x29", self::ENCODING, 'UCS-2BE');
            case 'x':
                $char = pack('n', hexdec(substr($value, 2, 2)));

                return $this->convertEncoding($char, self::ENCODING, 'UCS-2BE');
            case 'u':
                $char = pack('n', hexdec(substr($value, 2, 4)));

                return $this->convertEncoding($char, self::ENCODING, 'UCS-2BE');
            case 'U':
                $char = pack('N', hexdec(substr($value, 2, 8)));

                return $this->convertEncoding($char, self::ENCODING, 'UCS-4BE');
        }
    }

    /**
     * Convert a string from one encoding to another.
     *
     * @param string $value The string to convert
     * @param string $to    The input encoding
     * @param string $from  The output encoding
     *
     * @return string The string with the new encoding
     *
     * @throws \RuntimeException if no suitable encoding function is found (iconv or mbstring)
     */
    private function convertEncoding($value, $to, $from)
    {
        if (function_exists('iconv')) {
            return iconv($from, $to, $value);
        } elseif (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($value, $to, $from);
        }

        throw new \RuntimeException('No suitable convert encoding function (install the iconv or mbstring extension).');
    }
}
<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Yaml;

/**
 * Escaper encapsulates escaping rules for single and double-quoted
 * YAML strings.
 *
 * @author Matthew Lewinski <matthew@lewinski.org>
 */
class Escaper
{
    // Characters that would cause a dumped string to require double quoting.
    const REGEX_CHARACTER_TO_ESCAPE = "[\\x00-\\x1f]|\xc2\x85|\xc2\xa0|\xe2\x80\xa8|\xe2\x80\xa9";

    // Mapping arrays for escaping a double quoted string. The backslash is
    // first to ensure proper escaping because str_replace operates iteratively
    // on the input arrays. This ordering of the characters avoids the use of strtr,
    // which performs more slowly.
    static private $escapees = array('\\\\', '\\"',
                                     "\x00",  "\x01",  "\x02",  "\x03",  "\x04",  "\x05",  "\x06",  "\x07",
                                     "\x08",  "\x09",  "\x0a",  "\x0b",  "\x0c",  "\x0d",  "\x0e",  "\x0f",
                                     "\x10",  "\x11",  "\x12",  "\x13",  "\x14",  "\x15",  "\x16",  "\x17",
                                     "\x18",  "\x19",  "\x1a",  "\x1b",  "\x1c",  "\x1d",  "\x1e",  "\x1f",
                                     "\xc2\x85", "\xc2\xa0", "\xe2\x80\xa8", "\xe2\x80\xa9");
    static private $escaped  = array('\\"', '\\\\',
                                     "\\0",   "\\x01", "\\x02", "\\x03", "\\x04", "\\x05", "\\x06", "\\a",
                                     "\\b",   "\\t",   "\\n",   "\\v",   "\\f",   "\\r",   "\\x0e", "\\x0f",
                                     "\\x10", "\\x11", "\\x12", "\\x13", "\\x14", "\\x15", "\\x16", "\\x17",
                                     "\\x18", "\\x19", "\\x1a", "\\e",   "\\x1c", "\\x1d", "\\x1e", "\\x1f",
                                     "\\N", "\\_", "\\L", "\\P");

    /**
     * Determines if a PHP value would require double quoting in YAML.
     *
     * @param string $value A PHP value
     *
     * @return Boolean True if the value would require double quotes.
     */
    static public function requiresDoubleQuoting($value)
    {
        return preg_match('/'.self::REGEX_CHARACTER_TO_ESCAPE.'/u', $value);
    }

    /**
     * Escapes and surrounds a PHP value with double quotes.
     *
     * @param string $value A PHP value
     *
     * @return string The quoted, escaped string
     */
    static public function escapeWithDoubleQuotes($value)
    {
        return sprintf('"%s"', str_replace(self::$escapees, self::$escaped, $value));
    }

    /**
     * Determines if a PHP value would require single quoting in YAML.
     *
     * @param string $value A PHP value
     *
     * @return Boolean True if the value would require single quotes.
     */
    static public function requiresSingleQuoting($value)
    {
        return preg_match('/[ \s \' " \: \{ \} \[ \] , & \* \# \?] | \A[ - ? | < > = ! % @ ` ]/x', $value);
    }

    /**
     * Escapes and surrounds a PHP value with single quotes.
     *
     * @param string $value A PHP value
     *
     * @return string The quoted, escaped string
     */
    static public function escapeWithSingleQuotes($value)
    {
        return sprintf("'%s'", str_replace('\'', '\'\'', $value));
    }
}
<? require (('cli' == php_sapi_name()) ? 'prod' : XMODE) . DS . 'Controller.php'; ?>
<?php
/** Customized exception class
 *
 * @author Lionel Péramo */
namespace lib\myLibs\core;

use lib\myLibs\core\Controller,
    lib\myLibs\core\Debug_Tools;

require_once __DIR__ . '/../../../config/All_Config.php';

class Lionel_Exception extends \Exception
{
  public function __construct($message = 'Error !', $code = '', $file = '', $line = '', $context = '')
  {
    $this->message = $message;
    $this->code = ('' != $code) ? $code : $this->getCode();
    $this->file = ('' == $file) ? $this->getFile() : $file;
    $this->line = ('' == $line) ? $this->getLine() : $line;
    $this->context = $context;
  }

  public function errorMessage()
  {
    ob_clean();
    $renderController = new Controller();
    // $renderController->route = '';
    $renderController->viewPath = CORE_VIEWS_PATH;
    $renderController::$path = $_SERVER['DOCUMENT_ROOT'] . '..';

    if(! empty($this->context))
    {
      unset($this->context['variables']);
      convertArrayToShowable($this->context, 'Variables');
    }

    return $renderController->renderView('/exception.phtml', array(
      'message' =>$this->message,
      'code' => $this->code,
      'file' => $this->file,
      'line' => $this->line,
      'context' => $this->context,
      'backtraces' => $this->getTrace()
      )
    );
  }
}
<?
/** The 'Desc' functions explains the functions "without 'Desc'"
 *
 * @author Lionel Péramo */

namespace lib\myLibs\core;

use lib\myLibs\core\Database,
    config\All_Config;

class Tasks
{
  /** Clears the cache. */
  public static function cc()
  {
    array_map('unlink', glob(All_Config::$cache_path . '*.cache'));
    echo('Cache cleared.' . PHP_EOL);
  }

  public static function ccDesc() { return array('Clears the cache'); }

  public static function crypt(){
    require '../config/All_Config.php';
    echo crypt($pwd, FWK_HASH), PHP_EOL;
  }

  public static function cryptDesc(){
    return array('Crypts a password and shows it.',
      array('password' => 'The password to crypt.'),
      array('required')
    );
  }

  public static function genAssets($argv){ require 'GenAssets.php'; }

  public static function genAssetsDesc(){
    return array('Generates one css file and one js file that contain respectively all the minified css files and all the obfuscated minified js files.',
      array(
        'mask' => '1 => templates, 2 => css; 4 => js, => 7 all',
        'route' => 'The route for which you want to generate resources.'),
      array('optional', 'optional')
    );
  }

  /** Executes the sql script */
  public static function sql() { exec('mysql ../sql/entire_script.sql'); }

  public static function sqlDesc() { return array('Executes the sql script'); }

  /** (sql_generate_basic) Database creation, tables creation. */
  public static function sql_gb($argv)
  {
    Database::init();
    if(isset($argv[3]))
    {
      $force = 'true' == $argv[3]; // Forces the value to be a boolean
      Database::createDatabase($argv[2], $force);
    }else
      Database::createDatabase($argv[2]);
  }

  public static function sql_gbDesc()
  {
    return array(
      'Database creation, tables creation.(sql_generate_basic)',
      array (
        'databaseName' => 'The database name !',
        'force' => 'If true, we erase the database !'
      ),
      array('required', 'optional')
    );
  }

  /** (sql_generate_fixtures) Generates fixtures. */
  public static function sql_gf($argv)
  {
    Database::init();
    if(isset($argv[3]))
    {
      $force = 'true' == $argv[3]; // Forces the value to be a boolean
      Database::createFixtures($argv[2], $force);
    }else
      Database::createFixtures($argv[2]);
  }

  public static function sql_gfDesc()
  {
    return array(
      'Generates fixtures. (sql_generate_fixtures)',
      array(
        'databaseName' => 'The database name !',
        'force' => 'If true, we erase the database !'
      ),
      array('required', 'optional')
    );
  }

  public static function routes(){
    require '../config/Routes.php';
    $alt = 0;
    foreach(\config\Routes::$_ as $route => $details){
      // Routes and paths management
      $chunks = $details['chunks'];
      $altColor = ($alt % 2) ? cyan() : lightBlue();
      echo $altColor, PHP_EOL, sprintf('%-25s', $route), str_pad('Url', 10, ' '), ': ' , $chunks[0], PHP_EOL;
      echo str_pad(' ', 25, ' '), str_pad('Path', 10, ' '), ': ' . $chunks[1] . '/' . $chunks[2] . '/' . $chunks[3] . 'Controller/' . $chunks[4] , PHP_EOL;

      $shaName = sha1('ca' . $route . All_Config::$version . 'che');

      // Resources management
      if(isset($details['resources']))
      {
        $resources = $details['resources'];

        $basePath = substr(__DIR__, 0, -15) . 'cache/';
        echo str_pad(' ', 25, ' '), 'Resources : ';
        if(isset($resources['_css']) || isset($resources['bundle_css']) || isset($resources['module_css']))
          echo (file_exists($basePath . 'css' . '/' . $shaName. '.' . 'css.gz')) ? green() : lightRed(), '[CSS]', $altColor;
        if(isset($resources['_js']) || isset($resources['bundle_js']) || isset($resources['module_js']) || isset($resources['first_js']))
          echo (file_exists($basePath . 'js' . '/' . $shaName. '.' . 'js.gz')) ? green() : lightRed(), '[JS]', $altColor;
        if(isset($resources['template']))
          echo (file_exists($basePath . 'tpl' . '/' . $shaName. '.' . 'html.gz')) ? green() : lightRed(), '[TEMPLATE]', $altColor;

        echo '[', $shaName, ']', PHP_EOL, endColor();
      }else
        echo str_pad(' ', 25, ' '), 'Resources : No resources. ', '[', $shaName, ']', PHP_EOL, endColor();

      $alt++;
    }
  }

  public static function routesDesc(){ return array('Shows the routes and their associated kind of resources in the case they have some. (green whether they exists, red otherwise)'); }

  public static function genClassMap(){ require('GenClassMap.php'); }
  public static function genClassMapDesc(){ return array('Generates a class mapping file that will be used to replace the autoloading method.'); }

  public static function genBootstrap($argv) { require('GenBootstrap.php'); }

  public static function genBootstrapDesc(){
    return array(
      'Launch the genClassMap command and generates a file that contains all the necessary php files.',
      array('genClassmap' => 'If set to 0, it prevents the generation/override of the class mapping file.'),
      array('optional')
    );
  }
}
?>

<?php

namespace lib\myLibs\core;

use config\All_Config;
use lib\myLibs\core\Session;

/**
 * A classic MVC model class
 *
 * @author Lionel Péramo
 */
class Model
{
  public function get($property) { return $this->$property; }

  public function set($property, $value) { $this->$property = $value; }

  /**
   * Save or update if the id is known
   *
   * @return int The last id used
   */
  public function save()
  {

    $dbName = Session::get('db');
    /* @var $db lib\myLibs\core\bdd\Sql */
    $db = Session::get('dbConn');
//    $db instanceof lib\mesLibcore\bdd\Sql;
    $db->selectDb();

    $refl = new \ReflectionObject($this);
    $props = $refl->getProperties();
    $properties = array();

    foreach($props as $prop)
    {
      $name = $prop->name;
      $properties[$prop->name] = (empty($this->$name)) ? null : $this->$name;
      if(strpos($name, 'id') !== false)
      {
        $id = $name;
        if(!empty($properties[$name]))
          $update  = true;
      }
    }
    unset($properties['table'], $props, $prop, $refl);
    if($update)
    { // It's an update
      $query = 'UPDATE `'. All_Config::$dbConnections[$dbName]['db'] . '_' . $this->table . '` SET ';
      $idValue = $properties[$id];
      unset($properties[$id]);
      foreach($properties as $name => $value)
      {
        $query .= '`' . $name . '`=' ;
        $query .= (is_string($value)) ? '\'' . addslashes($value) . '\',' : $value . ' ';
      }

      $query = substr($query, 0, -1) . ' WHERE `'. $id . '`=' . $idValue;
    }else // we add a entry
    {
      unset($properties[$id]);
      $query = 'INSERT INTO `'. All_Config::$dbConnections[$dbName]['db'] . '_' . $this->table . '` (';
      $values = '';
      foreach($properties as $name => $value)
      {
        $query .= '`' . $name . '`,';
        $values .= (is_string($value)) ? '\'' . addslashes($value) . '\',' : $value . ',';
      }
      $query = substr($query , 0, -1) . ') VALUES (' . substr($values,0,-1) . ')';
    }

    $db->fetchAssoc($db->query($query));

    // echo $db->lastInsertedId();die;
    return $db->lastInsertedId();
  }
}
<?php
/** Description of Session
 *
 * @author Lionel Péramo */
namespace lib\myLibs\core;

class Session
{
  private static $id;

  public static function init() { self::$id = \sha1(\time()); }

  /** Puts a value associated with a key into the session
   *
   * @param string $key
   * @param mixed  $value
   */
  public static function set($key, $value) { $_SESSION[sha1(self::$id .$key)] = $value; }

  /** Puts all the value associated with the keys of the array into the session
   *
   * @param array $array
   */
  public static function sets(array $array) { foreach($array as $key => $value) $_SESSION[sha1(self::$id .$key)] = $value; }

  /** Retrieves a value from the session via its key
   *
   * @param string $key
   *
   * @return mixed
   */
  public static function get($key) { return $_SESSION[sha1(self::$id . $key)]; }
}
?>
<?php

namespace lib\myLibs\core;
require_once ROOTPATH . 'lib/myLibs/core/Tasks.php';

/** The 'Desc' functions explains the functions "without 'Desc'"
 *
 * @author Lionel Péramo */
class Tasks_Manager
{
  /**
   * List the available commands
   *
   * @param string $message The message to display before showing the commands
   */
  public static function showCommands($message)
  {
    echo PHP_EOL , lightRed() , $message, lightGray(), PHP_EOL, PHP_EOL;
    echo 'The available commmands are : ', PHP_EOL . PHP_EOL, '- ', white(), str_pad('no argument', 25, ' '), lightGray();
    echo ': ', cyan(), 'Shows the available commands.', PHP_EOL, PHP_EOL;

    $methods = get_class_methods('lib\myLibs\core\Tasks');

    foreach ($methods as $method)
    {
      if (false === strpos($method, 'Desc'))
      {
        $methodDesc = $method . 'Desc';
        $paramsDesc = Tasks::$methodDesc();
        echo lightGray(), '- ', white(), str_pad($method, 25, ' '), lightGray(), ': ', cyan(), $paramsDesc[0], PHP_EOL;
        // If we have parameters for this command, displays them
        if(isset($paramsDesc[1]))
        {
          $i = 0;
          foreach($paramsDesc[1] as $parameter => $paramDesc)
          {
            // + parameter : (required|optional) Description
            echo lightBlue(), '   + ', str_pad($parameter, 22, ' '), lightGray();
            echo ': ', cyan(), '(', $paramsDesc[2][$i], ') ', $paramDesc, PHP_EOL;
            ++$i;
          }
        }
        echo PHP_EOL;
      }
    }
    echo endColor();
  }

  /**
   * Executes a function depending on the type of the command
   *
   * @param string $command The command name
   * @param array  $argv    The arguments to pass
   */
  public static function execute($command, array $argv)
  {
    // We have to initialize the database before making any database operations...
//    if (false !== strpos($command, 'sql'))
//      self::initDb();
    define('DS', DIRECTORY_SEPARATOR);
    define('AVT', '..' . DS);

    if(!file_exists(ROOTPATH . 'lib/myLibs/core/ClassMap.php'))
    {
      echo yellow(), 'We cannot use the console if the class mapping file doesn\'t exist ! We launch the generation of this file ...', endColor(), PHP_EOL;
      Tasks::genClassMap();
    }

    require_once ROOTPATH . 'lib/myLibs/core/ClassMap.php';
    spl_autoload_register(function($className) use($classMap){ require $classMap[$className]; });
    Tasks::$command($argv);
  }
}
?>
<?
/** THE framework router
 *
 * @author Lionel Péramo */
namespace lib\myLibs\core;

use lib\myLibs\core\Controller,
    config\Routes;

class Router
{
	/** Retrieve the controller's path that we want or launches the route !
	 *
	 * @param string 			 $route  The wanted route
	 * @param string|array $params Additional params
	 * @param bool 				 $launch True if we have to launch the route or just retrieve the path (do we really need this ?)
	 *
	 * @return string Controller's path
	 */
	public static function get($route = 'index', $params = array(), $launch = true)
	{
		if(!is_array($params))
			$params = array($params);

		extract($chunks = array_combine(array('pattern', 'bundle', 'module', 'controller', 'action'), Routes::$_[$route]['chunks']));
		$chunks['route'] = $route;
		$chunks['css'] = $chunks['js'] = false;
		if(isset(Routes::$_[$route]['resources'])) {
			$resources = Routes::$_[$route]['resources'];
			$chunks['js'] = (isset($resources['bundle_js']) || isset($resources['module_js']) || isset($resources['_js']));
			$chunks['css'] = (isset($resources['bundle_css']) || isset($resources['module_css']) || isset($resources['_css']));
		}

    $controller = (isset(Routes::$_[$route]['core']) ? '' : 'bundles\\') . $bundle . '\\' . $module . '\\controllers\\' . $controller . 'Controller';

		if($launch)
			new $controller($chunks, $params);
		else
			return Routes::$_[$route] . 'Controller'; // TODO
	}

	/** Check if the pattern is present among the routes
	 *
	 * @param string $pattern The pattern to check
	 *
	 * @return bool|array The route and the parameters if they exist, false otherwise
	 */
	public static function getByPattern($pattern)
	{

		foreach(Routes::$_ as $key => $route)
		{
			$route = $route['chunks'][0];

			if(0 === strpos($pattern, $route))
			{
				$params = explode('/', trim(substr($pattern, strlen($route)), '/'));

				if('' == $params[0])
					return array($key, array());

				// We destroy the parameters after ? because we want only rewrited parameters
				$derParam = count($params) - 1;
				$paramsFinal = explode('?', $params[$derParam]);
				$params[$derParam] = $paramsFinal[0];

				return array($key, $params);
			}
		}

		return false;
	}
}
<?php

/**
 * Script_Functions : for now contains instructions for verbose mode
 * @TODO This have to work with different operating systems.
 *
 * @author lionel
 */
namespace lib\myLibs\core;

class Script_Functions
{
  /**
   * Execute a CLI command. (Passthru displays more things than system)
   *
   * @param string  $cmd     Command to pass
   * @param integer $verbose Verbose mode (0,1 or 2)
   *
   * @return bool Status
   */
  public static function cli($cmd, $verbose = 1)
  {
    return (0 < $verbose) ? passthru($cmd) : exec($cmd);
  }
}
?>
<?
// We generate the class mapping file if we need it.
if(!(isset($argv[2]) && '0' == $argv[2]))
  require('GenClassMap.php');

require('ClassMap.php');

$content = '';
foreach($classMap as $class)
{
  $content .= file_get_contents($class);
}

$fp = fopen(ROOTPATH . 'lib/myLibs/core/Bootstrap_comment.php', 'w');
fwrite($fp, $content);
fclose($fp);

$fp = fopen(ROOTPATH . 'lib/myLibs/core/Bootstrap.php', 'w');
fwrite($fp, php_strip_whitespace(ROOTPATH . 'lib/myLibs/core/Bootstrap_comment.php'));
fclose($fp);
?>
<?
/**
 * Mysql management
 *
 * @author Lionel Péramo */

namespace lib\myLibs\core\bdd;
use lib\myLibs\core\Lionel_Exception;

class Mysql
{
  static private $db;

  /**
   * Connects to Mysql
   *
   * @param string $server   Mysql server
   * @param string $username Username
   * @param string $password Password
   *
   * @return bool|resource Returns a MySQL link identifier on success, or false on error
   * @link http://php.net/manual/en/function.mysql-connect.php
   */
  public static function connect($server = 'localhost:3306', $username = 'root', $password = '') {
    return mysql_connect($server, $username, $password);
  }

  /** Connects to a database
   *
   * @param string   $database_name Database name
   * @param resource $link_identifier
   *
   * @return bool True if successful
   * @link http://php.net/manual/en/function.mysql-select-db.php
   */
  public static function selectDb($database_name, $link_identifier) {
    return mysql_select_db($database_name, $link_identifier);
  }

  /**
   * Sends a SQL query !
   *
   * @param string $query SQL query.
   * The query string should not end with a semicolon. Data inside the query should be properly escaped.
   *
   * @return bool|resource Returns a resource on success, or false on error
   * @link http://php.net/manual/en/function.mysql-query.php
   */
  public static function query($query, $link_identifier)
  {
    if (!$result = mysql_query($query, $link_identifier))
    {
      echo(nl2br($query));
      throw new Lionel_Exception('Invalid request : ' . mysql_error());
    }else
      return $result;
  }

  /**
   * Returns the results
   *
   * @param resource $result The query result in an associative array
   *
   * @return array The next result
   * @link http://php.net/manual/en/function.mysql-fetch-assoc.php
   */
  public static function fetchAssoc($result) { return mysql_fetch_assoc($result); }

  /**
   * Fetch a result row as an associative array, a numeric array, or both
   *
   * @param resource $result      The query result
   * @param int      $result_type The type of array that is to be fetched. It's a constant and can take the
   * following values: MYSQL_ASSOC, MYSQL_NUM, and MYSQL_BOTH.
   *
   * @return array The next result
   * @link http://php.net/manual/en/function.mysql-fetch-array.php
   */
  public static function fetchArray($result, $result_type) {
    return mysql_fetch_array($result, $result_type);
  }

  /**
   * Returns the results
   *
   * @param resource $result The query result
   *
   * @return array The next result
   * @link http://php.net/manual/en/function.mysql-fetch-row.php
   */
  public static function fetchRow($result) { return mysql_fetch_row($result); }

  /**
   * Returns the results as an object (simplified version of the existing one)
   *
   * @param resource $result The query result
   * @param string   $class_name [optional] <p>
   * Class name to instantiate, set the properties of and return. Default: returns a stdClass object.</p>
   *
   * @param array    $params [optional] Optional array of parameters to pass to the constructor
   *  for class_name objects.
   *
   * @return array The next result
   * @link http://php.net/manual/en/function.mysql-fetch-object.php
   */
  public static function fetchObject($result, $class_name = null, array $params = array()) {
    return mysql_fetch_object(func_get_args());
  }

  /**
   * Returns all the results in an associative array
   *
   * @param resource $result The query result
   *
   * @return bool|array The results. Returns false if there are no results.
   */
  public static function values($result) {
    if (0 == mysql_num_rows($result))
      return false;

    while ($row = mysql_fetch_assoc($result)) {
      $results[] = $row;
    }

    return $results;
  }

  /**
   * Returns the only expected result.
   *
   * @param resource $result The query result
   *
   * @return bool|mixed The result. Returns false if there are no result.
   */
  public static function single($result){
    if (0 == mysql_num_rows($result))
      return false;

    return mysql_fetch_assoc($result);
  }

  /**
   * Free result memory
   *
   * @param resource $result
   *
   * @return bool Returns true on success or false on failure.
   * @link http://php.net/manual/en/function.mysql-free-result.php
   */
  public static function freeResult($result) { return mysql_free_result($result); }

    /**
   * Returns the results
   *
   * @param resource $result The query result in an object
   *
   * @return array The results
   */
  public static function fetchField($result) { return mysql_fetch_field($result); }

  /**
   * Close MySQL connection
   *
   * @return bool Returns true on success or false on failure
   */
  public static function close($link_identifier) { return mysql_close(); }

  /**
   * Get the ID generated in the last query
   *
   * @return int The ID generated for an AUTO_INCREMENT column by the previous query on success, 0 if the previous query does not generate an AUTO_INCREMENT value, or FALSE if no MySQL connection was established.
   * @link http://php.net/manual/fr/function.mysql-insert-id.php
   */
  public static function lastInsertedId($link_identifier) { return mysql_insert_id($link_identifier); }
}
?>
<?php
/** Main sql management
 *
 * @author Lionel Péramo */

namespace lib\myLibs\core\bdd;

use lib\myLibs\core\Lionel_Exception,
  lib\myLibs\core\Session,
  lib\myLibs\core\bdd\Mysql,
  config\All_Config,
  lib\myLibs\core\Logger;

class Sql
{
  private static $_instance,
    $_sgbds = array('Mysql'),
    $_chosenSgbd,
    $_db,
    $_link_identifier;

  public function __construct($sgbd) { self::$_chosenSgbd = __NAMESPACE__ . '\\' . $sgbd; }

  /** Destructor that closes the connection */
  public function __destruct() { self::close(); }

  /**
   * Retrieves an instance of this class or creates it if it not exists yet
   *
   * @param string $sgbd Kind of sgbd
   */
  public static function getDB($sgbd)
  {
    if(in_array($sgbd, self::$_sgbds))
    {
      if (null == self::$_instance)
      {
        self::$_instance = new Sql($sgbd);
        require($sgbd . '.php');
      }

      extract(All_Config::$dbConnections[Session::get('db')]);
      self::$_db = $db;
      $server = ('' == $port) ? $host : $host . ':' . $port;
      self::$_link_identifier = self::connect($server, $login, $password);

      return self::$_instance;
    }else
      throw new Lionel_Exception('This SGBD doesn\'t exist...yet !', 'E_CORE_ERROR');
  }

  /**
   * Connects to Mysql
   *
   * @param string $server   Mysql server
   * @param string $username Username
   * @param string $password Password
   *
   * @return bool|resource Returns a MySQL link identifier on success, or false on error
   */
  public static function connect($server = 'localhost:3306', $username = 'root', $password = '')
  {
    return call_user_func(self::$_chosenSgbd . '::connect', $server, $username, $password);
  }

  /**
   * Connects to a database
   *
   * @param string $link_identifier Link identifier
   *
   * @return bool True if successful
   */
  public function selectDb()
  {
    $retour = call_user_func(self::$_chosenSgbd . '::selectDb', self::$_db, self::$_link_identifier);
    $this->query('SET NAMES UTF8');

    return $retour;
  }

  /**
   * Sends a SQL query !
   *
   * @param string $query SQL query.
   * The query string should not end with a semicolon. Data inside the query should be properly escaped.
   *
   * @return bool|resource Returns a resource on success, or false on error
   */
  public function query($query)
  {
    if(isset($_SESSION['debuglp_']) && 'Dev' == $_SESSION['debuglp_']){
      $trace = debug_backtrace();

      Logger::logSQLTo(
        (isset($trace[1]['file'])) ? $trace[1]['file'] : $trace[0]['file'],
        (isset($trace[1]['line'])) ? $trace[1]['line'] : $trace[0]['line'],
        $query,
        'sql');
    }

    return call_user_func(self::$_chosenSgbd . '::query', $query, self::$_link_identifier);
  }

  /**
   * Returns the results
   *
   * @param resource $result The query result in an associative array
   *
   * @return array The results
   */
  public function fetchAssoc($result)
  {
    return call_user_func(self::$_chosenSgbd . '::fetchAssoc', $result);
  }

    /**
   * Returns the results
   *
   * @param resource $result The query result
   *
   * @return array The results
   */
  public function fetchArray($result)
  {
    return call_user_func(self::$_chosenSgbd . '::fetchArray', $result);
  }

  /**
   * Returns the results
   *
   * @param resource $result The query result in an object
   *
   * @return array The results
   */
  public static function fetchField($result)
  {
    return call_user_func(self::$_chosenSgbd . '::fetchField', $result);
  }

  /**
   * Returns the results as an object (simplified version of the existing one)
   *
   * @param resource $result The query result
   * @param string   $class_name [optional] <p>
   * Class name to instantiate, set the properties of and return. Default: returns a stdClass object.</p>
   *
   * @param array    $params [optional] Optional array of parameters to pass to the constructor
   *  for class_name objects.
   *
   * @return array The next result
   */
  public static function fetchObject($result, $class_name = null, array $params = array() )
  {
    return call_user_func(self::$_chosenSgbd . '::fetchObject', $result, $class_name, $params);
  }

  /**
   * Returns all the results in an associative array
   *
   * @param resource $result The query result
   *
   * @return bool|array The results. Returns false if there are no results.
   */
  public static function values($result)
  {
    return call_user_func(self::$_chosenSgbd . '::values', $result);
  }

  /**
   * Returns the only expected result.
   *
   * @param resource $result The query result
   *
   * @return bool|mixed The result. Returns false if there are no result.
   */
  public static function single($result){
    return call_user_func(self::$_chosenSgbd . '::single', $result);
  }

  /**
   * Close MySQL connection
   *
   * @return bool Returns true on success or false on failure
   */
  private static function close()
  {
    return call_user_func(self::$_chosenSgbd . '::close', self::$_link_identifier);
  }

    /**
   * Free result memory
   *
   * @param resource $result
   *
   * @return bool Returns true on success or false on failure.
   */
  public static function freeResult($result)
  {
    return call_user_func(self::$_chosenSgbd . '::freeResult', $result);
  }

  /**
   * Return the last inserted id
   *
   * @return int The last inserted id
   */
  public static function lastInsertedId()
  {
    return call_user_func(self::$_chosenSgbd . '::lastInsertedId', self::$_link_identifier);
  }
}
<?
/** A classic MVC production controller class
 *
 * @author Lionel Péramo
 */
namespace lib\myLibs\core;

use config\All_Config,
    lib\myLibs\core\MasterController;

class Controller extends MasterController
{
  public $viewPath = '/'; // index/index/ for indexController and indexAction

  private static $cache_used,
    $css = array(),
    $js = array(),
    $rendered = array();

  /** If the files are in cache, put them directly in $rendered
   *
   * @param array $filesToCheck Files to check in cache
   *
   * @return bool True if ALL the files are in cache, false otherwise
   */
  public function checkCache(array $filesToCheck)
  {
    foreach($filesToCheck as $fileToCheck)
    {
      $templateFile = $this->viewPath . $fileToCheck;

      $cachedFile = parent::getCacheFileName($templateFile);
      if (file_exists($cachedFile))
      {
        self::$rendered[$templateFile] = parent::getCachedFile($cachedFile, true);
        if(!self::$rendered[$templateFile])
          return false;
      }else
        return false;
    }
    return true;
  }

  /** Renders a view. NB: Even is cache is activated, the template can be not fresh !
   *
   * @param string $file      The file to render
   * @param array  $variables Variables to pass
   * @param bool   $ajax      Is this an ajax partial ?
   * @param string $viewPath  Using the view path or not
   *
   * return string parent::$template Content of the template
   */
  public final function renderView($file, array $variables = array(), $ajax = false, $viewPath = true)
  {
    $templateFile = ($viewPath) ? $this->viewPath . $file : $file;
    if(!file_exists($templateFile)){
      require BASE_PATH . '/lib/myLibs/core/Logger.php';
      Logger::log('Problem when loading the file : ' . $templateFile);
      die('Server problem : the file requested doesn\'t exist ! Please wait for the re-establishment of the file, sorry for the inconvenience.');
    }

    // If we already have the template in memory and that it's not empty then we show it
    self::$cache_used = isset(self::$rendered[$templateFile]) && '' != self::$rendered[$templateFile];

    if(self::$cache_used)
      parent::$template = self::$rendered[$templateFile];
    else
    {
      $cachedFile = parent::getCacheFileName($templateFile);
      parent::$template = (!parent::getCachedFile($cachedFile)) ? $this->buildCachedFile($templateFile, $variables, $cachedFile)
                                                                : parent::getCachedFile(parent::getCacheFileName($templateFile), true);
    }

    return parent::$template;
  }

  /** Parses the template file and updates parent::$template
   *
   * @param string $filename
   * @param array  $variables Variables to pass to the template
   * @param sting  $cacheFile The cache file name version of the file
   * @param bool   $layout    If we add a layout or not
   */
  private function buildCachedFile($filename, array $variables, $cachedFile = null, $layout = true)
  {
    extract($variables);
    ob_start();
    require $filename;

    $content = ($layout) ? self::addLayout(ob_get_clean()) : ob_get_clean();

    $routeV = $this->route . VERSION;

    // /!\ We have to put these functions in this order to put the css before ! (in order to optimize the loading)
    $content = preg_replace('/>\s+</', '><',
      (!$layout) ? str_replace('/title>', '/title>', $content)
                 : str_replace('/title>', '/title>'. $this->addCss($routeV), $content . $this->addJs($routeV))); // suppress useless spaces

    // We clear these variables in order to put css and js for other modules that will not be cached (in case there are css and js imported in the layout)
    self::$js = self::$css = array();

    if('cli' == PHP_SAPI)
      return $content;

    if(null != $cachedFile)
    {
      $fp = fopen($cachedFile, 'w');
      fwrite($fp, $content);
      fclose($fp);
    }

    return $content;
  }

  /** Includes the layout */
  private function layout()
  {
    $cachedFile = parent::getCacheFileName('layout.phtml', CACHE_PATH, 'CORE_FRAMEWORK');

    if(!(parent::$layout = parent::getCachedFile(LAYOUT, $cachedFile))) // if it was not in the cache or "fresh"...
      parent::$layout = $this->buildCachedFile(LAYOUT, array(), $cachedFile, false);
  }

  /** Adds a css script to the existing ones
   *
   * @param array $css The css file to add (Array of string)
   */
  protected static function css($css) {
    array_splice(self::$css, count(self::$css), 0, (is_array($css)) ? $css : array($css));
  }

  /** Returns the pre-generated css and the additional concatenated css
   *
   * @param string $routeV Route name plus the version
   *
   * @return string The links to the css files or the style markup with the css inside
   */
  private function addCss($routeV)
  {
    $content = ($this->chkCss) ? '<link rel="stylesheet" href="' . parent::getCacheFileName($routeV, '/cache/css/', '', '.gz') . '" />' : '';

    if(empty(self::$css))
      return $content;

    $allCss = '';

    foreach(self::$css as $css) {
      $allCss .= file_get_contents(self::$path . $css . '.css');
    }

    if($firstTime)
      $allCss .= file_get_contents(parent::getCacheFileName($routeV, CACHE_PATH . 'css/', '', '.css'));

    if(strlen($allCss) < RESOURCE_FILE_MIN_SIZE)
      return '<style>' . $allCss . '</style>';

    $lastFile .= VERSION;
    $fp = fopen(parent::getCacheFileName($routeV, CACHE_PATH . 'css/', '_dyn', '.css'), 'w');
    fwrite($fp, $allCss);
    fclose($fp);

    return $content . '<link rel="stylesheet" href="' . parent::getCacheFileName($routeV, '/cache/css/', '_dyn', '.css') . '" />';
  }

  /** Adds one or more javascript scripts to the existing ones. If the keys are string il will add the string to the link.
   *
   * @param array $js The javascript file to add (Array of strings)
   *
   * @return string The link to the js file or the script markup with the js inside
   */
  protected static function js($js) {
    self::$js = array_merge(self::$js, (is_array($js)) ? $js : array($js));
  }

  /** Returns the pre-generated js and the additional concatenated js
   *
   * @param string $routeV Route name plus the version
   *
   * @return The links to the js files or the script markup with the js inside
   */
  private function addJs($routeV)
  {
    $content = ($this->chkJs) ? '<script src="' . parent::getCacheFileName($routeV, '/cache/js/', '', '.gz') . '" async defer></script>' : '';
    if(empty(self::$js))
      return $content;

    $allJs = '';

    foreach(self::$js as $js)
    {
      $lastFile = $js . '.js';
      ob_start();
      if(false === strpos($lastFile, ('http')))
        echo file_get_contents(parent::$path . $lastFile);
      else{
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $lastFile);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_exec($ch);
        curl_close($ch);
      }
      $allJs .= ob_get_clean();
    }

    if($firstTime)
      $allJs .= file_get_contents(parent::getCacheFileName($routeV, CACHE_PATH . 'js/', '', '.js'));

    if(strlen($allJs) < RESOURCE_FILE_MIN_SIZE)
      return '<script async defer>' . $allJs . '</script>';
    $lastFile .= VERSION;
    // Creates/erase the corresponding cleaned js file
    $fp = fopen(parent::getCacheFileName($routeV, CACHE_PATH . 'js/', '_dyn', '.js'), 'w');
    fwrite($fp, $allJs);
    fclose($fp);

    return $content . '<script src="' . parent::getCacheFileName($routeV, '/cache/js/', '_dyn', '.js') . '" async defer></script>';
  }
}
<?
/**
 * Backend of the LPCMS
 *
 * @author Lionel Péramo */

namespace lib\myLibs\core\controllers;

use lib\myLibs\core\Controller,
    lib\myLibs\core\bdd\Sql,
    lib\myLibs\core\Session,
    config\Router;

class profilerController extends Controller
{
  public function preExecute(){
    if('Dev' !== $_SESSION['debuglp_'])
      die('No hacks.');
  }

  public function indexAction($refresh = false)
  {
    echo '<div id="profiler">
      <div>
        <a id="dbgHideProfiler" role="button" class="lbBtn dbg_marginR5">Hide the profiler</a>
        <a id="dbgClearSQLLogs" role="button" class="lbBtn dbg_marginR5">Clear SQL logs</a>
        <a id="dbgRefreshSQLLogs" role="button" class="lbBtn">Refresh SQL logs</a><br><br>
      </div>
      <div id="dbgSQLLogs">';

    self::writeLogs(BASE_PATH . 'logs/sql.txt');

    echo '</div></div>';
  }

  private static function writeLogs($file)
  {
    if(file_exists($file) && '' != ($contents = file_get_contents($file)))
    {
      $requests = json_decode(substr($contents, 0, -1) . ']', true);
      foreach($requests as $r)
      {
        echo '<div><div class="dbg_leftBlock dbg_fl">In file <span class="dbg_file">', $r['file'], '</span> at line <span class="dbg_line">', $r['line'], '</span>: <p>', $r['query'], '</p></div><a role="button" class="dbg_fr lbBtn">Copy</a></div>';
      }
    }else
      echo 'No stored queries in ', $file, '.';
  }

  public function clearSQLLogsAction()
  {
    $file = BASE_PATH . 'logs/sql.txt';
    $handle = fopen($file, 'r+');
    ftruncate($handle, 0);
    fclose($handle);

    echo 'No more stored queries in ' , $file , '.';
  }

  public function refreshSQLLogsAction() { self::writeLogs(BASE_PATH . 'logs/sql.txt'); }
}
?>
<?
/** A classic MVC development controller class
 *
 * @author Lionel Péramo */
namespace lib\myLibs\core;

use config\All_Config,
    lib\myLibs\core\Logger,
    config\Routes,
    lib\myLibs\core\MasterController;

class Controller extends MasterController
{
  public $viewPath = '/'; // index/index/ for indexController and indexAction

  private static $css = array(),
    $js = array(),
    $rendered = array();

  public function __construct(array $baseParams = array(), array $getParams = array()){
    parent::__construct($baseParams, $getParams);
    Logger::logTo(PHP_EOL . "\tRoute [" . $this->route . "] Patt : " . $this->pattern, 'trace');
  }

  /** If the files are in cache, put them directly in $rendered
   *
   * @param array $filesToCheck Files to check in cache
   *
   * @return bool True if ALL the files are in cache, false otherwise
   */
  public function checkCache(array $filesToCheck) { return false; }

  /** Renders a view. NB: Even is cache is activated, the template can be not fresh !
   *
   * @param string $file      The file to render
   * @param array  $variables Variables to pass
   * @param bool   $ajax      Is this an ajax partial ?
   * @param string $viewPath  Using the view path or not
   *
   * @return string parent::$template Content of the template
   */
  public final function renderView($file, array $variables = array(), $ajax = false, $viewPath = true)
  {
    $templateFile = ($viewPath) ? $this->viewPath . $file : $file;
    Logger::logTo("\t" . 'Ajax : ' . (($ajax) ? 'true' : 'false'), 'trace');

    if (file_exists($templateFile))
      parent::$template = $this->buildCachedFile($templateFile, $variables);
    else
      throw new Lionel_Exception('Erreur : Fichier non trouvé ! : ' , $templateFile);

    if(!$ajax)
      self::addDebugBar(CORE_VIEWS_PATH . DS . 'debugBar.phtml');

    return parent::$template;
  }

  /** Parses the template file and updates parent::$template
   *
   * @param string $filename  The file name
   * @param array  $variables Variables to pass to the template
   * @param sting  $cacheFile The cache file name version of the file
   * @param bool   $layout    If we add a layout or not
   */
  private function buildCachedFile($filename, array $variables, $cachedFile = null, $layout = true)
  {
    extract($variables);

    ob_start();
    require $filename;
    $content = ($layout && !parent::$layoutOnce) ? parent::addLayout(ob_get_clean()) : ob_get_clean();

    Logger::logTo("\t" . 'File : ' . $filename, 'trace');

    // /!\ We have to put these functions in this order to put the css before ! (in order to optimize the loading)
    $content = str_replace(
      '/title>',
      '/title>'. self::addCss($layout),
      $content . self::addJs($layout));

    // We clear these variables in order to put css and js for other modules that will not be cached (in case there are css and js imported in the layout)
    self::$js = self::$css = array();

    return $content;
  }

  /** Adds a debug bar at the top of the template
   *
   * @param string $debugBar Debug bar template
   */
  private function addDebugBar($debugBar)
  {
    ob_start();
    // send variables to the debug toolbar (if debug is active, cache don't)
    require $debugBar;
    parent::$template = (false === strpos(parent::$template, 'body'))
                        ? ob_get_clean() . parent::$template
                        : preg_replace('`(<body[^>]*>)`', '$1' . ob_get_clean(), parent::$template);

    parent::$template = str_replace(
      '/title>',
      '/title>'. self::addCss(false),
      parent::$template . self::addJs(false)); // suppress useless spaces
  }


  /** Includes the layout */
  private function layout() { parent::$layout = $this->buildCachedFile(LAYOUT, array(), null, false); }

  /** Adds a css script to the existing ones
   *
   * @param array $css The css file to add (Array of string)
   */
  protected static function css($css = array())
  {
    if(!is_array($css)) $css = array($css);

    array_splice(self::$css, count(self::$css), 0, $css);
  }

  /** Puts the css into the template
   *
   * @param bool $firstTime If it's not the layout, often the first time we arrive at that function.
   *
   * @return string The links to the css files or the style markup with the css inside
   */
  private function addCss($firstTime)
  {
    $route = Routes::$_;
    $debugContent = '';

    if($firstTime)
    {
      if(isset($route[$this->route])){
        $route = $route[$this->route];
        if(isset($route['resources']))
        {
          $chunks = $route['chunks'];
          $resources = $route['resources'];
          $debLink = "\n" . '<link rel="stylesheet" href="';
          $debLink2 = $debLink . '/bundles/';

          if(isset($resources['first_css'])) {
            foreach($resources['first_css'] as $first_css) {
              $debugContent .= $debLink . $css . '.css" />';
            }
          }
          if(isset($resources['bundle_css'])) {
            foreach($resources['bundle_css'] as $bundle_css) {
              $debugContent .= $debLink2 . $chunks[1] . '/resources/css/' . $bundle_css . '.css" />';
            }
          }
          if(isset($resources['module_css'])) {
            foreach($resources['module_css'] as $module_css) {
              $debugContent .= $debLink2 . $chunks[2] . '/resources/css/' . $module_css . '.css" />';
            }
          }
          if(isset($resources['_css'])) {
            foreach($resources['_css'] as $css) {
              $debugContent .= $debLink . $css . '.css" />';
            }
          }
        }
      }
    }

    if(empty(self::$css)) return $debugContent;

    foreach(self::$css as $css) { $debugContent .= "\n" . '<link rel="stylesheet" href="' . $css . '.css" />'; }

    return $debugContent;
  }

  /** Adds one or more javascript scripts to the existing ones. If the keys are string il will add the string to the link.
   *
   * @param array $js The javascript file to add (Array of strings)
   *
   * @return string The link to the js file or the script markup with the js inside
   */
  protected static function js($js = array())
  {
    if(!is_array($js)) $js = array($js);

    self::$js = array_merge(self::$js, $js);
  }

  /** Puts the css into the template. Updates parent::$template.
   *
   * @param bool $firstTime If it's not the layout, often the first time we arrive at that function.
   *
   * @return The links to the js files or the script markup with the js inside
   */
  private function addJs($firstTime)
  {
    $route = Routes::$_;
    $debugContent = '';

    if($firstTime)
    {
      if(isset($route[$this->route])) {
        $route = $route[$this->route];
        if(isset($route['resources']))
        {
          $chunks = $route['chunks'];
          $resources = $route['resources'];
          $debLink = "\n" . '<script src="';
          $debLink2 = $debLink . '/bundles/';

          if(isset($resources['first_js'])) {
            foreach($resources['first_js'] as $first_js) {
              $debugContent .= $debLink .  $first_js . '.js" ></script>';
            }
          }
          if(isset($resources['bundle_js'])) {
            foreach($resources['bundle_js'] as $bundleJs) {
              $debugContent .= $debLink2 . $chunks[1] . '/resources/js/' . $bundleJs . '.js" ></script>';
            }
          }
          if(isset($resources['module_js'])) {
            foreach($resources['module_js'] as $module_js) {
              $debugContent .= $debLink2 . $chunks[2] . '/resources/js/' . $module_js . '.js" ></script>';
            }
          }
          if(isset($resources['_js'])) {
            foreach($resources['_js'] as $js) {
              // var_dump($this->viewJSPath);die;
              $debugContent .= $debLink . $this->viewJSPath . $js . '.js" ></script>';
            }
          }
        }
      }
    }

    if(empty(self::$js)) return $debugContent;

    foreach(self::$js as $key => $js)
    {
      // If the key don't give info on async and defer then put them automatically
      if(is_int($key))
        $key = '';
      $debugContent .= "\n" . '<script src="' . $js . '.js" ' . $key . '></script>';
    }

    return $debugContent;
  }
}
<?
/**
 * Framework database functions
 *
 * @author Lionel Péramo
 */
namespace lib\myLibs\core;

use Symfony\Component\Yaml\Parser,
    Symfony\Component\Yaml\Yaml,
    config\All_Config;

class Database
{
  // Database connection
  private static $host = 'localhost',
    $user = 'root',
    $pwd = 'e94b8f58',
    $base = 'test',
    $motor = 'InnoDB',

    // commands beginning
    $command = '',
    $initCommand = '',

    // paths
    $pathSql = '',
    $pathYml = '',
    $pathYmlFixtures = '',
    $databaseFile = 'database_schema',
    $fixturesFile = 'db_fixture',
    $fixturesFileIdentifiers = 'ids',
    $tablesOrderFile = 'tables_order.yml',

  // just in order to simplify the code
  $attributeInfos = array();

  public static function init()
  {
    define('VERBOSE', All_Config::$verbose);
    $dbConn = All_Config::$dbConnections;
    if(isset($dbConn[key($dbConn)]))
    {
      $infosDb = $dbConn[key($dbConn)];
      self::$user = $infosDb['login'];
      self::$pwd = $infosDb['password'];
      self::$base = $infosDb['db'];
      if(isset($infosDb['motor']))
        self::$motor = $infosDb['motor'];
    }

    self::$pathSql = __DIR__ . DS . AVT . AVT . AVT . 'config' . DS . 'data' . DS;
    self::$pathYml = self::$pathSql . 'yml' . DS;
    self::$pathYmlFixtures = self::$pathYml . 'fixtures' . DS;
    self::$pathSql .= 'sql' . DS;
    self::$tablesOrderFile = self::$pathYml . self::$tablesOrderFile;

    self::$initCommand = 'mysql --show-warnings -h ' . self::$host . ' -u ' . self::$user . ' --password=' . self::$pwd;
    $finCommande = ' -e "source ' . self::$pathSql;
    self::$command = (VERBOSE > 1) ? self::$initCommand . ' -D ' . self::$base . ' -v' . $finCommande
                               : self::$initCommand . ' -D ' . self::$base . $finCommande;
    self::$initCommand .= (VERBOSE > 1) ? ' -v -e "source ' . self::$pathSql
                                        : ' -e "source ' . self::$pathSql;
  }

  /**
   * Runs or creates & runs the database schema file
   *
   * @param string   $databaseName Database name
   * @param bool     $force        If true, we erase the database before the tables creation.
   */
  public static function createDatabase($databaseName, $force = false)
  {
    if ($force)
    {
      self::dropDatabase($databaseName);
      self::generateSqlSchema($databaseName, true);
      Script_Functions::cli(self::$initCommand . self::$databaseFile . '_force.sql "', VERBOSE);
    } else {
      self::generateSqlSchema($databaseName, false);
      Script_Functions::cli(self::$initCommand . self::$databaseFile . '.sql "', VERBOSE);
    }

    echo 'Database created.', PHP_EOL;
  }

  /**
   * Returns the attribute in uppercase if it exists
   *
   * @param string $attr  Attribute
   * @param bool   $show  If we show the information. Default : false
   *
   * @return string $attr Concerned attribute in uppercase
   */
  public static function getAttr($attr, $show = false)
  {
    $output = '';
    if(isset(self::$attributeInfos[$attr]))
    {
      if('notnull' == $attr)
        $attr = 'not null';
      else if('type' == $attr && false !== strpos(self::$attributeInfos[$attr], 'string'))
        return 'VARCHAR'.substr(self::$attributeInfos[$attr], 6);

      $output .= ($show) ? ' '.strtoupper($attr)
                         : strtoupper(self::$attributeInfos[$attr]);
    }

    return $output;
  }

  /**
   * Sort the tables using the foreign keys
   *
   * @param array $theOtherTables Remaining tables to sort
   * @param array $tables         Final sorted tables array
   */
  private static function _sortTableByForeignKeys(array $theOtherTables, &$tables)
  {
    $nextArrayToSort = $theOtherTables;

    foreach($theOtherTables as $key => $properties)
    {
      foreach($properties['relations'] as $relation => $relationProperties)
      {
        $add = (in_array($relation, $tables));
      }

      if($add)
      {
        $tables[] = $key;
        unset($nextArrayToSort[$key]);
      }
    }

    if(0 < count($nextArrayToSort))
      self::_sortTableByForeignKeys ($nextArrayToSort, $tables);
  }

  /**
   * Create the sql content of the wanted fixture
   *
   * @param string $databaseName  The database name to use
   * @param string $file          The fixture file to parse
   * @param array  $schema        The database schema in order to have the properties type
   * @param array  $sortedTables  Final sorted tables array
   * @param array  $fixtureMemory An array that stores foreign identifiers in order to resolve yaml aliases
   * @param bool   $force         True => we have to truncate the table before inserting the fixtures
   */
  public static function createFixture($databaseName, $file, array $schema, array $sortedTables, &$fixtureMemory = array(), $force = false)
  {
    // Gets the fixture data
    $fixturesData = Yaml::parse($file);

    $createdFiles = array();
    $first = true;

    // For each table
    foreach ($fixturesData as $table => $names)
    {
      $createdFile = self::$pathSql . self::$fixturesFile . '_' . $databaseName . '_' . $table . '.sql';
      $createdFiles [$table]= $createdFile;

      $localMemory = array();
      $ymlIdentifiers = $table . ': ' . PHP_EOL;

      if($force)
        self::truncateTable($databaseName, $table);

      if (!file_exists($createdFile))
      {
        //$tableSql = 'USE ' . $databaseName . ';' . PHP_EOL . 'SET NAMES UTF8;' . PHP_EOL . PHP_EOL . 'INSERT INTO `' . $table . '` (' . PHP_EOL;
        $tableSql = 'USE ' . $databaseName . ';' . PHP_EOL . 'SET NAMES UTF8;' . PHP_EOL . PHP_EOL . 'INSERT INTO `' . $table . '` (';
        $values = $properties = array();
        $theProperties = '';

        if(isset($schema[$table]['relations']))
        {
          foreach(array_keys($schema[$table]['relations']) as $relation)
          {
            $datas = Yaml::parse(self::$pathYmlFixtures . self::$fixturesFileIdentifiers . DS . $databaseName . '_' . $relation . '.yml');
            foreach($datas as $key => $data) {
              $fixturesMemory[$key] = $data;
            }
          }
        }

        $i = 1; // The database ids begin to 1 by default

        foreach($names as $name => $properties)
        {
          // Allows to put the properties in disorder in the fixture file
          ksort($properties);

          $ymlIdentifiers .= '  ' . $name . ': ' . $i++ . PHP_EOL;
          //$ymlIdentifiers .= '  ' . $name . ': ' . $i++;
          $localMemory[$name] = $i;

          $theValues = '';
          foreach ($properties as $property => $value)
          {
            // If the property refers to a table name, then we search the corresponding foreign key name
            if ($first)
                $theProperties .= (in_array($property, $sortedTables)) ? '`' . $schema[$table]['relations'][$property]['local'] . '`, '
                                                                       : '`' . $property . '`, ';

            $properties [] = $property;
            if (!in_array($property, $sortedTables))
            {
              // if the value is null
              if(null === $value)
              {
                $tmp = $schema[$table]['columns'][$property];
                $tmpBool = isset($tmp['notnull']);
                if(!$tmpBool || ($tmpBool && false === $tmp['notnull']))
                {
                  if (false !== strpos($tmp['type'], 'string'))
                    $value = ' ';

                  switch($tmp['type'])
                  {
                    case 'timestamp' :
                    case 'integer' : $value = 0;
                                     break;
                  }
                }
              }else if(is_bool($value))
                  $value = ($value) ? 1 : 0;
              else if(is_string($value) && 'integer' == $schema[$table]['columns'][$property]['type'])
                $value = $localMemory[$value];

              $theValues .= (is_string($value)) ? '\''.addslashes($value) . '\', ' : $value . ', ';
            } else
              $theValues .= $fixturesMemory[$property][$value] . ', ';

            $values [] = array($name => $value);
          }

          if ($first)
            $tableSql .= substr($theProperties, 0, -2) . ') VALUES';

          $tableSql .= '(' . substr($theValues, 0, -2) . '),';

          $first = false;
        }

        $tableSql  = substr($tableSql, 0, -1) . ';';

        $fp = fopen($createdFile, 'x' );
        fwrite($fp, $tableSql);
        fclose($fp);

        echo 'File created : ', self::$fixturesFile, '_', $databaseName, '_', $table, '.sql', PHP_EOL;

        $fp = fopen(self::$pathYmlFixtures . self::$fixturesFileIdentifiers . DS . $databaseName . '_' . $table . '.yml', 'w' );
        fwrite($fp, $ymlIdentifiers);
        fclose($fp);
      }else
        echo 'Aborted : the file ' , self::$fixturesFile, '_' , $databaseName , '_' , $table, ',sql', ' already exists.', PHP_EOL;
    }
  }

  /**
   * Creates all the fixtures for the specified database
   *
   * @param string $databaseName Database name !
   * @param bool   $force        If true, we erase the data before inserting
   */
  public static function createFixtures($databaseName, $force = false)
  {
    $folder = '';
    // Looks for the fixtures file
    if ($folder = opendir(self::$pathYmlFixtures))
    {
      // Analyzes the database schema in order to guess the properties types
      $schema = Yaml::parse(self::$pathYml . 'schema.yml');

      $tablesOrder = Yaml::parse(self::$tablesOrderFile);
      $tablesToCreate = array();

      // Browse all the fixtures files
      while(false !== ($file = readdir($folder)))
      {
        if ($file != '.' && $file != '..' && $file != '')
        {
          $file = self::$pathYmlFixtures . $file;
          // If it's not a folder (for later if we want to add some "complex" folder management ^^)
          if (!is_dir($file))
          {
            $tables = self::analyzeFixtures($file);
            // Beautify the array
            foreach ($tables as $table => $file)
            {
              $tablesToCreate[$databaseName][$table] = $file;
            }
          }
        }
      }

      foreach($tablesOrder as $table)
      {
        for ($i = 0, $cptTables = count($tablesToCreate[$databaseName]); $i < $cptTables; $i += 1)
        {
          if(isset($tablesToCreate[$databaseName][$table]))
          {
            $file = $tablesToCreate[$databaseName][$table];
            self::createFixture($databaseName, $file, $schema, $tablesOrder, $fixtureMemory, $force);
            self::executeFixture($databaseName, $table, $file);
            break;
          }
        }
      }
      die;
    }
  }

  /**
   * Executes the sql file for the specified table and database
   *
   * @param string $databaseName The database name
   * @param string $table        The table name
   */
  public static function executeFixture($databaseName, $table)
  {
    Script_Functions::cli(self::$initCommand . self::$fixturesFile . '_' . $databaseName .'_' . $table . '.sql "', VERBOSE);
  }

  /**
   * Drops the database.
   *
   * @param string $databaseName Database name !
   */
  public static function dropDatabase($databaseName)
  {
    $file = 'drop_' . $databaseName.'.sql';
    $pathAndFile = self::$pathSql . $file;

    // If the file that drops database doesn't exist yet...creates it.
    if (!file_exists($pathAndFile))
    {
      exec('echo DROP DATABASE IF EXISTS ' . $databaseName . '; > ' . $pathAndFile);
      echo '\'Drop database\' file created.' , PHP_EOL;
    }

    // And drops the database
    Script_Functions::cli(self::$initCommand . $file . '"', VERBOSE);
    echo 'Database dropped.', PHP_EOL;
  }

  /**
   * Generates the sql schema
   *
   * @param string $databaseName Database name
   * @param bool   $force        If true, we erase the existing tables
   */
  public static function generateSqlSchema($databaseName, $force = false)
  {
    $dbFile = ($force) ? self::$pathSql.self::$databaseFile . '_force.sql'
                       : self::$pathSql.self::$databaseFile . '.sql';
    if (!file_exists($dbFile))
    {
      echo 'The \'sql schema\' file doesn\'t exist. Creates the file...', PHP_EOL;
      $sql = ($force) ? 'CREATE DATABASE '
                      : 'CREATE DATABASE IF NOT EXISTS ';

      $sql .=  $databaseName . ';' . PHP_EOL . PHP_EOL . 'USE ' . $databaseName . ';' . PHP_EOL . PHP_EOL;
//      $sql .= 'SET FOREIGN_KEY_CHECKS = 0;' . PHP_EOL . PHP_EOL;

      // Gets the database schema
      $schema = Yaml::parse(self::$pathYml . 'schema.yml');

      $theOtherTables = $sortedTables = array();
      $constraints = '';

      $tableSql = array();
      // For each table
      foreach($schema as $table => $properties)
      {
        $primaryKeys = array();
        $defaultCharacterSet = '';

        /** TODO CREATE TABLE IF NOT EXISTS ...AND ALTER TABLE ADD CONSTRAINT IF EXISTS ? */
        $tableSql[$table] = 'DROP TABLE IF EXISTS `' . $table . '`;' . PHP_EOL . 'CREATE TABLE `' . $table . '` (' . PHP_EOL;

        // For each kind of data (columns, indexes, etc.)
        foreach($properties as $property => $attributes)
        {
          if('columns' == $property)
          {
            // For each column
            foreach ($attributes as $attribute => $informations)
            {
              self::$attributeInfos = $informations;

              $tableSql[$table] .= '  `' . $attribute . '` '
                . self::getAttr('type')
                . self::getAttr('notnull', true)
                . self::getAttr('auto_increment', true)
                . ',' . PHP_EOL;

              if('' != self::getAttr('primary'))
                $primaryKeys[] = $attribute;
            }
          }else if('relations' == $property)
          {
            foreach ($attributes as $otherTable => $attribute)
            {
              // Management of 'ON DELETE XXXX'
              $onDelete = '';
              if(isset($attribute['onDelete']))
                $onDelete = '  ON DELETE '.strtoupper ($attribute['onDelete']);

              $constraints .= 'ALTER TABLE ' . $table . ' ADD CONSTRAINT ' . $attribute['constraint_name']
                . ' FOREIGN KEY(' . $attribute['local'] . ')' . PHP_EOL;
              $constraints .= '  REFERENCES ' . $otherTable . '(' . $attribute['foreign'] . ')' . PHP_EOL
                . $onDelete . ';' . PHP_EOL;
            }

          }else if('indexes' == $property)
          {

          }else if('default_character_set' == $property)
          {
            $defaultCharacterSet = $attributes;
          }
        }
        unset($property, $attributes, $informations, $otherTable, $attribute);

        if(empty($primaryKeys))
          echo 'NOTICE : There isn\'t primary key in ', $table, '!', PHP_EOL;
        else
        {
          $primaries = '`';
          foreach ($primaryKeys as $primaryKey)
          {
            $primaries .= $primaryKey.'`, `';
          }

          $tableSql[$table] .= '  PRIMARY KEY(' . substr($primaries, 0, -3) . ') '. PHP_EOL;
        }
        unset($primaries, $primaryKey);
        $tableSql[$table] .= ('' == $defaultCharacterSet) ? ') ENGINE=' . self::$motor . ' DEFAULT CHARACTER SET utf8' : ') ENGINE=' . self::$motor . ' DEFAULT CHARACTER SET ' . $defaultCharacterSet;

        $tableSql[$table] .= ';' . PHP_EOL . PHP_EOL;

        // Sort the tables by foreign keys associations
        if(isset($properties['relations']))
          $theOtherTables[$table] = $schema[$table];
        else
          $sortedTables[] = $table;
      }
      //$sql .= $constraints. PHP_EOL. 'SET FOREIGN_KEY_CHECKS = 1;' . PHP_EOL;

      self::_sortTableByForeignKeys($theOtherTables, $sortedTables);

      $tablesOrder = '';
      $storeSortedTables = ($force || !file_exists(self::$tablesOrderFile));
      foreach($sortedTables as $sortedTable)
      {
        // We store the names of the sorted tables into a file in order to use it later
        if ($storeSortedTables)
          $tablesOrder .= '- ' . $sortedTable . PHP_EOL;

        $sql .= $tableSql[$sortedTable];
      }

      if($storeSortedTables)
      {
        $fp = fopen(self::$tablesOrderFile, 'w' );
        fwrite($fp, $tablesOrder);
        fclose($fp);

        echo '\'Tables order\' file created.' , PHP_EOL;
      }

      $fp = fopen($dbFile, 'w');
      fwrite($fp, $sql);
      fclose($fp);

      echo '\'SQL schema\' file created.', PHP_EOL;
    }else
      echo 'The \'SQL schema\' file already exists.', PHP_EOL;
  }

  /**
   * Truncates the specified table in the specified database
   *
   * @param string $databaseName Database name
   * @param string $tableName    Table name
   */
  public static function truncateTable($databaseName, $tableName)
  {
    $file = 'truncate_' . $databaseName . '_' . $tableName . '.sql';
    $pathAndFile = self::$pathSql . $file;

    // If the file that truncates the table doesn't exist yet...creates it.
    if (!file_exists($pathAndFile))
    {
      $fp = fopen($pathAndFile, 'x');
      fwrite($fp, 'USE '. $databaseName . ';' . PHP_EOL . 'TRUNCATE TABLE ' . $tableName . ';');
      fclose($fp);
      echo '\'Truncate table\' file created.' , PHP_EOL;
    }

    // And truncates the table
    Script_Functions::cli(self::$initCommand . $file . '"', VERBOSE);
    echo 'Table truncated.', PHP_EOL;
  }

  /**
   * Analyze the fixtures contained in the file and return the found table names
   *
   * @param string $file Fixture file name to analyze
   *
   * @return array The found table names
   */
  private static function analyzeFixtures($file)
  {
    // Gets the fixture data
    $fixturesData = Yaml::parse($file);

    // For each table
    foreach (array_keys($fixturesData) as $table)
    {
      $tablesToCreate[$table]= $file;
    }

    return $tablesToCreate;
  }
}
?>
<?php
namespace lib\myLibs\core;

/** Simple logger class
 *
 * @author Lionel Péramo
 */

class Logger
{
	/** Returns the date or also the ip address and the browser if different
	 * @return string
	 */
	private static function logIpTest()
	{
		$_SESSION['_date'] = $_SESSION['_ip'] = $_SESSION['_browser'] = '';
		if(!isset($_SESSION['_date']))
			$_SESSION['_date'] = $_SESSION['_ip'] = $_SESSION['_browser'] = '';

		$infos = '';
		$date = date(DATE_ATOM, time());
		if($date != $_SESSION['_date'])
			$infos .= '[' . ($_SESSION['_date'] = $date) . '] ';

		if($_SERVER['REMOTE_ADDR'] != $_SESSION['_ip'])
			$infos .= $infos . '[' . ($_SESSION['_ip'] = $_SERVER['REMOTE_ADDR']) . '] ';

		if($_SERVER['HTTP_USER_AGENT'] != $_SESSION['_browser'])
			return $infos . '[' .  ($_SESSION['_browser'] = $_SERVER['HTTP_USER_AGENT']) . '] ';


		return $infos;
	}

	/** Appends a message to the log file at logs/log.txt */
	public static function log($message) {
		error_log(self::logIpTest() . $message . "\n", 3,  __DIR__ . '/../../../logs/log.txt');
	}

	/** Appends a message to the log file at the specified path appended to __DIR__ */
	public static function logToPath($message, $path = '') {
		error_log(self::logIpTest() . $message . "\n", 3,  __DIR__ . $path . '.txt');
	}

	/** Appends a message to the log file at the specified path into logo path */
	public static function logTo($message, $path = '') {
		error_log(self::logIpTest() . $message . "\n", 3,  __DIR__ . '/../../../logs/' . $path . '.txt');
	}

  public static function logSQLTo($file, $line, $message, $path = '')
  {
    $path = __DIR__ . '/../../../logs/' . $path . '.txt';
    error_log(((! ($content = file_get_contents($path)) || '' == $content) ? '[' : '') . '{"file":"' . $file . '","line":' . $line . ',"query":"' . preg_replace('/\s\s+/', ' ', str_replace(array("\r", "\r\n", "\n"), '', trim($message))) . '"},', 3, $path);
  }
}
?>
<?
/** MVC master controller class
 *
 * @author Lionel Péramo
 */
namespace lib\myLibs\core;

class MasterController{
  public static $path;

  protected $bundle = '',
    $module = '',
    $controller = '',
    $action = '',
    $route,
    $getParams = '',
    $viewCSSPath = '/', // CSS path for this module
    $viewJSPath = '/', // JS path for this module
    $pattern = '';

  protected static $id,
  /* @var string $template The actual template being processed */
    $template,
    $layout,
    $body,
    $bodyAttrs,
    $layoutOnce = false;

  /**
   * @param array $baseParams [
   *  'bundle' => $bundle,
   *  'controller' => $controller,
   *  'action' => $action]
   *
   * @param array $getParams The params passed by GET method
   */
  public function __construct(array $baseParams = array(), array $getParams = array())
  {
    // If a controller is specified (in the other case, the calling controller is the Bootstrap class)
    if(isset($baseParams['controller']))
    {
      // Stores the bundle, module, controller and action for later use
      list($this->pattern, $this->bundle, $this->module, $this->controller, , $this->route, $this->chkJs, $this->chkCss) = array_values($baseParams);

      $this->action = substr($baseParams['action'], 0, -6);

      self::$id = $this->bundle . $this->module . $this->controller . $this->action;
      $this->getParams = $getParams;

      $mainPath = '/bundles/' . $this->bundle . '/' . $this->module . '/';
      // Stores the templates' path of the calling controller
      $this->viewPath = BASE_PATH . $mainPath . 'views/' . $this->controller . '/';
      $this->viewCSSPath = $mainPath .'resources/css/';
      $this->viewJSPath = $mainPath . 'resources/js/';

      self::$path = $_SERVER['DOCUMENT_ROOT'] . '..';

      // runs the preexecute function if exists and then the action
      $this->preExecute();
      // dump($getParams, $baseParams);die;
      call_user_func_array(array($this, $baseParams['action']), $getParams);
    }
  }

  // To overload in the child class (e.g: in articleController)
  public function preExecute(){}

  /** Encodes the value passed as parameter in order to create a cache file name
   *
   * @param string $filename File name to modify
   * @param string $path     File's path
   * @param stirng $prefix   Prefix of the file name
   *
   * @return string The cache file name version of the file
   */
  protected static function getCacheFileName($filename, $path = CACHE_PATH, $prefix = '', $extension = '.cache') {
    return $path . sha1('ca' . $prefix . $filename . 'che') . $extension;
  }

  /** If the file is in the cache and is "fresh" then gets it. WE HAVE TO HAVE All_Config::$cache TO TRUE !!
   *
   * @param string  $cacheFile The cache file name version of the file
   * @param bool    $exists    True if we know that the file exists.
   *
   * @return string|bool $content The cached (and cleaned) content if exists, false otherwise
   */
  protected static function getCachedFile($cachedFile, $exists = false)
  {
    if(($exists || file_exists($cachedFile)) && (filemtime($cachedFile) + CACHE_TIME) > time())
      return file_get_contents ($cachedFile);

    return false;
  }

  /** Replaces the layout body content by the template body content if the layout is set
   *
   * @param string $content Content of the template to process
   */
  protected static function addLayout($content)
  {
    if(isset(self::$layout))
    {
      self::$layoutOnce = true;
      return preg_replace('`(<body[^>]*>)(.*)`s', '$1' . str_replace('$','\\$', $content), self::$layout);
    }else
      return $content;
  }

  /** Sets the body attributes
  *
  * @param string $attrs
  */
  public static function bodyAttrs($attrs = '') { self::$bodyAttrs = $attrs; }

  /** Sets the body content
   *
   * @param string $content
   */
  private static function body($content = '') { self::$body = $content; }

  /** Sets the title of the page
   *
   * @param string $title
   */
  protected static function title($title) {
    self::$layout = (isset(self::$layout))
      ? preg_replace('@(<title>)(.*)(</title>)@', '$1' . $title . '$3', self::$layout)
      : '<title>' . $title . '</title><body>';
  }

  /** Sets the favicons of the site
   *
   * @param string $filename
   * @param string $filenameIE
   */
  protected static function favicon($filename = '', $filenameIE = '')
  {
    echo '<link rel="icon" type="image/png" href="' , $filename , '" />
      <!--[if IE]><link rel="shortcut icon" type="image/x-icon" href="' , $filenameIE . '" /><![endif]-->';
  }
}
<?
$_SESSION['debuglp_'] = 'Dev';
define ('BEFORE', microtime(true));
define('BASE_PATH', substr(__DIR__, 0, -15)); // Finit avec /
require '../lib/myLibs/core/Debug_Tools.php';

if('out' == $_GET['d'])
	unset($_SESSION['debuglp_']);

use lib\myLibs\core\Router,
    config\Routes,
    lib\myLibs\core\Lionel_Exception,
    config\All_Config;

ob_start();

define('DS', DIRECTORY_SEPARATOR);
ini_set('display_errors', 1);
ini_set('html_errors', 1);
ini_set('error_reporting', -1);
error_reporting(-1);
// We load the class mapping
require BASE_PATH . 'lib/myLibs/core/ClassMap.php';
//var_dump($classMap['bundles\CMS\backend\controllers\indexController']);die;
spl_autoload_register(function($className) use($classMap){ require $classMap[$className]; });

function errorHandler($errno, $message, $file, $line, $context) { throw new Lionel_Exception($message, $errno, $file, $line, $context); }
set_error_handler('errorHandler');
define('XMODE', 'dev');

ob_get_clean();

function t($texte){ echo $texte; }

try{
  header('Content-Type: text/html; charset=utf-8');
  header("Vary: Accept-Encoding,Accept-Language");

  // if the pattern is in the routes, launch the associated route
  if($route = Router::getByPattern($_SERVER['REQUEST_URI']))
  {
    call_user_func('bundles\\' . Routes::$default['bundle'] . '\\Init::Init');
    Router::get($route[0], $route[1]);
  }
}catch(Exception $e){
  echo $e->errorMessage();
  return false;
}
<?
/** Class mapping generation task
 *
 * @author Lionel Péramo */
$dirs = array('bundles', 'config', 'lib');
$classes = array();
$processedDir = 0;
foreach ($dirs as $dir){
  list($classes, $processedDir) = iterateCM($classes, ROOTPATH . $dir, $processedDir);
}
ob_start();
var_export($classes);
$classMap = ob_get_clean();
$fp = fopen(ROOTPATH . 'lib/myLibs/core/ClassMap.php', 'w');
fwrite($fp, '<? $classMap = ' . substr(str_replace(array('\\\\', ' ', "\n"), array('\\', '', ''), $classMap), 0, -2) . ');');
fclose($fp);

echo PHP_EOL, green() , 'Class mapping finished.', endColor(), PHP_EOL;
var_dump($classMap); echo PHP_EOL;die;

function iterateCM($classes, $dir, $processedDir)
{
  if ($handle = opendir($dir)) {
      while (false !== ($entry = readdir($handle))) {
        // We check that we process interesting things
        if('.' == $entry || '..' == $entry)
          continue;

        $_entry = $dir . DS . $entry;

        // recursively...
        if(is_dir($_entry))
          list($classes, $processedDir) = iterateCM($classes, $_entry, $processedDir);

        // Only php files are interesting
        $posDot = strrpos($entry, ".");
        if('.php' != (substr($entry, $posDot) ))
          continue;

        $classes[substr(str_replace('/', '\\', $dir), strlen(ROOTPATH)) . '\\' . substr($entry, 0, $posDot)] = $_entry;
      }
      closedir($handle);
      $processedDir += 1;
      echo "\x0d\033[K", 'Processed directories : ', $processedDir, '...';

      return array($classes, $processedDir);
  }

  die ('Problem encountered with the directory : ' . $dir . ' !');
}
<?
function lg($message){
  require_once __DIR__ . '/Logger.php';
  lib\myLibs\core\Logger::logTo($message, 'trace');
};

/* A nice dump function that takes as much parameters as we want to put */
function dump()
{
	echo '<pre>';
	foreach (func_get_args() as $param)
	{
    var_dump(is_string($param) ? htmlspecialchars($param) : $param);
    echo '<br />';
	}
	echo '</pre>';
}

/**
 * Puts new lines in order to add lisibility to a code in debug mode
 *
 * @param string $stringToFormat The ... (e.g. : self::$template
 *
 * @return string The formatted string
 */
function reformatSource($stringToFormat)
{
  return preg_replace('@&gt;\s*&lt;@', "&gt;<br/>&lt;", htmlspecialchars($stringToFormat));
}

/** Converts a php array into stylish html table
 *
 * @param $dataToShow array  Array to convert
 * @param $title      string Table name to show in the header
 * @param $indexToExclude string Index to exclude from the render
 */
function convertArrayToShowable(&$dataToShow, $title, $indexToExclude = null){
    ob_start();?>
    <table class="radius test">
      <thead>
        <tr class="head">
          <th colspan="3"><?= $title ?></th>
        </tr>
        <tr class="head">
          <th>Name</th>
          <th>Index or value if array</th>
          <th>Value if array</th>
        </tr>
      </thead>
      <tbody>
    <?
      recurArrayConvertTab($dataToShow, $indexToExclude);
    ?></tbody></table><?
    $dataToShow = ob_get_clean();
}

/** Recursive function that converts a php array into a stylish tbody
*
* @param $donnees        array|object  Array or object to convert
* @param $indexToExclude string        Index to exclude from the render
* @param $boucle         int           Number of recursions
*/
function recurArrayConvertTab($donnees, $indexToExclude = null, $boucle = -1){
  $i = 0;
  $oldBoucle = $boucle;
  ++$boucle;
  foreach($donnees as $index => &$donnee)
  {
    if($index === $indexToExclude)
    {
      // foreach(array_keys($donnees[$index]) as $key) { unset($donnees[$key]); }
      // unset($donnees[$index]);
      continue;
    }

    if($boucle == 0)
    {
      echo '</tbody></table><table class="test"><tbody>';
    }
    if(is_array($donnee) || is_object($donnee))
    {
        if(1 == $boucle){
          if($boucle < $oldBoucle){
            echo '<tr class="foldable"><td colspan="' , $boucle , '"></td><td>\'' , $index, '\'</td></tr>';
          }
          else
            echo '<td>\'' , $index, '\'</td><td colspan="0" class="dummy"></td></tr>';
        }else if($boucle > 1)
          echo '<tr class="foldable"><td colspan="', $boucle, '"></td><td colspan="0">\'' , $index,  '\'</td><td colspan="0" class="dummy"></td></tr>';
        else
          echo '<tr class="foldable"><td>\'' , $index, '\'</td>';

        $oldBoucle = recurArrayConvertTab($donnee, $indexToExclude, $boucle);

        // if($boucle + 1 < $oldBoucle)
        //   echo $boucle, $oldBoucle, '</tr></tbody></table>';
    }else
    {
      if(0 == $boucle){
        echo '<tr class="foldable" ><td>\'', $index, '\'</td><td colspan="2">\'', $donnee , '\'</td></tr>';
      }else{
        if(is_object($donnee))
          $donnee = 'This is an Object non renderable !!';
        echo '<tr class="deepContent"><td colspan="' , $boucle , '"></td><td>\'', $index, '\'</td><td>\'', $donnee , '\'</td></tr>';
      }
    }
    $i += 1;
  }
  return $oldBoucle;
}

function debug($noErrors = true){ if($noErrors) error_reporting(0); return (isset($_SESSION['debuglp_']) && $_SESSION['debuglp_'] == 'Dev');}
?>
<?
define('BASE_PATH', substr(__DIR__, 0, -16)); // Finit avec /
require BASE_PATH . '/config/Routes.php';
require BASE_PATH . '/lib/myLibs/core/Router.php';
require_once BASE_PATH . '/config/All_Config.php';
require BASE_PATH . '/lib/packerjs/JavaScriptPacker.php';

$routes = \config\Routes::$_;

// If we ask just for only one route
if(isset($argv[3]))
{
  $theRoute = $argv[3];
  if(isset($routes[$theRoute])){
    echo PHP_EOL, 'Cleaning the resources cache...';
    $mask = (isset($argv[2])) ? $argv[2] + 0 : 7;

    $routes = array($theRoute => $routes[$theRoute]);
    // Cleaning the files specific to the route passed in parameter
    $shaName = sha1('ca' . $theRoute . VERSION . 'che');

    if($mask & 1){
      $file = CACHE_PATH . 'tpl/' . $shaName . '.gz';
      if(file_exists($file))
        unlink($file);
    }

    if(($mask & 2) >> 1)
    {
      $file = CACHE_PATH . 'css/' . $shaName . '.gz';
      if(file_exists($file))
        unlink($file);
    }

    if(($mask & 4) >> 2)
    {
      $file = CACHE_PATH . 'js/' . $shaName . '.gz';
      if(file_exists($file))
        unlink($file);
    }
    echo green(), ' OK', PHP_EOL, endColor();
  } else
    dieC('yellow', PHP_EOL . 'This route doesn\'t exist !' . PHP_EOL);
}else
{
  echo PHP_EOL, 'Cleaning the resources cache...';
  $mask = (isset($argv[2])) ? $argv[2] + 0 : 7;

  if($mask & 1)
    array_map('unlink', glob(\Config\All_Config::$cache_path . '/tpl/*'));

  if(($mask & 2) >> 1)
    array_map('unlink', glob(\Config\All_Config::$cache_path . '/css/*'));

  if(($mask & 4) >> 2)
    array_map('unlink', glob(\Config\All_Config::$cache_path . '/js/*'));

  echo green(), ' OK', PHP_EOL, endColor();
}

$cptRoutes = count($routes);

echo $cptRoutes , ' route(s) to process. Processing the route(s) ... ' . PHP_EOL;
for($i = 0; $i < $cptRoutes; $i += 1){
  $route = current($routes);
  $name = key($routes);
  next($routes);
  echo lightBlue(), str_pad($name, 25, ' '), lightGray();
  if(!isset($route['resources'])){
    echo status('Nothing to do', 'cyan'), ' =>', green(), ' OK', endColor(), PHP_EOL;
    continue;
  }

  $resources = $route['resources'];
  $chunks = $route['chunks'];
  $shaName = sha1('ca' . $name . VERSION . 'che');
  $bundlePath = BASE_PATH . '/bundles/' . $chunks[1] . '/';

  if(($mask & 2) >> 1)
    echo css($shaName, $chunks, $bundlePath, $resources);
  if(($mask & 4) >> 2)
    echo js($shaName, $chunks, $bundlePath, $resources);
  if($mask & 1)
    echo template($shaName, $name, $resources);

  echo ' => ', green(), 'OK ', endColor(), '[', cyan(), $shaName, endColor(), ']', PHP_EOL;
}

function status($status, $color = 'green'){ return ' [' . $color() . $status . lightGray(). ']'; }

/**
 * Cleans the css (spaces and comments)
 *
 * @param $content The css content to clean
 *
 * @return string The cleaned css
 */
function cleanCss($content)
{
  $content = preg_replace('@/\*.*?\*/@s', '', $content);
  $content = str_replace(array("\r\n", "\r", "\n", "\t", '  '), '', $content);
  $content = str_replace(array('{ ',' {'), '{', $content);
  $content = str_replace(array(' }','} '), '}', $content);
  $content = str_replace(array('; ',' ;'), ';', $content);
  $content = str_replace(array(', ',' ,'), ',', $content);

  return str_replace(': ', ':', $content);
}

/** Generates the gzipped css files
*
* @param string $shaName    Name of the cached file
* @param array  $chunks     Route site path
* @param string $bundlePath
* @param array  $resources  Resources array from the defined routes of the site
*/
function css($shaName, array $chunks, $bundlePath, array $resources){
  ob_start();
  loadResource($resources, $chunks, 'first_css', $bundlePath);
  loadResource($resources, $chunks, 'bundle_css', $bundlePath, '');
  loadResource($resources, $chunks, 'module_css', $bundlePath, $chunks[2] . '/');
  loadResource($resources, $chunks, '_css', $bundlePath);
  $allCss = ob_get_clean();

  if('' == $allCss)
    return status('No CSS', 'cyan');

  $allCss = cleanCss($allCss);
  $pathAndFile = CACHE_PATH . 'css/' . $shaName;
  $fp = fopen($pathAndFile, 'w');
  fwrite($fp, $allCss);
  fclose($fp);
  exec('gzip -f -9 ' . $pathAndFile);

  return status('CSS');
}

/**
 *  Generates the gzipped js files
 *
 * @param string $shaName   Name of the cached file
 * @param array  $chunks    Route site path
 * @param string $bundlePath
 * @param array  $resources Resources array from the defined routes of the site
 */
function js($shaName, array $chunks, $bundlePath, array $resources){
  ob_start();
  loadResource($resources, $chunks, 'first_js', $bundlePath);
  loadResource($resources, $chunks, 'bundle_js', $bundlePath, '');
  loadResource($resources, $chunks, 'module_js', $bundlePath . $chunks[2] . '/');
  loadResource($resources, $chunks, '_js', $bundlePath);
  $allJs = ob_get_clean();

  if('' == $allJs)
    return status('No JS', 'cyan');

  $pathAndFile = CACHE_PATH . 'js/' . $shaName;
  $fp = fopen($pathAndFile, 'w');
  fwrite($fp, $allJs);
  fclose($fp);
  // exec('gzip -f -7 ' . $pathAndFile);
  exec('jamvm -Xmx32m -jar ../lib/yuicompressor-2.4.8.jar ' . $pathAndFile . ' -o ' . $pathAndFile . ' --type js; gzip -f -9 ' . $pathAndFile);
  // exec('jamvm -Xmx32m -jar ../lib/compiler.jar --js ' . $pathAndFile . ' --js_output_file ' . $pathAndFile . '; gzip -f -9 ' . $pathAndFile);
  return status('JS');
}

/**
 * Loads css or js resources
 *
 * @param array       $resources
 * @param array       $chunks
 * @param string      $key        first_js, module_css kind of ...
 * @param string      $bundlePath
 * @param string|bool $path
 */
function loadResource(array $resources, array $chunks, $key, $bundlePath, $path = true){
  if(isset($resources[$key]))
  {
    $type = substr(strrchr($key, '_'), 1);
    $path = $bundlePath . (($path)
      ?  $chunks[2] . '/resources/' . $type . '/'
      : $path . 'resources/' . $type . '/');

    foreach($resources[$key] as $resource)
    {
      if(false === strpos($resource, 'http'))
        echo file_get_contents($path . $resource . '.' . $type);
      else {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $resource . '.' . $type);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_exec($ch);
        curl_close($ch);
      }
    }
  }
}

/**
 * Generates the gzipped css files
 *
 * @param string $shaName   Name of the cached file
 * @param string $route
 * @param array  $resources Resources array from the defined routes of the site
 */
function template($shaName, $route, array $resources){
  if(!isset($resources['template']))
    return status('No TEMPLATE', 'cyan');

  ob_start();
  call_user_func('bundles\\' . \config\Routes::$default['bundle'] . '\\Init::Init');

  \lib\myLibs\core\Router::get($route);
  $content = ob_get_clean();



  $pathAndFile = CACHE_PATH . 'tpl/' . $shaName;
  $fp = fopen($pathAndFile, 'w');
  fwrite($fp, preg_replace('/>\s+</', '><', $content));
  fclose($fp);
  exec('gzip -f -9 ' . $pathAndFile);

  return status('TEMPLATE');
}
<?php

/* By Dean Edwards then Nicolas Martin.
 * KNOWN BUG: erroneous behavior when using escapeChar with a replacement
 * value that is a function
 *
 * # The packed result may be different than with the Dean Edwards
 *   version, but with the same length. The reason is that the PHP
 *   function usort to sort array don't necessarily preserve the
 *   original order of two equal member. The Javascript sort function
 *   in fact preserve this order (but that's not require by the
 *   ECMAScript standard). So the encoded keywords order can be
 *   different in the two results.
 *
 * # Be careful with the 'High ASCII' Level encoding if you use
 *   UTF-8 in your files...
 */
namespace lib\packerjs;

class JavaScriptPacker
{
  // constants
  const IGNORE = '$1';

  // validate parameters
  private $_script = '',
    $_encoding = 62,
    $_fastDecode = true,
    $_specialChars = false,
    $LITERAL_ENCODING = array(
      'None' => 0,
      'Numeric' => 10,
      'Normal' => 62,
      'High ASCII' => 95
    ),
  // keep a list of parsing functions, they'll be executed all at once
    $_parsers = array(),
    $_count = array();

  /**
   * Constructor
   *
   * @param string     $script       The JavaScript to pack
   * @param int|string $encoding     Level of encoding 0,10,62,95 or 'None', 'Numeric', 'Normal', 'High ASCII'.
   * @param bool       $fastDecode   If true, includes the fast decoder in the packed result
   * @param bool       $specialChars If you have flagged your private and local variables in the script
   *
   * @return JavaScriptPacker
   */
  public function __construct($_script, $_encoding = 62, $_fastDecode = true, $_specialChars = false)
  {
    $this->_script = $_script . "\n";
    if (array_key_exists($_encoding, $this->LITERAL_ENCODING))
      $_encoding = $this->LITERAL_ENCODING[$_encoding];
    $this->_encoding = min((int) $_encoding, 95);
    $this->_fastDecode = $_fastDecode;
    $this->_specialChars = $_specialChars;
  }

  public function pack()
  {
    $this->_addParser('_basicCompression');
    if ($this->_specialChars)
      $this->_addParser('_encodeSpecialChars');
    if ($this->_encoding)
      $this->_addParser('_encodeKeywords');

    return $this->_pack($this->_script);
  }

  // apply all parsing routines
  private function _pack($script)
  {
    for ($i = 0; isset($this->_parsers[$i]); $i+=1)
    {
      $script = call_user_func(array(&$this, $this->_parsers[$i]), $script);
    }

    return $script;
  }

  private function _addParser($parser) { $this->_parsers[] = $parser; }

  // zero encoding - just removal of white space and comments
  private function _basicCompression($script)
  {
    $parser = new ParseMaster();
    // make safe
    $parser->escapeChar = '\\';
    // protect strings
    $parser->add('/\'[^\'\\n\\r]*\'/', self::IGNORE);
    $parser->add('/"[^"\\n\\r]*"/', self::IGNORE);
    // remove comments
    $parser->add('/\\/\\/[^\\n\\r]*[\\n\\r]/', ' ');
    $parser->add('/\\/\\*[^*]*\\*+([^\\/][^*]*\\*+)*\\//', ' ');
    // protect regular expressions
    $parser->add('/\\s+(\\/[^\\/\\n\\r\\*][^\\/\\n\\r]*\\/g?i?)/', '$2'); // IGNORE
    $parser->add('/[^\\w\\x24\\/\'"*)\\?:]\\/[^\\/\\n\\r\\*][^\\/\\n\\r]*\\/g?i?/', self::IGNORE);
    // remove: ;;; doSomething();
    if ($this->_specialChars)
      $parser->add('/;;;[^\\n\\r]+[\\n\\r]/');
    // remove redundant semi-colons
    $parser->add('/\\(;;\\)/', self::IGNORE); // protect for (;;) loops
    $parser->add('/;+\\s*([};])/', '$2');
    // apply the above
    $script = $parser->exec($script);

    // remove white-space
    $parser->add('/(\\b|\\x24)\\s+(\\b|\\x24)/', '$2 $3');
    $parser->add('/([+\\-])\\s+([+\\-])/', '$2 $3');
    $parser->add('/\\s+/', '');

    return $parser->exec($script);
  }

  private function _encodeSpecialChars($script)
  {
    $parser = new ParseMaster();
    // replace: $name -> n, $$name -> na
    $parser->add('/((\\x24+)([a-zA-Z$_]+))(\\d*)/', array('fn' => '_replace_name'));
    // replace: _name -> _0, double-underscore (__name) is ignored
    $regexp = '/\\b_[A-Za-z\\d]\\w*/';
    // build the word list
    $keywords = $this->_analyze($script, $regexp, '_encodePrivate');
    // quick ref
    $encoded = $keywords['encoded'];

    $parser->add($regexp, array(
      'fn' => '_replace_encoded',
      'data' => $encoded
      )
    );

    return $parser->exec($script);
  }

  private function _encodeKeywords($script)
  {
    // escape high-ascii values already in the script (i.e. in strings)
    if ($this->_encoding > 62)
      $script = $this->_escape95($script);
    // create the parser
    $parser = new ParseMaster();
    $encode = $this->_getEncoder($this->_encoding);
    // for high-ascii, don't encode single character low-ascii
    $regexp = ($this->_encoding > 62) ? '/\\w\\w+/' : '/\\w+/';
    // build the word list
    $keywords = $this->_analyze($script, $regexp, $encode);
    $encoded = $keywords['encoded'];

    // encode
    $parser->add($regexp, array(
      'fn' => '_replace_encoded',
      'data' => $encoded
      )
    );

    return (empty($script)) ? $script : $this->_bootStrap($parser->exec($script), $keywords);
  }

  private function _analyze($script, $regexp, $encode)
  {
    /* analyse and retreive all words in the script
     *
     * instances of "protected" words
     * dictionary of word->encoding
     * list of words sorted by frequency
     */
    $_protected = $_encoded = $_sorted = $all = array();
    preg_match_all($regexp, $script, $all);
    $all = $all[0]; // simulate the javascript comportement of global match
    if (!empty($all))
    {
      /* word->count
       * same list, not sorted
       * "protected" words (dictionary of word->"word")
       * dictionary of charCode->encoding (eg. 256->ff)
       */
      $this->_count = $unsorted = $protected = $value = array();

      $i = count($all);
      $j = 0; //$word = null;
      // count the occurrences - used for sorting later
      do
      {
        --$i;
        $word = '$' . $all[$i];
        if (!isset($this->_count[$word]))
        {
          $this->_count[$word] = 0;
          $unsorted[$j] = $word;
          // make a dictionary of all of the protected words in this script
          //  these are words that might be mistaken for encoding
          //if (is_string($encode) && method_exists($this, $encode))
          $values[$j] = call_user_func(array(&$this, $encode), $j);
          $protected['$' . $values[$j]] = $j++;
        }
        // increment the word counter
        $this->_count[$word]++;
      } while ($i > 0);
      // prepare to sort the word list, first we must protect
      //  words that are also used as codes. we assign them a code
      //  equivalent to the word itself.
      // e.g. if "do" falls within our encoding range
      //      then we store keywords["do"] = "do";
      // this avoids problems when decoding
      $i = count($unsorted);
      do
      {
        $word = $unsorted[--$i];
        if (isset($protected[$word]) /* != null */)
        {
          $_sorted[$protected[$word]] = substr($word, 1);
          $_protected[$protected[$word]] = true;
          $this->_count[$word] = 0;
        }
      } while ($i);

      usort($unsorted, array(&$this, '_sortWords'));
      $j = 0;
      // because there are "protected" words in the list
      //  we must add the sorted words around them
      do
      {
        if (!isset($_sorted[$i]))
          $_sorted[$i] = substr($unsorted[$j++], 1);
        $_encoded[$_sorted[$i]] = $values[$i];
      } while (++$i < count($unsorted));
    }

    return array(
      'sorted' => $_sorted,
      'encoded' => $_encoded,
      'protected' => $_protected);
  }

  /**
   * @param type $match1
   * @param type $match2
   * @return type
   */
  private function _sortWords($match1, $match2) { return $this->_count[$match2] - $this->_count[$match1]; }

  /**
   * Builds the boot function used for loading and decoding
   *
   * @param string $packed
   * @param type $keywords
   *
   * @return type
   */
  private function _bootStrap($packed, $keywords)
  {
    $ENCODE = $this->_safeRegExp('$encode\\($count\\)');

    // $packed: the packed script
    $packed = "'" . $this->_escape($packed) . "'";

    // $ascii: base for encoding
    $ascii = min(count($keywords['sorted']), $this->_encoding);
    if (0 == $ascii)
      $ascii = 1;

    // $count: number of words contained in the script
    $count = count($keywords['sorted']);

    // $keywords: list of words contained in the script
    foreach ($keywords['protected'] as $i => $value)
    {
      $keywords['sorted'][$i] = '';
    }
    // convert from a string to an array
    ksort($keywords['sorted']);
    $keywords = "'" . implode('|', $keywords['sorted']) . "'.split('|')";

    $encode = ($this->_encoding > 62) ? '_encode95' : $this->_getEncoder($ascii);
    $encode = $this->_getJSFunction($encode);
    $encode = preg_replace('/_encoding/', '$ascii', $encode);
    $encode = preg_replace('/arguments\\.callee/', '$encode', $encode);
    $inline = '\\$count' . ($ascii > 10 ? '.toString(\\$ascii)' : '');

    // $decode: code snippet to speed up decoding
    if ($this->_fastDecode)
    {
      // create the decoder
      $decode = $this->_getJSFunction('_decodeBody');
      if ($this->_encoding > 62)
        $decode = preg_replace('/\\\\w/', '[\\xa1-\\xff]', $decode);
      // perform the encoding inline for lower ascii values
      elseif ($ascii < 36)
        $decode = preg_replace($ENCODE, $inline, $decode);
      // special case: when $count==0 there are no keywords. I want to keep
      //  the basic shape of the unpacking funcion so i'll frig the code...
      if (0 == $count)
        $decode = preg_replace($this->_safeRegExp('($count)\\s*=\\s*1'), '$1=0', $decode, 1);
    }

    // boot function
    $unpack = $this->_getJSFunction('_unpack');
    if ($this->_fastDecode)
    {
      // insert the decoder
      $this->buffer = $decode;
      $unpack = preg_replace_callback('/\\{/', array(&$this, '_insertFastDecode'), $unpack, 1);
    }
    $unpack = preg_replace('/"/', "'", $unpack);
    if ($this->_encoding > 62)
    // high-ascii, get rid of the word-boundaries for regexp matches
      $unpack = preg_replace('/\'\\\\\\\\b\'\s*\\+|\\+\s*\'\\\\\\\\b\'/', '', $unpack);

    if ($ascii > 36 || $this->_encoding > 62 || $this->_fastDecode)
    {
      // insert the encode function
      $this->buffer = $encode;
      $unpack = preg_replace_callback('/\\{/', array(&$this, '_insertFastEncode'), $unpack, 1);
    } else
    // perform the encoding inline
      $unpack = preg_replace($ENCODE, $inline, $unpack);

    // pack the boot function too
    $unpackPacker = new JavaScriptPacker($unpack, 0, false, true);
    $unpack = $unpackPacker->pack();

    // arguments
    $params = array($packed, $ascii, $count, $keywords);
    if ($this->_fastDecode)
    {
      $params[] = 0;
      $params[] = '{}';
    }
    $params = implode(',', $params);

    // the whole thing
    return 'eval(' . $unpack . '(' . $params . "))\n";
  }

  private $buffer;

  private function _insertFastDecode($match) { return '{' . $this->buffer . ';'; }

  private function _insertFastEncode($match) { return '{$encode=' . $this->buffer . ';'; }

  /**
   * @param int $ascii
   *
   * @return string
   */
  private function _getEncoder($ascii)
  {
    return $ascii > 10 ? $ascii > 36 ? $ascii > 62 ?
          '_encode95' : '_encode62'  : '_encode36'  : '_encode10';
  }

  /**
   * No encoding
   *
   * @param type $charCode
   *
   * @return type
   */
  private function _encode10($charCode) { return $charCode; }

  /**
   * inherent base36 support
   * characters: 0123456789abcdefghijklmnopqrstuvwxyz
   *
   * @param type $charCode
   *
   * @return type
   */
  private function _encode36($charCode) { return base_convert($charCode, 10, 36); }

  /**
   * Hitch a ride on base36 and add the upper case alpha characters
   * characters: 0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ
   *
   * @param type $charCode
   *
   * @return type
   */
  private function _encode62($charCode)
  {
    $res = '';
    if ($charCode >= $this->_encoding)
      $res = $this->_encode62((int) ($charCode / $this->_encoding));

    $charCode = $charCode % $this->_encoding;

    return ($charCode > 35) ? $res . chr($charCode + 29) : $res . base_convert($charCode, 10, 36);
  }

  /**
   * use high-ascii values
   * characters: ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþ
   *
   * @param type $charCode
   *
   * @return type
   */
  private function _encode95($charCode)
  {
    $res = '';
    if ($charCode >= $this->_encoding)
      $res = $this->_encode95($charCode / $this->_encoding);

    return $res . chr(($charCode % $this->_encoding) + 161);
  }

  /**
   *
   * @param string $string
   *
   * @return string
   */
  private function _safeRegExp($string) { return '/' . preg_replace('/\$/', '\\\$', $string) . '/'; }

  /**
   *
   * @param string $charCode
   *
   * @return string The charCode preceded by '_'
   */
  private function _encodePrivate($charCode) { return '_' . $charCode; }

  // protect characters used by the parser
  private function _escape($script) { return preg_replace('/([\\\\\'])/', '\\\$1', $script); }

  // protect high-ascii characters already in the script
  private function _escape95($script)
  {
    return preg_replace_callback(
        '/[\\xa1-\\xff]/', array(&$this, '_escape95Bis'), $script
    );
  }

  private function _escape95Bis($match) { return '\x' . ((string) dechex(ord($match))); }

  private function _getJSFunction($aName)
  {
    return (defined('self::JSFUNCTION' . $aName)) ? constant('self::JSFUNCTION' . $aName) : '';
  }

  // JavaScript Functions used.
  // Note : In Dean's version, these functions are converted
  // with 'String(aFunctionName);'.
  // This internal conversion complete the original code, ex :
  // 'while (aBool) anAction();' is converted to
  // 'while (aBool) { anAction(); }'.
  // The JavaScript functions below are corrected.
  // unpacking function - this is the boot strap function
  //  data extracted from this packing routine is passed to
  //  this function when decoded in the target
  // NOTE ! : without the ';' final.
  const JSFUNCTION_unpack =
    'function($packed, $ascii, $count, $keywords, $encode, $decode) {
    while ($count--) {
        if ($keywords[$count]) {
            $packed = $packed.replace(new RegExp(\'\\\\b\' + $encode($count) + \'\\\\b\', \'g\'), $keywords[$count]);
        }
    }
    return $packed;
}';

  // code-snippet inserted into the unpacker to speed up decoding
  const JSFUNCTION_decodeBody =
    '    if (!\'\'.replace(/^/, String)) {
        // decode all the values we need
        while ($count--) {
            $decode[$encode($count)] = $keywords[$count] || $encode($count);
        }
        // global replacement function
        $keywords = [function ($encoded) {return $decode[$encoded]}];
        // generic match
        $encode = function () {return \'\\\\w+\'};
        // reset the loop counter -  we are now doing a global replace
        $count = 1;
    }
';
  // zero encoding
  // characters: 0123456789
  const JSFUNCTION_encode10 =
    'function($charCode) {
    return $charCode;
}'; //;';
  // inherent base36 support
  // characters: 0123456789abcdefghijklmnopqrstuvwxyz
  const JSFUNCTION_encode36 =
    'function($charCode) {
    return $charCode.toString(36);
}'; //;';
  // hitch a ride on base36 and add the upper case alpha characters
  // characters: 0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ
  const JSFUNCTION_encode62 =
    'function($charCode) {
    return ($charCode < _encoding ? \'\' : arguments.callee(parseInt($charCode / _encoding))) +
    (($charCode = $charCode % _encoding) > 35 ? String.fromCharCode($charCode + 29) : $charCode.toString(36));
}';

  // use high-ascii values
  // characters: ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþ
  const JSFUNCTION_encode95 =
    'function($charCode) {
    return ($charCode < _encoding ? \'\' : arguments.callee($charCode / _encoding)) +
        String.fromCharCode($charCode % _encoding + 161);
  }';

}

class ParseMaster
{
  public $ignoreCase = false,
    $escapeChar = '';

  // constants
  const EXPRESSION = 0,
    REPLACEMENT = 1,
    LENGTH = 2;

  // used to determine nesting levels
  private $GROUPS = '/\\(/', //g
    $SUB_REPLACE = '/\\$\\d/',
    $INDEXED = '/^\\$\\d+$/',
    $TRIM = '/([\'"])\\1\\.(.*)\\.\\1\\1$/',
    $ESCAPE = '/\\\./', //g
    $QUOTE = '/\'/',
    $DELETED = '/\\x01[^\\x01]*\\x01/', //g
    $_escaped = array(),  // escaped characters
    $_patterns = array(), // patterns stored by index
    $buffer;

  public function add($expression, $replacement = '')
  {
    // count the number of sub-expressions
    //  - add one because each pattern is itself a sub-expression
    $length = 1 + preg_match_all($this->GROUPS, $this->_internalEscape((string) $expression), $out);

    // treat only strings $replacement
    if (is_string($replacement))
    {
      // does the pattern deal with sub-expressions?
      if (preg_match($this->SUB_REPLACE, $replacement))
      {
        // a simple lookup? (e.g. "$2")
        if (preg_match($this->INDEXED, $replacement))
        {
          // store the index (used for fast retrieval of matched strings)
          $replacement = (int) (substr($replacement, 1)) - 1;
        } else
        { // a complicated lookup (e.g. "Hello $2 $1")
          // build a function to do the lookup
          $quote = preg_match($this->QUOTE, $this->_internalEscape($replacement)) ? '"' : "'";
          $replacement = array(
            'fn' => '_backReferences',
            'data' => array(
              'replacement' => $replacement,
              'length' => $length,
              'quote' => $quote
            )
          );
        }
      }
    }
    // pass the modified arguments
    if (!empty($expression))
      $this->_add($expression, $replacement, $length);
    else
      $this->_add('/^$/', $replacement, $length);
  }

  public function exec($string)
  {
    // execute the global replacement
    $this->_escaped = array();

    // simulate the _patterns.toSTring of Dean
    $regexp = '/';
    foreach ($this->_patterns as $reg)
    {
      $regexp .= '(' . substr($reg[self::EXPRESSION], 1, -1) . ')|';
    }
    $regexp = substr($regexp, 0, -1) . '/';
    $regexp .= ($this->ignoreCase) ? 'i' : '';

    $string = $this->_escape($string, $this->escapeChar);
    $string = preg_replace_callback(
      $regexp, array(
      &$this,
      '_replacement'
      ), $string
    );
    $string = $this->_unescape($string, $this->escapeChar);

    return preg_replace($this->DELETED, '', $string);
  }

  /** clear the patterns collection so that this object may be re-used */
  public function reset() { $this->_patterns = array(); }

  /** create and add a new pattern to the patterns collection */
  private function _add()
  {
    $arguments = func_get_args();
    $this->_patterns[] = $arguments;
  }

  /** this is the global replace function (it's quite complicated) */
  private function _replacement($arguments)
  {
    if (empty($arguments))
      return '';

    $i = 1;
    $j = 0;
    // loop through the patterns
    while (isset($this->_patterns[$j]))
    {
      $pattern = $this->_patterns[$j++];
      // do we have a result?
      if (isset($arguments[$i]) && ($arguments[$i] != ''))
      {
        $replacement = $pattern[self::REPLACEMENT];

        if (is_array($replacement) && isset($replacement['fn']))
        {
          if (isset($replacement['data']))
            $this->buffer = $replacement['data'];
          return call_user_func(array(&$this, $replacement['fn']), $arguments, $i);
        } else if (is_int($replacement))
          return $arguments[$replacement + $i];

        $delete = ('' == $this->escapeChar ||
          strpos($arguments[$i], $this->escapeChar) === false) ? '' : "\x01" . $arguments[$i] . "\x01";

        // skip over references to sub-expressions
        return $delete . $replacement;
      } else
        $i += $pattern[self::LENGTH];
    }
  }

  /**
   * @param type $match
   * @param type $offset
   *
   * @return type
   */
  private function _backReferences($match, $offset)
  {
    $replacement = $this->buffer['replacement'];
    $quote = $this->buffer['quote'];
    $i = $this->buffer['length'];
    while ($i)
    {
      $replacement = str_replace('$' . $i--, $match[$offset + $i], $replacement);
    }

    return $replacement;
  }

  /**
   * @param type $match
   * @param type $offset
   *
   * @return type
   */
  private function _replace_name($match, $offset)
  {
    $length = strlen($match[$offset + 2]);
    $start = $length - max($length - strlen($match[$offset + 3]), 0);

    return substr($match[$offset + 1], $start, $length) . $match[$offset + 4];
  }

  /**
   * @param type $match
   * @param type $offset
   *
   * @return type
   */
  private function _replace_encoded($match, $offset) { return $this->buffer[$match[$offset]]; }

  /* php : we cannot pass additional data to preg_replace_callback,
   * and we cannot use &$this in create_function, so let's go to lower level
   *
   * encode escaped characters
   */

  private function _escape($string, $escapeChar)
  {
    if ($escapeChar)
    {
      $this->buffer = $escapeChar;
      return preg_replace_callback(
          '/\\' . $escapeChar . '(.)' . '/', array(&$this, '_escapeBis'), $string
      );
    } else
      return $string;
  }

  /**
   * @param type $match
   *
   * @return type
   */
  private function _escapeBis($match)
  {
    $this->_escaped[] = $match[1];

    return $this->buffer;
  }

  /**
   * Decode escaped characters
   *
   * @param type $string
   * @param type $escapeChar
   *
   * @return type
   */
  private function _unescape($string, $escapeChar)
  {
    if ($escapeChar)
    {
      $regexp = '/' . '\\' . $escapeChar . '/';
      $this->buffer = array('escapeChar' => $escapeChar, 'i' => 0);

      return preg_replace_callback($regexp, array(&$this, '_unescapeBis'), $string);
    } else
      return $string;
  }

  private function _unescapeBis()
  {
    $temp = (isset($this->_escaped[$this->buffer['i']])
      && '' != $this->_escaped[$this->buffer['i']])
      ? $this->_escaped[$this->buffer['i']]
      : '';

    $this->buffer['i']++;

    return $this->buffer['escapeChar'] . $temp;
  }

  /**
   * @param type $string
   *
   * @return type
   */
  private function _internalEscape($string)
  {
    return preg_replace($this->ESCAPE, '', $string);
  }
}
?>
