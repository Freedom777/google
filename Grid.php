<?php

/**
 * Created by PhpStorm.
 * User: 7
 * Date: 07-Feb-17
 * Time: 7:57 PM
 */

class Grid {

    const CELL_MUSHROOM = 'M';
    const CELL_TOMATO = 'T';

    public $R = false;
    public $C = false;
    public $L = false;
    public $H = false;



    public $dataAr = [];
    public $fillAr = [];
    public $total = [
        self::CELL_MUSHROOM => 0,
        self::CELL_TOMATO => 0,
    ];
    public $rest = [
        self::CELL_MUSHROOM => 0,
        self::CELL_TOMATO => 0,
        'total' => 0,
    ];
    public $availSlices = [];
    public $availSliceCombinations = [];
    public $lenAvailSlices = [];
    public $completeFlag = false;
    public $failedFlag = false;
    public $keepTrying = false;

    public $curAbsIndex = 0;
    public $pathCombinationsAr = [];
    private $currentCombination = [];
    public $currentSliceIdx = null;
    private $outputFile = 'data/result.out';
    public $totalSteps = 0;


    public $rollbacksCnt = 0;

    public function __construct($filename, $outFilename)
    {
        $this->readFile($filename);
        die('aaa');
        $this->availSliceCombinations = $this->getMultipliers($this->L, $this->H);
        $this->lenAvailSlices = sizeof($this->availSliceCombinations);
        $this->analyze();


        if ( !empty($outFilename) ) {
            $this->outputFile = $outFilename;
        }
    }

    private function readFile($filename) {
        $fileAr = file($filename);
        list($this->R, $this->C, $this->L, $this->H) = explode(' ', $fileAr [0]);
        $this->H = trim($this->H); // Remove line ending
        $dataAr = [];

        for ( $i = 1, $cntI = $this->R ; $i <= $cntI; $i++ ) { // Bypass header
            $this->fillAr [$i - 1] = array_fill(0, $this->C, 0);
            for ( $j = 0, $cntJ = $this->C; $j < $cntJ; $j++ ) {
                $flagMushroom = 0;
                switch ( $fileAr [$i] [$j] ) {
                    case self::CELL_MUSHROOM:
                        ++$this->total [self::CELL_MUSHROOM];
                        $flagMushroom = 1;
                        break;

                    case self::CELL_TOMATO:
                        ++$this->total [self::CELL_TOMATO];
                        break;
                }
                $dataAr [($i - 1)] [$j] = $flagMushroom;
            }
        }

        $this->rest = $this->total;
        $this->rest ['total'] = $this->R * $this->C;
        $this->dataAr = $dataAr;

        return true;
    }

    private function analyze() {
        for ( $i = 0, $cntI = $this->R ; $i < $cntI; $i++ ) {
            for ( $j = 0, $cntJ = $this->C; $j < $cntJ; $j++ ) {
                for ( $k = 0, $cntK = $this->availSliceCombinations; $k < $cntK; $k++ ) {
                    $curCombination = $this->availSliceCombinations [$k];
                    if ( $this->isCellSliceValid([$j, $i, $j + $curCombination [0] - 1, $i + $curCombination [1] - 1]) ) {
                        $this->availSlices [$this->convertRelAbs($i, $j)] [] = $k;
                    }
                }
            }
        }
        die('222');
    }

    private function isCellSliceValid( $coord = [] ) {
        list($y1, $x1, $y2, $x2) = $coord;
        // Grid limits
        if ( $x1 < 0 || $x1 >= $this->C || $x2 < 0 || $x2 >= $this->C || $y1 < 0 || $y1 >= $this->R || $y2 < 0 || $y2 >= $this->R ) {
            return false;
        }

        $cntMushrooms = 0;
        $xLen = $x2 - $x1 + 1;
        $totalRecs = ($y2 - $y1 + 1) * $xLen;
        for ( $i = $y1; $i <= $y2; $i++ ) {
            $cntMushrooms += array_sum(array_slice($this->dataAr [$i], $x1, $xLen));
        }

        // Test for minimum set of ingredients L
        $cntTomatoes = $totalRecs - $cntMushrooms;
        if ( $cntMushrooms < $this->L || $cntTomatoes < $this->L ) {
            return false;
        }

        return true;
    }


