<?

namespace bundles\CMS\models;

use lib\myLibs\Model;

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
?>
