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
    ];
    public $availSlices = [];
    public $completeFlag = false;
    public $failedFlag = false;
    public $keepTrying = false;

    public $curAbsIndex = 0;
    public $pathCombinationsAr = [];
    private $currentCombination = [];

    public function __construct($filename)
    {
        $this->readFile($filename);
        $this->availSlices = $this->getMultipliers($this->L, $this->H);
        /*
        for ( $i = 0, $cnt = $this->R * $this->C; $i < $cnt; $i++ ) {
            $this->pathCombinationsAr [$i] = [];
        }
        */
    }

    private function readFile($filename) {
        $fileAr = file($filename);
        list($this->R, $this->C, $this->L, $this->H) = explode(' ', $fileAr [0]);
        $this->H = trim($this->H); // Remove line ending
        $dataAr = [];

        for ( $i = 1, $cntI = $this->R + 1; $i < $cntI; $i++ ) { // Bypass header
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
        $this->dataAr = $dataAr;

        return true;
    }

    public function saveFile($filename) {
        $resultAr = [
            'completed' => $this->completeFlag,
            'availSlices' => $this->availSlices,
            'pathCombinationsAr' => $this->pathCombinationsAr,
        ];
        file_put_contents($filename, 'return ' . var_export($resultAr, true) .';');
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
        $maxX = $this->C - 1;
        $maxY = $this->R - 1;

        // Limit field
        if ( $x1 < 0 || $x1 > $maxX || $x2 < 0 || $x2 > $maxX || $y1 < 0 || $y1 > $maxY || $y2 < 0 || $y2 > $maxY ) {
            return false;
        }

        // Init variables
        $resultAr = [];
        $xr = 0;
        $yr = 0;
        $cntMushrooms = 0;
        $cntTomatoes = $this->H;

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
                }
                ++$yr;
            }

            $cntTomatoes -= $cntMushrooms;

            $this->rest [self::CELL_MUSHROOM] += $cntMushrooms;
            $this->rest [self::CELL_TOMATO] += $cntTomatoes;
        } else {
            // Put slice cells
            $sliceCells = ($x2 - $x1 + 1) * ($y2 - $y1 + 1);
            // Limit by H (max cells by slice)
            if ($sliceCells > $this->H) {
                return false;
            }

            for ($i = $y1; $i <= $y2; $i++) {
                for ($j = $x1; $j <= $x2; $j++) {
                    if (!empty($this->fillAr [$i] [$j])) {
                        return false;
                    }

                    // $this->fillAr [$i] [$j] = !empty($remove) ? 0 : 1;
                    $resultAr [$yr] [$xr++] = $this->dataAr [$i] [$j];
                    $cntMushrooms += $this->dataAr [$i] [$j];
                }
                ++$yr;
            }
            // Test for minimum set of ingredients L
            $cntTomatoes -= $cntMushrooms;
            if ($cntMushrooms < $this->L || $cntTomatoes < $this->L) {
                return false;
            }

            // Fill matrix, if success with all conditions
            for ($i = $y1; $i <= $y2; $i++) {
                for ($j = $x1; $j <= $x2; $j++) {
                    $this->fillAr [$i] [$j] = 1;
                }
            }

            $this->rest [self::CELL_MUSHROOM] -= $cntMushrooms;
            $this->rest [self::CELL_TOMATO] -= $cntTomatoes;
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

    public function showCombination($y1, $x1, $y2, $x2) {
        var_dump($y1, $x1, $y2, $x2); die();
        for ( $i = $y1; $i < $y2; $i++ ) {
            for ( $j = $x1; $j < $x2; $j++ ) {
                echo '$("#cell_' . $i . '_' . $j . '")' . '.css("background-color", "#ff0");' . "\n";
            }
        }
    }

    public function step(){
        if ( !$pos = $this->getFirstEmptySpace()/* && !empty($this->getCurKoef()) && $this->rest [self::CELL_MUSHROOM] > $this->L && $this->rest [self::CELL_TOMATO] > $this->L */) {
            $this->completeFlag = true;
            return true;
        } else {
            $i = $pos [0];
            $j = $pos [1];
            $absIdx = $this->convertRelAbs($i, $j);
            $combFound = false;

            if ( empty($this->pathCombinationsAr [$absIdx]) ) {
                $this->pathCombinationsAr [$absIdx] = [];
            }

            if ( sizeof($this->pathCombinationsAr [$absIdx]) < sizeof($this->availSlices) ) {
                $this->keepTrying = true;
                foreach ( $this->availSlices as $key => $availSlice ) {
                    if ( !in_array($key, $this->pathCombinationsAr [$absIdx]) ) {
                        // Mark, that we already used combination
                        $this->pathCombinationsAr [$absIdx] [] = $key;

                        $coordAr = [$i, $j, $i + $availSlice [0] - 1, $j + $availSlice [1] - 1];
                        $tmpAr = $this->getSlice($coordAr);
                        /*
                        var_dump($i, $j, $i + $availSlice [0] - 1, $j + $availSlice [1] - 1);
                        var_dump($tmpAr);
                        die();
                        */
                        $this->currentCombination = $coordAr;
                        if ( is_array($tmpAr) ) {
                            if ( ($this->rest [self::CELL_MUSHROOM] < $this->L || $this->rest [self::CELL_TOMATO] < $this->L) && $this->getFirstEmptySpace() ) {
                                $tmpAr = $this->getSlice($coordAr, true);
                                return false;
                            }
                            // showCombination($i, $j, $i + $availSlice [1], $j + $availSlice [0]);
                            return true;
                            // $combFound = true;
                            // break;
                        }
                        // Show current combination
                        // break;
                    }
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
        // Clear current
        end($this->pathCombinationsAr);
        $absIdx = key($this->pathCombinationsAr);
        $lastSliceCombinationsAr = array_pop($this->pathCombinationsAr);
        $lastCombIdx = array_pop($lastSliceCombinationsAr);
        $lastSlice = $this->availSlices [$lastCombIdx];
        list($j, $i) = $this->convertAbsRel($absIdx);
        $tmpAr = $this->getSlice([$i, $j, $i + $lastSlice [0] - 1, $j + $lastSlice [1] - 1], true);
        if ($this->keepTrying == false) {
            end($this->pathCombinationsAr);
            $absIdx = key($this->pathCombinationsAr);
            list($j, $i) = $this->convertAbsRel($absIdx);
            end($this->pathCombinationsAr [$absIdx]);
            $lastCombIdx = current($this->pathCombinationsAr [$absIdx]);
            $lastSlice = $this->availSlices [$lastCombIdx];
            // $lastCombIdx = array_pop($lastSliceCombinationsAr);
            // $lastSlice = $this->availSlices [$lastCombIdx];

            $tmpAr = $this->getSlice([$i, $j, $i + $lastSlice [0] - 1, $j + $lastSlice [1] - 1], true);
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