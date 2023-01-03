<?php
declare(strict_types=1);
namespace examples\deployment;
?><!-- this is a comment -->
<?php
echo 'comment';

/**
 * Class Top
 */
class FileToCompress {
  public string $test = 'hello';
  public string $test2 = 'ho';
  public function hello(): void
  {
    if($this->test === 'hello' || $this->test == 'hello' && $this->test2 == 'ho')
      echo $this->test ?? 'hello';
  }
  public static function test(): void{}
}
(new FileToCompress())->hello();
