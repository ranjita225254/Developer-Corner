<?php

$mat1 = array();
$mat1[0][0] = 2;
$mat1[0][1] = 754;
$mat1[1][0] = 3;
$mat1[1][1] = 1;
$largset = $mat1[0][0];
for ($i = 0; $i < 2; $i++) {
    for ($j = 0; $j < 2; $j++) {
        if ($largset < $mat1[$i][$j]) {
            $largset = $mat1[$i][$j];
        }
    }
}
echo $largset;
