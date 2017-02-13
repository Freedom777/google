<?php
/**
 * Created by PhpStorm.
 * User: 7
 * Date: 03-Feb-17
 * Time: 4:22 PM
 */

$a = 1;
$b = ++$a;
$c = $a++;
$d = 'c';
$e = $$d;

echo $a . ' ' . $b . ' ' . $c . ' ' . $e;