    public function saveFile() {
        $resultAr = [
            'completed' => $this->completeFlag,
            'availSlices' => $this->availSlices,
            'pathCombinationsAr' => $this->pathCombinationsAr,
        ];
        file_put_contents($this->outputFile, 'return ' . var_export($resultAr, true) .';');
    }

    protected function getCurKoef() {
        if ( !empty($this->rest [self::CELL_TOMATO]) ) {
            return $this->rest [self::CELL_MUSHROOM] / $this->rest [self::CELL_TOMATO];
        }

        return 0;
    }

    public function display() {
        require 'tpl/header.php';
        require 'tpl/main.php';
    }

    public function getSlice($coord = [], $remove = false) {
        list($y1, $x1, $y2, $x2) = $coord;

        // Init variables
        $resultAr = [];
        $xr = 0;
        $yr = 0;
        $cntMushrooms = 0;
        $totalRecs = 0;

        // Clear filled cells
        if ( !empty($remove) ) {

            for ($i = $y1; $i <= $y2; $i++) {
                for ($j = $x1; $j <= $x2; $j++) {
                    if ( empty($this->fillAr [$i] [$j]) ) {
                        return false;
                    }
                    $resultAr [$yr] [$xr++] = $this->dataAr [$i] [$j];
                    $cntMushrooms += $this->dataAr [$i] [$j];
                    $this->fillAr [$i] [$j] = 0;
                    ++$totalRecs;
                }
                $xr = 0;
                ++$yr;
            }

            $cntTomatoes = $totalRecs - $cntMushrooms;

            $this->rest [self::CELL_MUSHROOM] += $cntMushrooms;
            $this->rest [self::CELL_TOMATO] += $cntTomatoes;
            $this->rest ['total'] += $totalRecs;
        } else {
            // Put slice cells
            for ($i = $y1; $i <= $y2; $i++) {
                for ($j = $x1; $j <= $x2; $j++) {
                    // Check for filling already filled cells
                    if ( !empty($this->fillAr [$i] [$j]) ) {
                        return false;
                    }

                    // $this->fillAr [$i] [$j] = !empty($remove) ? 0 : 1;
                    $resultAr [$yr] [$xr++] = $this->dataAr [$i] [$j];
                    $cntMushrooms += $this->dataAr [$i] [$j];
                    ++$totalRecs;
                }
                $xr = 0;
                ++$yr;
            }
            // Test for minimum set of ingredients L
            $cntTomatoes = $totalRecs - $cntMushrooms;

            // Fill matrix, if success with all conditions
            for ($i = $y1; $i <= $y2; $i++) {
                for ($j = $x1; $j <= $x2; $j++) {
                    $this->fillAr [$i] [$j] = 1;
                }
            }

            $this->rest [self::CELL_MUSHROOM] -= $cntMushrooms;
            $this->rest [self::CELL_TOMATO] -= $cntTomatoes;
            $this->rest ['total'] -= $totalRecs;
        }

        return $resultAr;
    }

    public function getMultipliers($minValue, $maxValue) {
        $resultAr = [];
        // *2, because each of ingredients should exist
        for ( $i = $minValue*2; $i <= $maxValue; $i++ ) {
            for ( $j = 1, $cnt = $i >> 1; $j <= $cnt; $j++ ) {
                if (empty($i % $j)) {
                    $resultAr [] = [$j, $i / $j];
                }
            }
            $resultAr [] = [$i, 1];
        }

        return $resultAr;
    }

    private function getFirstEmptySpace() {
        for ( $i = 0, $cntI = $this->R; $i < $cntI; $i++ ) {
            if ( array_sum($this->fillAr [$i]) < $this->C ) {
                for ( $j = 0, $cntJ = $this->C; $j < $cntJ; $j++ ) {
                    if ( empty($this->fillAr [$i] [$j]) ) {
                        return [$i, $j];
                    }
                }
            }
        }

        return false;
    }

