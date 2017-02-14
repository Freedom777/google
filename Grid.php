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
    public $lenAvailSlices = 0;
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
        // var_dump($filename); die();
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
        $this->H = (int) $this->H; // Remove line ending
        $dataAr = [];

        for ( $y = 0, $cntY = $this->R ; $y < $cntY; $y++ ) { // Bypass header
            $this->fillAr [$y] = array_fill(0, $this->C, 0);
            for ( $x = 0, $cntX = $this->C; $x < $cntX; $x++ ) {
                $flagMushroom = 0;
                switch ( $fileAr [$y + 1] [$x] ) {
                    case self::CELL_MUSHROOM:
                        ++$this->total [self::CELL_MUSHROOM];
                        $flagMushroom = 1;
                        break;

                    case self::CELL_TOMATO:
                        ++$this->total [self::CELL_TOMATO];
                        break;
                }
                $dataAr [$y] [$x] = $flagMushroom;
            }
        }

        $this->rest = $this->total;
        $this->rest ['total'] = $this->R * $this->C;
        $this->dataAr = $dataAr;

        return true;
    }

    private function analyze() {
        for ( $y = 0, $cntY = $this->R ; $y < $cntY; $y++ ) { // y
            for ( $x = 0, $cntX = $this->C; $x < $cntX; $x++ ) { // x
                for ( $k = 0, $cntK = sizeof($this->availSliceCombinations); $k < $cntK; $k++ ) {
                    $curCombination = $this->availSliceCombinations [$k];
                    // Check for combination and X length
                    if ( $curCombination [1] > $this->R || $curCombination [0] > $this->C ) {
                        continue;
                    }

                    if ( $this->isCellSliceValid([$y, $x, $y + $curCombination [1] - 1, $x + $curCombination [0] - 1]) ) {
                        $this->availSlices [$this->convertRelAbs($y, $x)] [] = $k;
                    }
                }
            }
        }
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
        for ( $y = $y1; $y <= $y2; $y++ ) {
            $cntMushrooms += array_sum(array_slice($this->dataAr [$y], $x1, $xLen));
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

            for ($y = $y1; $y <= $y2; $y++) {
                for ($x = $x1; $x <= $x2; $x++) {
                    if ( empty($this->fillAr [$y] [$x]) ) {
                        return false;
                    }
                    $resultAr [$yr] [$xr++] = $this->dataAr [$y] [$x];
                    $cntMushrooms += $this->dataAr [$y] [$x];
                    $this->fillAr [$y] [$x] = 0;
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
            for ($y = $y1; $y <= $y2; $y++) {
                for ($x = $x1; $x <= $x2; $x++) {
                    // Check for filling already filled cells
                    if ( !empty($this->fillAr [$y] [$x]) ) {
                        return false;
                    }

                    // $this->fillAr [$y] [$x] = !empty($remove) ? 0 : 1;
                    $resultAr [$yr] [$xr++] = $this->dataAr [$y] [$x];
                    $cntMushrooms += $this->dataAr [$y] [$x];
                    ++$totalRecs;
                }
                $xr = 0;
                ++$yr;
            }
            // Test for minimum set of ingredients L
            $cntTomatoes = $totalRecs - $cntMushrooms;

            // Fill matrix, if success with all conditions
            for ($y = $y1; $y <= $y2; $y++) {
                for ($x = $x1; $x <= $x2; $x++) {
                    $this->fillAr [$y] [$x] = 1;
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
        for ( $y = $maxValue; $y >= $minValue*2; $y-- ) {
            for ( $x = 1, $cnt = $y >> 1; $x <= $cnt; $x++ ) {
                if (empty($y % $x)) {
                    $resultAr [] = [$x, $y / $x];
                }
            }
            $resultAr [] = [$y, 1];
        }

        return $resultAr;
    }

    private function getFirstEmptySpace() {
        for ( $y = 0, $cntY = $this->R; $y < $cntY; $y++ ) {
            if ( array_sum($this->fillAr [$y]) < $this->C ) {
                for ( $x = 0, $cntX = $this->C; $x < $cntX; $x++ ) {
                    if ( empty($this->fillAr [$y] [$x]) ) {
                        return [$x, $y];
                    }
                }
            }
        }

        return false;
    }

    public function step(){
        if ( !$pos = $this->getFirstEmptySpace() ) {
            $this->completeFlag = true;
            return true;
        } else {
            ++$this->totalSteps;
            $y = $pos [0];
            $x = $pos [1];
            $absIdx = $this->convertRelAbs($y, $x);
            $combFound = false;

            if ( !isset($this->pathCombinationsAr [$absIdx]) ) {
                // First available combination for cell
                // reset($this->availSlices [$absIdx]);
                $this->currentSliceIdx = 0; //key($this->availSlices [$absIdx]);
            }

            // if ( sizeof($this->pathCombinationsAr [$absIdx]) <= sizeof($this->availSlices) ) { // All available combinations tried
            // if ( $this->currentSliceIdx < $this->lenAvailSlices ) {
            // if ( ($availSlice = current($this->availSlices [$absIdx])) !== false ) {
            if ( isset($this->availSlices [$absIdx] [$this->currentSliceIdx]) ) {
                $this->keepTrying = true;
                $availSlice = $this->availSlices [$this->currentSliceIdx];
                $this->pathCombinationsAr [$absIdx] = $this->currentSliceIdx++;
                // next($this->availSlices [$absIdx]);

                $coordAr = [$x, $y, $x + $availSlice [1] - 1, $y + $availSlice [0] - 1];
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
        list($x, $y) = $this->convertAbsRel($absIdx);
        $tmpAr = $this->getSlice([$y, $x, $y + $lastSlice [0] - 1, $x + $lastSlice [1] - 1], true);
        if ($this->keepTrying == false) {
            end($this->pathCombinationsAr);
            $absIdx = key($this->pathCombinationsAr);
            list($x, $y) = $this->convertAbsRel($absIdx);
            $lastCombIdx = $this->pathCombinationsAr [$absIdx];
            $lastSlice = $this->availSlices [$lastCombIdx];
            // $lastCombIdx = array_pop($lastSliceCombinationsAr);
            // $lastSlice = $this->availSlices [$lastCombIdx];

            $tmpAr = $this->getSlice([$y, $x, $y + $lastSlice [0] - 1, $x + $lastSlice [1] - 1], true);
            // Forward to next combination
            $this->currentSliceIdx = $lastCombIdx + 1;
        }
    }

    public function convertAbsRel($absVal) {
        $y = $absVal % $this->C;
        $x = (int) ($absVal / $this->C);

        return [$y, $x];
    }

    public function convertRelAbs($y, $x) {
        return $y*$this->C + $x;
    }

    public function output() {
        $resultAr = [];

        for ( $y = 0, $cntY = $this->R; $y < $cntY; $y++ ) {
            for ( $x = 0, $cntX = $this->C; $x < $cntX; $x++ ) {
                $resultAr [$y] [$x] = 0;
                if ( !empty($this->fillAr [$y] [$x]) ) { // Filled cell
                    $resultAr [$y] [$x] = 1;
                // Check for current combination
                } else if ( $y >= $this->currentCombination [0] && $y <= $this->currentCombination [2] && $x >= $this->currentCombination [1] && $x <= $this->currentCombination [3] ) {
                    $resultAr [$y] [$x] = 2;
                }
            }
        }

        return $resultAr;
    }
}