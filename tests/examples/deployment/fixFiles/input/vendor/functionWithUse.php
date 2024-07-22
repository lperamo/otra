<?php
$parameter = '';
$test = function () use (&$parameter) : void
{
  echo $parameter;
};
