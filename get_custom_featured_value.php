<?php 
$redun=Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('additional_description'); 
foreach ($redun as $ajsdj)
{ 
echo $ajsdj['additional_description']; 
}
exit; 
?>
