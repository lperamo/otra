<!DOCTYPE html>
<html>
    <head>
        <title>Page not found !</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link rel="stylesheet" type="text/css" href="/assets/css/404.css" />
    </head>
    <body>
        <div id="error">
          Hmm.. it seems that there is a problem :/ , status code : <?php echo $_SERVER['REDIRECT_STATUS']; ?> !! =&gt; Fail ! <br /><br />
          You're trying to acceed to :

          <?php
          $host = 'http://'.$_SERVER['HTTP_HOST'];
          echo $host.$_SERVER['REQUEST_URI']; ?>
          <br />

          You can go to <a href="<?php echo $host; ?>"><?php echo $host; ?></a> instead
        </div>
    </body>
</html>
