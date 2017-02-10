<?php

require_once 'Grid.php';

$Grid = new Grid('data/example.in');

while ( !$Grid->completeFlag || !$Grid->failedFlag ) {
    while (!$Grid->step() && !$Grid->keepTrying) {
        $Grid->rollback();
    }
}
echo 'ok';