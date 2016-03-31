<?php

for ($i = 0; $i < 6; $i++) {
    echo '<br/>';
    for ($j = 0; $j < $i; $j++) {
        echo '*';
    }
}

/* output is 

*
**
***
****
*****
 
 */
for($i=0;$i<5;$i++)
{
    echo '<br/>';

    for($j=0;$j<$i;$j++)
    {
        echo '*';
    }
}
for($i=5;$i>0;$i--)
{
    echo '<br/>';

    for($j=0;$j<$i;$j++)
    {
        echo '*';
    }
}
/* output is 

*
**
***
****
*****
****
***
**
*
Comment File 
 */

