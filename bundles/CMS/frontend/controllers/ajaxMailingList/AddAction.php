<?
namespace bundles\CMS\frontend\controllers\ajaxMailingList;

use lib\myLibs\Controller,
  bundles\CMS\models\MailingList;

/**
 * LPCMS - Frontend - AjaxArticle - Show
 *
 * @author Lionel Péramo
 */
class addAction extends Controller
{
  public function addAction()
  {
    $email = $_POST['email'];

    if (true === empty($email))
      throw new LionelException('Missing login !');

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
