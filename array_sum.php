<?php

$arr = array();
$arr[0][0] = 1;
$arr[0][1] = 2;
$arr[0][2] = 3;
$arr[0][3] = 4;
$arr[1][0] = 6;
$arr[1][1] = 7;
$arr[1][2] = 8;
$arr[1][3] = 9;
$arr[2][0] = 11;
$arr[2][1] = 12;
$arr[2][2] = 13;
$arr[2][3] = 14;
$arr[3][0] = 16;
$arr[3][1] = 17;
$arr[3][2] = 18;
$arr[3][3] = 19;
$arr[4][0] = 21;
$arr[4][1] = 22;
$arr[4][2] = 23;
$arr[4][3] = 24;
for ($i = 0; $i < 4; $i++) {
    $row = 0;
    $col = 0;
    echo '<br/><br/>';
    for ($j = 0; $j < 4; $j++) {
        echo $arr[$i][$j] . '&nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp';
        $row += $arr[$i][$j];
        $col += $arr[$j][$i];
    }
    echo $row;
    echo '<br/><br/>';
    $columnsum[] = $col;
}
foreach ($columnsum as $colsum) {
    echo $colsum . '&nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp';
}
