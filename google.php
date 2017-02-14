<?php
session_start();

require 'Grid.php';

if ( empty($_SESSION ['grid']) ) {
    $Grid = new Grid('data/example.in');
} else {
    $Grid = unserialize($_SESSION ['grid']);
}

while ( !$Grid->step() && !$Grid->keepTrying ) {
    $Grid->rollback();
}
$_SESSION ['grid'] = serialize($Grid);

$resultAr = [
    'grid' => [],
    'stop' => 0,
];
if ( $Grid->completeFlag || $Grid->failedFlag ) {
    $resultAr ['stop'] = 1;
    $Grid->saveFile();
} else {
    $resultAr ['grid'] = $Grid->output();
    $resultAr ['rest'] = [
        'mushrooms' => $Grid->rest [$Grid::CELL_MUSHROOM],
        'tomatoes' => $Grid->rest [$Grid::CELL_TOMATO],
        'total' => $Grid->rest ['total'],
    ];
    $resultAr ['rollbacks_cnt'] = $Grid->rollbacksCnt;
    $currentSlice = $Grid->availSliceCombinations [$Grid->currentCellCombIdx];
    $resultAr ['current_slice'] = $currentSlice [0] . ' x ' . $currentSlice [1];
    $resultAr ['steps_cnt'] = $Grid->totalSteps;
    $resultAr ['path'] = implode(', ', array_keys($Grid->pathCombinationsAr));
}

echo json_encode($resultAr);
// var_dump(json_encode($resultAr)); die();
die();
// var_dump($Field->getSlice(0,0, 1,1));

/*
Analyze::readFile('example.in');
var_dump(Analyze::$dataAr);
die();
*/
/*
$val = 24;
foreach ( Analyze::getMultipliers($val) as $i => $j ) {
    echo $val . ' = ' . $i . ' * ' . $j . '<br />';
}
*/