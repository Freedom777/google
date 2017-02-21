<?php

$start = microtime(true);
require 'Grid.php';

$Grid = new Grid('small');
echo 'Grid ... ';
if ($Grid->modeContinue) {
    echo 'Continue' . "\n";
} else {
    echo 'Started' . "\n";
}

while ( !$Grid->completeFlag && !$Grid->failedFlag ) {
    while (!$Grid->step() && $Grid->rollbackFlag) {
        $Grid->rollback();
    }
}
$end = microtime(true);
if ( $Grid->completeFlag ) {
    $Grid->saveFile();
    echo sprintf('%01.3f', $end - $start)  . ' seconds';
} else {
    echo 'failed';
}
