<?php
$assocarr=array();
$assocarr[]="Ranjita";
$assocarr[]="Gupta";
$assocarr[]="B.tech";
print_r($assocarr);
?>

<?php
$assocarray=array();
$assocarray[0][0]="A";
$assocarray[0][1]="B";
$assocarray[0][2]="C";
$assocarray[1][0]="D";
$assocarray[1][1]="E";
$assocarray[1][2]="F";
$assocarray[2][0]="G";
$assocarray[2][1]="H";
$assocarray[2][2]="I";
print_r($assocarray);
?>


<?php

$assocarray=array();
$assocarray[0][0]="A";
$assocarray[0][1]="B";
$assocarray[0][2]="C";
$assocarray[1][0]="D";
$assocarray[1][1]="E";
$assocarray[1][2]="F";
$assocarray[2][0]="G";
$assocarray[2][1]="H";
$assocarray[2][2]="I";
for($i=0;$i<3;$i++)
{
    echo '<br/>';
    for($j=0;$j<3;$j++)
    {
        echo $assocarray[$i][$j];
        }
}
?>

<?php




$assocarray=array();
$assocarray[0][0]="A";
$assocarray[0][1]="B";
$assocarray[0][2]="C";
$assocarray[0][3]="D";
$assocarray[0][4]="E";
$assocarray[1][0]="F";
$assocarray[1][1]="G";
$assocarray[1][2]="H";
$assocarray[1][3]="I";
$assocarray[1][4]="J";
$assocarray[2][0]="K";
$assocarray[2][1]="L";
$assocarray[2][2]="M";
$assocarray[2][3]="N";
$assocarray[2][4]="O";
$assocarray[3][0]="P";
$assocarray[3][1]="Q";
$assocarray[3][2]="R";
$assocarray[3][3]="S";
$assocarray[3][4]="T";
$assocarray[4][0]="U";
$assocarray[4][1]="V";
$assocarray[4][2]="W";
$assocarray[4][3]="X";
$assocarray[4][4]="y";
for($i=0;$i<5;$i++)
{
    echo '<br/>';
    for($j=0;$j<5;$j++)
    {
        echo $assocarray[$i][$j];
        }
}

