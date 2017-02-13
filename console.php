<?php

require_once 'Grid.php';

$Grid = new Grid('data/example.in', 'data/result.out');

while ( !$Grid->completeFlag || !$Grid->failedFlag ) {
    while (!$Grid->step() && !$Grid->keepTrying) {
        $Grid->rollback();
    }
}

if ( $Grid->completeFlag ) {
    $Grid->saveFile();
    echo 'ok';
} else {
    echo 'failed';
}