    public function step(){
        die('aaa');
        if ( !$pos = $this->getFirstEmptySpace() ) {
            $this->completeFlag = true;
            return true;
        } else {
            ++$this->totalSteps;
            $i = $pos [0];
            $j = $pos [1];
            $absIdx = $this->convertRelAbs($i, $j);
            $combFound = false;

            if ( !isset($this->pathCombinationsAr [$absIdx]) ) {
                // First available combination for cell
                // reset($this->availSlices [$absIdx]);
                $this->currentSliceIdx = key($this->availSlices [$absIdx]);
            }

            // if ( sizeof($this->pathCombinationsAr [$absIdx]) <= sizeof($this->availSlices) ) { // All available combinations tried
            // if ( $this->currentSliceIdx < $this->lenAvailSlices ) {
            if ( ($availSlice = current($this->availSlices [$absIdx])) !== false ) {
                $this->keepTrying = true;
                // $availSlice = $this->availSlices [$this->currentSliceIdx];
                $this->pathCombinationsAr [$absIdx] = $this->currentSliceIdx;
                next($this->availSlices [$absIdx]);

                $coordAr = [$i, $j, $i + $availSlice [0] - 1, $j + $availSlice [1] - 1];
                $tmpAr = $this->getSlice($coordAr);
                $this->currentCombination = $coordAr;
                if ( is_array($tmpAr) ) {
                    if ( ($this->rest [self::CELL_MUSHROOM] < $this->L || $this->rest [self::CELL_TOMATO] < $this->L) && !empty($this->rest ['total']) ) {
                        $tmpAr = $this->getSlice($coordAr, true);
                        return false;
                    }
                    return true;
                }

            } elseif ( empty($absIdx) ) { // First cell reached with all combinations already taken
                $combFound = true;
                $this->failedFlag = true;
            } else {
                $this->keepTrying = false;
            }

            return $combFound;
        }
    }
/*
    public function isHoleExists() {
        return ;
    }
*/
    public function rollback () {
        ++$this->rollbacksCnt;
        // Clear current
        end($this->pathCombinationsAr);
        $absIdx = key($this->pathCombinationsAr);
        $lastCombIdx = array_pop($this->pathCombinationsAr);
        $lastSlice = $this->availSlices [$lastCombIdx];
        list($j, $i) = $this->convertAbsRel($absIdx);
        $tmpAr = $this->getSlice([$i, $j, $i + $lastSlice [0] - 1, $j + $lastSlice [1] - 1], true);
        if ($this->keepTrying == false) {
            end($this->pathCombinationsAr);
            $absIdx = key($this->pathCombinationsAr);
            list($j, $i) = $this->convertAbsRel($absIdx);
            $lastCombIdx = $this->pathCombinationsAr [$absIdx];
            $lastSlice = $this->availSlices [$lastCombIdx];
            // $lastCombIdx = array_pop($lastSliceCombinationsAr);
            // $lastSlice = $this->availSlices [$lastCombIdx];

            $tmpAr = $this->getSlice([$i, $j, $i + $lastSlice [0] - 1, $j + $lastSlice [1] - 1], true);
            // Forward to next combination
            $this->currentSliceIdx = $lastCombIdx + 1;
        }
    }

    public function convertAbsRel($absVal) {
        $i = $absVal % $this->C;
        $j = (int) ($absVal / $this->C);

        return [$i, $j];
    }

    public function convertRelAbs($i, $j) {
        return $i*$this->C + $j;
    }

    public function output() {
        $resultAr = [];

        for ( $i = 0, $cntI = $this->R; $i < $cntI; $i++ ) {
            for ( $j = 0, $cntJ = $this->C; $j < $cntJ; $j++ ) {
                $resultAr [$i] [$j] = 0;
                if ( !empty($this->fillAr [$i] [$j]) ) { // Filled cell
                    $resultAr [$i] [$j] = 1;
                // Check for current combination
                } else if ( $i >= $this->currentCombination [0] && $i <= $this->currentCombination [2] && $j >= $this->currentCombination [1] && $j <= $this->currentCombination [3] ) {
                    $resultAr [$i] [$j] = 2;
                }
            }
        }

        return $resultAr;
    }
}