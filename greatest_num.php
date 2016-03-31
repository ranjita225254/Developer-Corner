<?php
/* find the greatest number in an array through program */
$greatest = array(14, 25, 4510, 5, 1);
$largset = $greatest[0];
for ($i = 1; $i <  count($greatest); $i++) {
    if ($largset < $greatest[$i]) {
        $largset = $greatest[$i];
    }
}
echo $largset;

