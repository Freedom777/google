<?php
/**
 * Created by PhpStorm.
 * User: 7
 * Date: 10-Feb-17
 * Time: 9:27 PM
 */

function abc(){
    return __FUNCTION__;
}

function xyz() {
    return abc();
}

echo xyz();