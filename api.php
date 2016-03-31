<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ApiController
 *
 * @author unicode
 */
class Uni_Warranty_ApiController extends Mage_Core_Controller_Front_Action {

    /**
     * Registration for Customer and Technitions
     */
    public function apiAction() {

        if ($this->getRequest()->getParams()) {
            $storesId = Mage::app()->getStore()->getStoreId();
            $websiteId = Mage::app()->getStore()->getWebsiteId();
            $data = $this->getRequest()->getParams();
            if (isset($_FILES['sstech_profileimage']['name'])) {
                if ($_FILES['sstech_profileimage']['name']) {
                    $mediaPath = Mage::getBaseDir('media') . DS;
                    $folderName = 'customer_image';
                    $sourcePath = $mediaPath . $folderName . "/" . $data['group'];
                    if (!file_exists($sourcePath)) {
                        mkdir($sourcePath, 0777, TRUE);
                        chmod($sourcePath, 0777);
                    }
                    $ext = explode(".", $_FILES['sstech_profileimage']['name']);
                    $newFileName = 'File-' . time() . "." . $ext[1];
                    $finalPath = $sourcePath . "/" . $newFileName;
                    $pathToDb = strstr($finalPath, $data['group']);
                    move_uploaded_file($_FILES['sstech_profileimage']['tmp_name'], $finalPath);
                    $image = $pathToDb;
                }
            }
            if ($data['group'] == 6) {
                $fullName = explode(" ", $data['name']);
                if (count($fullName) > 1) {
                    for ($i = 0; $i < count($fullName) - 1; $i++) {
                        $soManyName[] = $fullName[$i];
                    }
                    $name1 = implode(" ", $soManyName);
                    $name2 = " " . $fullName[count($fullName) - 1];
                } else {
                    $name1 = $fullName[0];
                    $name2 = ' ';
                }
            } else {
                $name1 = $data['firstname'];
                $name2 = $data['lastname'];
            }
//            $client = new SoapClient(Mage::getBaseUrl() . 'api/v2_soap/?wsdl');
//            $session = $client->login('admin', 'admin123');
            $status_flag = 0;
            $customer = Mage::getModel("customer/customer");
            try {
                if (isset($name1, $name2, $data['email'], $storesId, $websiteId, $data['password'], $data['group'])) {
                    $customer->setCustomerId()
                            ->setFirstname($name1)
                            ->setLastname($name2)
                            ->setEmail($data['email'])
                            ->setPassword($data['password'])
                            ->setGroupId($data['group'])
                            ->setStoreId($storesId)
                            ->setWebsiteId($websiteId)
                            ->setAboutYou(isset($data['about_you']) ? $data['about_you'] : " ")
                            ->setMessageToken(isset($data['message_token']) ? $data['message_token'] : " ")
                            ->setDeviceId(isset($data['device_id']) ? $data['device_id'] : " ")
                            ->setLatitude(isset($data['latitude']) ? $data['latitude'] : " ")
                            ->setLongitude(isset($data['longitude']) ? $data['longitude'] : " ")
                            ->setUpdatedAt(date("Y-m-d H:i:s"));
                }
//                        ->setMobNo($data['mob_no']);
                if ($data['group'] == 6) {
                    $customer->setExperience($data['experience']);
                }
                if (isset($image) && $image) {
                    $customer->setSstechProfileimage($image);
                }
                $customer->save();
                $status_flag = 1;
            } catch (Exception $exc) {
                //echo "Unable to create customer....";
                //echo $exc->getTraceAsString();
                $status_flag = 0;
            }

            if ($status_flag == 1) {
                $address = Mage::getModel("customer/address");
                if ($data['group'] == 1) {
                    if (isset($data['zip'], $data['city'], $data['street'], $data['region']) && $data['zip'] && $data['city'] && $data['street'] && $data['region']) {
                        $address->setCustomerId($customer->getId())
                                ->setFirstname($name1)
                                ->setLastname($name2)
                                ->setCountryId('US')
                                ->setRegion($data['region'])
                                ->setPostcode($data['zip'])
                                ->setCity($data['city'])
                                ->setStreet($data['street'])
                                ->setIsDefaultBilling('1')
                                ->setIsDefaultShipping('1')
                                ->setSaveInAddressBook('1');
                    }
                }
                if (isset($data['mob_no']) && $data['mob_no']) {
                    $address->setCustomerId($customer->getId())
                            ->setTelephone($data['mob_no'])
                            ->setIsDefaultBilling('1')
                            ->setIsDefaultShipping('1')
                            ->setSaveInAddressBook('1');
                }
                try {
                    $address->save();
                    // echo $customer->getId();
                    $response['unique_id'] = $customer->getId();
                    $response['name'] = $customer->getFirstname() . " " . $customer->getLastname();
                    $response['message'] = 'Success';
                } catch (Exception $e) {
                    $response['message'] = $e->getTraceAsString();
                }
            } else {
                $response['message'] = "Unable to save details";
            }
            echo json_encode($response);
        }
    }

    /**
     * Login for both Customer and Technition....
     */
    public function loginApiAction($change_user = Null, $change_password = NULL, $change_group = NULL) {
        $session = Mage::getSingleton('customer/session');
        $loginData = $this->getRequest()->getPost();
        if (isset($loginData) && $loginData) {
            if (!empty($loginData['username']) && !empty($loginData['password']) && !empty($loginData['group'])) {
                try {
                    $grop = $loginData['group'];
                    $session->login($loginData['username'], $loginData['password']);
                    $customer1 = $session->getCustomer()->getData();
                    $customer_id = $session->getCustomer()->getId();
                    if ($customer1['group_id'] == $grop) {
//                $result['data']=$customer1;
                        $name = $customer1['firstname'] . " " . $customer1['lastname'];
                        $result['success'] = true;
                        $result['unique_id'] = $customer_id;
                        $result['name'] = $name;
                    } else {
                        $result['error'] = "you do not belong to this group";
                    }
                } catch (Mage_Core_Exception $ex) {
                    switch ($ex->getCode()) {
                        case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD: {
                                $result['error'] = $ex->getMessage();
                                break;
                            }
                    }
                }
                echo json_encode($result);
            } else {
                $errorMsg["erreo"] = "Required Data not found";
                if (isset($errorMsg) && $errorMsg) {
                    echo json_encode($errorMsg);
                }
            }
        }
        if (!empty($change_user) && !empty($change_password) && !empty($change_group)) {
            try {
                $grop = $change_group;
                $session->login($change_user, $change_password);
                $customer1 = $session->getCustomer()->getData();
                $customer_id = $session->getCustomer()->getId();
                if ($customer1['group_id'] == $grop) {
//                $result['data']=$customer1;
                    $name = $customer1['firstname'] . " " . $customer1['lastname'];
                    $result['success'] = true;
                    $result['unique_id'] = $customer_id;
                    $result['name'] = $name;
                } else {
                    $result['error'] = "Wrong Old Password";
                }
            } catch (Mage_Core_Exception $ex) {
                switch ($ex->getCode()) {
                    case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD: {
                            $result['error'] = $ex->getMessage();
                            break;
                        }
                }
            }
            return $result;
        }
    }

    /**
     * return Region of US in json array...
     */
    public function regionAction() {
        $regionCollection = Mage::getModel('directory/region_api')->items('US');
        if (isset($regionCollection) && $regionCollection) {
            foreach ($regionCollection as $row) {
                $jsonRow['states'][] = array("id" => $row['region_id'], "name" => $row['name'], "code" => $row['code']);
            }
            echo Zend_Json::encode($jsonRow);
        }
    }

    /**
     * return type of services in json array
     */
    public function typeAction() {
        $serviceType = Mage::getModel('uni_services/services')->getCollection();
        if (isset($serviceType) && $serviceType) {
            foreach ($serviceType as $allTypes) {
                $jsonServiceTypeData['type'][] = array("id" => $allTypes['id'], "service_name" => $allTypes['service_name']);
            }
            echo json_encode($jsonServiceTypeData);
        }
    }

    /**
     * return all products in json array
     */
    public function productAction() {
        $product = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect("name")->addAttributeToFilter('type_id', array('neq' => 'virtual'));
        if (isset($product) && $product) {
            foreach ($product as $allproduct) {
                $jsonProductData['product'][] = array("entity_id" => $allproduct['entity_id'], "name" => $allproduct['name']);
            }
            echo json_encode($jsonProductData);
        }
    }

    public function serviceChargesAction() {
        $code = $this->getRequest()->getParam('code');
        if (isset($code) && $code) {
            $product = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect("name,zip_code")->addAttributeToFilter("zip_code", array("eq" => $code));
            foreach ($product as $allproduct) {
                $loadedServices = Mage::getModel('catalog/product')->load($allproduct['entity_id']);
                $jsonProductData['product'][] = array("entity_id" => $loadedServices['entity_id'], "name" => $loadedServices['name'], "price" => $loadedServices['price']);
            }
            echo json_encode($jsonProductData);
        } else {
            $codeNotFound['code'] = "Code not found";
            if (isset($codeNotFound) && $codeNotFound) {
                echo json_encode($codeNotFound);
            }
        }
    }

    /**
     * return all brands in json form..
     */
    public function brandAction() {
        $brands = Mage::getModel('brand/brand')->getCollection();
        if (isset($brands) && $brands) {
            foreach ($brands as $brand) {
                $jsonBrands['brand'][] = array("id" => $brand['id'], "brand_name" => $brand['brand_name']);
            }
            echo json_encode($jsonBrands);
        }
    }

    /**
     * return all Types of problem in json form..
     */
    public function problemAction() {
        $problems = Mage::getModel('problem/problem')->getCollection();
        if (isset($problems) && $problems) {
            foreach ($problems as $problem) {
                $jsonProblems['problem'][] = array("id" => $problem['id'], "problem_type" => $problem['problem_type']);
            }
            echo json_encode($jsonProblems);
        }
    }

    /**
     * @see funtion saves the all detail regarding to problem in any machine..
     */
    public function supportAction() {
        $supportData = $this->getRequest()->getPost();
        try {
            $support = Mage::getModel('support/support');
            $support->setProductName($supportData['product']);
            $support->setServiceType($supportData['service']);
            $support->setBrandName($supportData['brand']);
            $support->setProblemType($supportData['problem']);
            $support->setEmail($supportData['email']);
            $support->setSNo($supportData['s_no']);
            $support->setModelNo($supportData['model']);
            $support->save();
            echo "problem recorded successfully";
        } catch (Exception $ex) {
            echo "problem not recorded";
        }
    }

    /**
     * 
     * @param type $emailId
     * @return customer data by email id of customer..
     */
    public function customerByEmailIdAction($emailId) {
        $customer = Mage::getModel("customer/customer");
        $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
        $customer->loadByEmail($emailId);
        return $customer;
    }

    /**
     * @return unique id for customer.
     */
    public function generateUniqueIdAction($eID) {
        $customer = $this->customerByEmailIdAction($eID);
        $uniqyeId = "TM" . date("m/d") . time() . $customer->getId();
        $generatedUniqueId = str_replace("/", "", $uniqyeId);
        return $generatedUniqueId;
    }

    /**
     * to save availability details of technitions from_date To to_date AND from_time To to_time.
     */
    public function availabilityAction() {
        $currentTime = time();
        date("H:i:s", $currentTime);
        $currentDate = strtotime(date('m/d/y'));
        $data = $this->getRequest()->getPost();
        if (isset($data) && $data) {
            $emailTogetData = $data['email'];
            $availability = Mage::getModel('availability/availability');
            if (isset($data['email'], $data['from_date'], $data['to_date'], $data['from_time'], $data['to_time']) && $data['email'] && $data['from_date'] && $data['to_date'] && $data['from_time'] && $data['to_time']) {
                $data['from_date'];
                $fromD = date_create($data['from_date']);
                $toD = date_create($data['to_date']);
                $diff = date_diff($fromD, $toD);
                $numDayCount = $diff->format("%a");
                if ($numDayCount) {
                    for ($d = 0; $d <= $numDayCount; $d++) {
                        $datetocheck = date("m/d/y", strtotime($data['from_date'] . "+$d day"));
                        $timeSlice = array();
                        $forImp = array();
                        $arrToImp = array();
                        $timeSlice = $this->splitTimeSlotAction($data['from_time'], $data['to_time'], $datetocheck);
                        foreach ($timeSlice as $forImp) {
                            $count_loop = 0;
                            $slot_ind_string = '';
                            foreach ($forImp as $impArr) {
                                if ($count_loop == 0) {
                                    $slot_ind_string.=$impArr . "~";
                                } else {
                                    $slot_ind_string.=$impArr;
                                }
                                $count_loop++;
                            }
                            $arrToImp[] = $slot_ind_string;
                        }
                        $timeToSaveInDb = '';
                        $timeToSaveInDb = implode(",", $arrToImp);
                        if (isset($datetocheck)) {
                            $availability = Mage::getModel('availability/availability');
                            $availability->setTechName(isset($data['name']) ? $data['name'] : '')
                                    ->setEmail($data['email'])
                                    ->setFromDate(strtotime($datetocheck))
                                    ->setToDate(strtotime($datetocheck))
                                    ->setFromTime(strtotime($data['from_time']))
                                    ->setToTime(strtotime($data['to_time']))
                                    ->setTimeSlot($timeToSaveInDb);
                            $availability->save();
                        }
                    }
                } else {
                    $timeSlice = $this->splitTimeSlotAction($data['from_time'], $data['to_time'], $data['from_date']);
                    foreach ($timeSlice as $forImp) {
                        $count_loop = 0;
                        $slot_ind_string = '';
                        foreach ($forImp as $impArr) {
                            if ($count_loop == 0) {
                                $slot_ind_string.=$impArr . "~";
                            } else {
                                $slot_ind_string.=$impArr;
                            }
                            $count_loop++;
                        }
                        $arrToImp[] = $slot_ind_string;
                    }
                    //print_r($arrToImp);die;

                    $timeToSaveInDb = implode(",", $arrToImp);
                    $availability = Mage::getModel('availability/availability');
                    $availability->setTechName(isset($data['name']) ? $data['name'] : '')
                            ->setEmail($data['email'])
                            ->setFromDate(strtotime($data['from_date']))
                            ->setToDate(strtotime($data['to_date']))
                            ->setFromTime(strtotime($data['from_time']))
                            ->setToTime(strtotime($data['to_time']))
                            ->setTimeSlot($timeToSaveInDb);
                    $availability->save();
                }
            }

            $allDatas = $availability->getCollection()
                    ->addFieldToFilter("email", array("eq" => $emailTogetData))
                    ->addFieldToFilter(array("from_date", "to_date"), array(array("gteq" => $currentDate), array("eq" => $currentDate)));
            if (isset($allDatas) && $allDatas) {
                foreach ($allDatas as $passIt) {
                    if (isset($passIt, $passIt['from_date'], $currentDate, $passIt['to_date']) && $passIt['from_date'] == $currentDate && $passIt['to_date'] == $currentDate) {
                        $exploTimeSlot = explode(",", $passIt['time_slot']);
                        foreach ($exploTimeSlot as $key_slot) {
                            $ind_slot_array = explode("~", $key_slot);
                            $all_slot_array[] = $ind_slot_array[0];
                            $all_slot_array[] = $ind_slot_array[1];
                        }
                        if (isset($all_slot_array) && $all_slot_array) {
                            for ($compair = 0; $compair < count($all_slot_array) - 1; $compair++) {
                                if ($compair % 2 != 0) {
                                    if ($all_slot_array[$compair] >= $currentTime && $all_slot_array[$compair + 2] > $currentTime) {
                                        $customer = $this->customerByEmailIdAction($passIt['email']);
                                        $proImg = $customer->getSstechProfileimage() ? Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'customer_image/' . $customer->getSstechProfileimage() : NULL;
                                        $jsonAvailability['avail'][] = array('name' => $passIt['tech_name'], 'email' => $passIt['email'], 'profile_image' => $proImg, 'from_date' => date("m-d-y", $passIt['from_date']), 'to_date' => date("m-d-y", $passIt['to_date']), 'from_time' => date("H:i:s", $passIt['from_time']), 'to_time' => date("H:i:s", $passIt['to_time']));
                                    }
                                }
                            }
                        }
                    } elseif (isset($passIt, $passIt['from_date'], $currentDate, $passIt['to_date']) && $passIt['from_date'] >= $currentDate && $passIt['to_date'] > $currentDate) {
                        $customer = Mage::getModel("customer/customer");
                        $customer = $this->customerByEmailIdAction($passIt['email']);
                        $proImg = $customer->getSstechProfileimage() ? Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'customer_image/' . $customer->getSstechProfileimage() : NULL;
                        $jsonAvailability['avail'][] = array('name' => $passIt['tech_name'], 'email' => $passIt['email'], 'profile_image' => $proImg, 'from_date' => date("m-d-y", $passIt['from_date']), 'to_date' => date("m-d-y", $passIt['to_date']), 'from_time' => date("H:i:s", $passIt['from_time']), 'to_time' => date("H:i:s", $passIt['to_time']));
                    } else {
                        echo "no data found";
                    }
                }
            }
            if (isset($jsonAvailability)) {
                echo json_encode($jsonAvailability, JSON_UNESCAPED_SLASHES);
            }
        }
        $error['result'] = "No data Found";
        if (isset($error) && $error) {
            echo json_encode($error);
        }
    }

    /**
     * @see function return time slots of on behalf of from_time AND to_time..
     * @param type $from
     * @param type $to
     * @return type array
     */
    public function splitTimeSlotAction($from, $to, $dateArrOrSingle) {
        if (is_array($dateArrOrSingle)) {
            foreach ($dateArrOrSingle as $itsArrDate) {
                $dateConverted = date('m/d/y', $itsArrDate);
                $dateTimeCombo = "$dateConverted $from";
                $dateTimeToCombo = "$dateConverted $to";
                $fromTimeString1[] = strtotime($dateTimeCombo);
                $toTimeString1[] = strtotime($dateTimeToCombo);
                $itsArrDate = date("m/d/y", $itsArrDate);
                $slotArr[] = array(1 => strtotime("$itsArrDate 00:00"), 2 => strtotime("$itsArrDate 04:00"),
                    3 => strtotime("$itsArrDate 08:00"), 4 => strtotime("$itsArrDate 12:00"),
                    5 => strtotime("$itsArrDate 16:00"), 6 => strtotime("$itsArrDate 20:00"),
                    7 => strtotime("$itsArrDate 23:59"));
            }

            for ($split = 0; $split < count($dateArrOrSingle); $split++) {
                for ($split2 = 1; $split2 <= 7; $split2++) {
                    for ($split3 = 7; $split3 > $split2; $split3--) {
                        foreach ($fromTimeString1 as $fts1) {
                            foreach ($toTimeString1 as $tts1) {
                                if ($fts1 >= $slotArr[$split][$split2] && $fts1 < $slotArr[$split][$split2 + 1] && $tts1 <= $slotArr[$split][$split3] && $tts1 > $slotArr[$split][$split3 - 1]) {
                                    $timeDiff = $slotArr[$split][$split3] - $slotArr[$split][$split2];
                                    $subDiff = ($timeDiff / 4);
                                    $forConditionCheck = (($subDiff / 60) / 60);
                                    if (is_float($forConditionCheck)) {
                                        $checkDes = explode(".", $forConditionCheck);
                                        $forConditionCheck = $checkDes[0] + 1;
                                    }
                                    if ($forConditionCheck == 1) {
                                        $arr[] = array($split => $slotArr[$split][$split2], $split + 1 => $slotArr[$split][$split3]);
                                    } else {
                                        if ($fts1 >= $slotArr[$split][$split2] && $fts1 < $slotArr[$split][$split2 + 1]) {
                                            for ($multiSlot = 1; $multiSlot <= $forConditionCheck; $multiSlot++) {
                                                $arr[] = array($multiSlot => $slotArr[$split][$split2 + $multiSlot - 1], $multiSlot + 1 => $slotArr[$split][$split2 + $multiSlot]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return $arr;
        } else {
            $fromDateCombo = "$dateArrOrSingle $from";
            $toDateCombo = "$dateArrOrSingle $to";
            $fTimeStr = strtotime($fromDateCombo);
            $tTimeStr = strtotime($toDateCombo);
            $slotArr = array(1 => strtotime("$dateArrOrSingle 00:00"), 2 => strtotime("$dateArrOrSingle 04:00"),
                3 => strtotime("$dateArrOrSingle 08:00"), 4 => strtotime("$dateArrOrSingle 12:00"),
                5 => strtotime("$dateArrOrSingle 16:00"), 6 => strtotime("$dateArrOrSingle 20:00"),
                7 => strtotime("$dateArrOrSingle 23:59"));
            for ($split = 1; $split <= count($slotArr); $split++) {
                for ($split2 = count($slotArr); $split2 >= $split; $split2--) {
                    if ($fTimeStr >= $slotArr[$split] && $fTimeStr < $slotArr[$split + 1] && $tTimeStr <= $slotArr[$split2] && $tTimeStr > $slotArr[$split2 - 1]) {
                        $timeDiff = $slotArr[$split2] - $slotArr[$split];
                        $subDiff = ($timeDiff / 4);
                        $forConditionCheck = (($subDiff / 60) / 60);
                        if (is_float($forConditionCheck)) {
                            $checkDes = explode(".", $forConditionCheck);
                            $forConditionCheck = $checkDes[0] + 1;
                        }
                        if ($forConditionCheck == 1) {
                            $arr = array(0 => array(0 => $slotArr[$split], 1 => $slotArr[$split2]));
                            return $arr;
                        } else {
                            if ($fTimeStr >= $slotArr[$split] && $fTimeStr < $slotArr[$split + 1]) {
                                for ($multiSlot = 1; $multiSlot <= $forConditionCheck; $multiSlot++) {
                                    $arr[] = array($multiSlot => $slotArr[$split + $multiSlot - 1], $multiSlot + 1 => $slotArr[$split + $multiSlot]);
                                    date("H:i:s", $slotArr[$split + $multiSlot - 1]) . " To " . date("H:i:s", $slotArr[$split + $multiSlot]);
                                }
                            }
                            return $arr;
                        }
                    }
                }
            }
        }
    }

    /**
     * return available time slot on behalf of date....
     */
    public function getCurrentTimeSlotAction() {
        if ($this->getRequest()->getParam("date")) {
            $orgDate = $this->getRequest()->getParam("date");
            $date = strtotime($orgDate);
            $allDataOnDate = $availOnDate = Mage::getModel('availability/availability')
                            ->getCollection()->addFieldToFilter("from_date", $date);
            $slot = array(1 => strtotime("$orgDate 00:00:00"), 2 => strtotime("$orgDate 04:00:00"), 3 => strtotime("$orgDate 08:00:00"), 4 => strtotime("$orgDate 12:00:00"), 5 => strtotime("$orgDate 16:00:00"), 6 => strtotime("$orgDate 20:00:00"), 7 => strtotime("$orgDate 23:59"));
            if (isset($allDataOnDate) && $allDataOnDate->getData()) {
                foreach ($allDataOnDate as $slotingDate) {

                    $slot_data = explode(",", $slotingDate['time_slot']);
                    foreach ($slot_data as $key_slot) {
                        $ind_slot_array = explode("~", $key_slot);
                        $final_array[] = $ind_slot_array[0];
                        $final_array[] = $ind_slot_array[1];
                    }
                    $var[] = $final_array;
                }
            } else {
                $noData['data'] = "no record found";
                if (isset($noData) && $noData) {
                    echo json_encode($noData);
                }
            }
            if (isset($var) && $var) {
                foreach ($var as $topi) {
                    foreach ($topi as $loti) {
                        for ($iterateMe = 1; $iterateMe <= 7; $iterateMe++) {
                            if ($slot[$iterateMe] == $loti) {
                                if ($iterateMe == 7) {
                                    $arrForSlot['slots'][] = "24:00:00";
                                } elseif ($iterateMe == 1) {
                                    $arrForSlot['slots'][] = "00:00:00";
                                } else {
                                    $arrForSlot['slots'][] = date("H:i:s", $slot[$iterateMe]);
                                }
                            }
                        }
                    }
                }
            }
            if (isset($arrForSlot) && $arrForSlot['slots']) {
                $chunkOfSlots = array_chunk($arrForSlot['slots'], 2);
                for ($chunks = 0; $chunks < count($chunkOfSlots); $chunks++) {
                    $finalArr[] = $chunkOfSlots[$chunks][0] . ' To ' . $chunkOfSlots[$chunks][1];
                }

                if (isset($finalArr) && $finalArr) {
                    $sorting = array_unique($finalArr);
                    asort($sorting);
                    $sorting = array_values($sorting);
                    $jsonUniqueSlot['t_slot'] = $sorting;
                    echo json_encode($jsonUniqueSlot);
                }
            }
        } else {
            $error['msg'] = "Date not found";
            if (isset($error) && $error) {
                echo json_encode($error);
            }
        }
    }

    /**
     * 
     */
    public function getAllTechnitionAction() {
        $date = $this->getRequest()->getParam("date");
        $date = strtotime($date);
        $allDataOnDate = $availOnDate = Mage::getModel('availability/availability')
                        ->getCollection()->addFieldToFilter("from_date", $date);
        if (isset($allDataOnDate) && $allDataOnDate) {
            foreach ($allDataOnDate as $techs) {
                $emailArr[] = $techs['email'];
            }
            if (isset($emailArr) && $emailArr) {
                foreach ($emailArr as $emails) {
                    $jsonTechEmail['email'][] = array("name" => $emails);
                }
            } else {
                $jsonTechEmail['email'] = "data not found";
            }
            echo json_encode($jsonTechEmail);
        }
    }

    public function submitRatingAction() {
        $tech_mail = $this->getRequest()->getParam("tech_mail");
        $rating = $this->getRequest()->getParam("rating");
        $review_message = $this->getRequest()->getParam("review_message");
        $customer_id = $this->getRequest()->getParam("customer_id");
        $customer = $this->customerByEmailIdAction($tech_mail);
        $customerId = $customer->entity_id;
        $existing_rating_avg = $customer->average_rating;
        $vote_count = $customer->no_of_votes;


        try {
            $new_rating = (($existing_rating_avg * $vote_count) + $rating) / ($vote_count + 1);
            $customer1 = Mage::getModel('customer/customer')->load($customerId);
            $customer1->setNoOfVotes(($vote_count + 1))->setAverageRating($new_rating);
            $customer1->save();
            $rateMe = Mage::getModel('techrating/techrating');
            if (isset($rateMe) && $rateMe) {
                $rateMe->setRatingFor($customerId);
                $rateMe->setRatingBy($customer_id);
                $rateMe->setRating($new_rating);
                $rateMe->setRatingComment($review_message);
                $rateMe->save();
            }
            echo "Success";
        } catch (Exception $e) {
            echo $e->getTraceAsString();
        }
    }

    /**
     * @return type return cusatomer details.
     */
    public function fetchAllByEmailAction() {
        if ($this->getRequest()->getParam("email")) {
            $email = $this->getRequest()->getParam("email");
            $customer = $this->customerByEmailIdAction($email);
            if ($customer->getId()) {
                $customerId = $customer->getId();
                $customer1 = Mage::getModel('customer/customer')->load($customerId); //insert cust ID
//        print_r($customer1);
//        $allOption = Mage::getResourceModel('customer/customer')
//                ->getAttribute('experience')
//                ->getSource()
//                ->getAllOptions();
//        foreach ($allOption as $labels) {
//            if ($customer1['experience']==$labels['value']) {
//                $option= $labels['label'];
//            }
//        }
                $customerAddress = array();
                foreach ($customer1->getAddresses() as $address) {
                    $customerAddress = $address->toArray();
                }
                if (isset($customer1['group_id']) && $customer1['group_id'] == 1) {
                    $jsonData['data'][] = array("firstname" => $customer['firstname'], "lastname" => $customer['lastname'], "city" => isset($customerAddress['city']) ? $customerAddress['city'] : '', "region" => isset($customerAddress['region']) ? $customerAddress['region'] : '', "region_id" => isset($customerAddress['region_id']) ? $customerAddress['region_id'] : '', "postcode" => isset($customerAddress['postcode']) ? $customerAddress['postcode'] : '', "telephone" => isset($customerAddress['telephone']) ? $customerAddress['telephone'] : '', "street" => isset($customerAddress['street']) ? $customerAddress['street'] : '', "image" => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'customer_image/' . $customer1['sstech_profileimage'],);
                }if (isset($customer1['group_id']) && $customer1['group_id'] == 6) {
                    if ($customer1['lastname'] == " ") {
                        $names = $customer1['firstname'];
                    } else {
                        $names = $customer1['firstname'] . " " . $customer1['lastname'];
                    }
//            $jsonData['data'][] = array("firstname" => $customer['firstname'], "lastname" => $customer['lastname'], "telephone" => isset($customerAddress['telephone']) ? $customerAddress['telephone'] : '', "about_you" => isset($customer1['about_you']) ? $customer1['about_you'] : '', "experience" => isset($option) ? $option : '');
                    $jsonData['data'][] = array("id" => $customer1['entity_id'], "email" => $customer1['email'], "name" => $names,
                        "telephone" => isset($customerAddress['telephone']) ? $customerAddress['telephone'] : '',
                        "about_you" => isset($customer1['about_you']) ? $customer1['about_you'] : '',
                        "experience" => isset($customer1['experience']) ? $customer1['experience'] : '', "latitude" => $customer1['latitude'], "longitude" => $customer1['longitude'], "image" => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'customer_image/' . $customer1['sstech_profileimage'], "city" => isset($customerAddress['city']) ? $customerAddress['city'] : '', "region" => isset($customerAddress['region']) ? $customerAddress['region'] : '', "region_id" => isset($customerAddress['region_id']) ? $customerAddress['region_id'] : '', "postcode" => isset($customerAddress['postcode']) ? $customerAddress['postcode'] : '', "telephone" => isset($customerAddress['telephone']) ? $customerAddress['telephone'] : '', "street" => isset($customerAddress['street']) ? $customerAddress['street'] : '', 'qualification' => isset($customer1['qualification']) ? $customer1['qualification'] : '', 'skill' => isset($customer1['skill']) ? $customer1['skill'] : '');
//            $jsonData['data'][] = $customer1;
                }
                echo json_encode($jsonData, JSON_UNESCAPED_SLASHES);
            } else {
                $validateEmail['error'] = "Please provide VALID Email ID";
                if (isset($validateEmail) && $validateEmail) {
                    echo json_encode($validateEmail);
                }
            }
        } else {
            $noData['msg'] = "please provide email ID";
            if (isset($noData) && $noData) {
                echo json_encode($noData);
            }
        }
    }

    public function updateAction() {
        $data = $this->getRequest()->getPost();
        if (isset($_FILES['sstech_profileimage']['name'])) {
            if ($_FILES['sstech_profileimage']['name']) {
                $mediaPath = Mage::getBaseDir('media') . DS;
                $folderName = 'customer_image';
                $sourcePath = $mediaPath . $folderName . DS . $data['group'];
                if (!file_exists($sourcePath)) {
                    mkdir($sourcePath, 0777, TRUE);
                    chmod($sourcePath, 0777);
                }
                $ext = explode(".", $_FILES['sstech_profileimage']['name']);
                $newFileName = 'File-' . time() . "." . $ext[1];
                $finalPath = $sourcePath . DS . $newFileName;
                $pathToDb = strstr($finalPath, $data['group']);
                move_uploaded_file($_FILES['sstech_profileimage']['tmp_name'], $finalPath);
                $image = $pathToDb;
            }
        }
        try {
            if (isset($data) && $data['email']) {
                if (isset($data['group'], $data['name']) && $data['group'] == 6 && $data['name']) {
                    $fullName = explode(" ", $data['name']);
                    if (count($fullName) > 1) {
                        for ($i = 0; $i < count($fullName) - 1; $i++) {
                            $soManyName[] = $fullName[$i];
                        }
                        $name1 = implode(" ", $soManyName);
                        $name2 = " " . $fullName[count($fullName) - 1];
                    } else {
                        $name1 = $fullName[0];
                        $name2 = ' ';
                    }
                } else {
                    $customer = $this->customerByEmailIdAction($data['email']);
                    $name1 = isset($data['firstname']) ? $data['firstname'] : $customer['firstname'];
                    $name2 = isset($data['lastname']) ? $data['lastname'] : $customer['lastname'];
                }
                $customer = $this->customerByEmailIdAction($data['email']);
                $customerId = $customer->getId();
                $customer1 = Mage::getModel('customer/customer')->load($customerId);
//                echo $customer1['lastname'];exit;
                $customer1->setFirstname(isset($name1) ? $name1 : $customer1['firstname']);
                $customer1->setLastname(isset($name2) ? $name2 : $customer1['lastname']);
                $customer1->setAboutYou(isset($data['about_you']) ? $data['about_you'] : $customer1['about_you']);
                $customer1->setExperience(isset($data['experience']) ? $data['experience'] : $customer1['experience']);
                $customer1->setMessageToken(isset($data['message_token']) ? $data['message_token'] : $customer1['message_token']);
                $customer1->setDeviceId(isset($data['device_id']) ? $data['device_id'] : $customer1['device_id']);
                $customer1->setLatitude(isset($data['latitude']) ? $data['latitude'] : $customer['latitude']);
                $customer1->setLongitude(isset($data['longitude']) ? $data['longitude'] : $customer1['longitude']);
                $customer1->setSkill(isset($data['skill']) ? $data['skill'] : $customer1['skill']);
                $customer1->setQualification(isset($data['qualification']) ? $data['qualification'] : $customer1['qualification']);
                if (isset($image) && $image) {
                    $customer1->setSstechProfileimage($image);
                }
                $customer1->save();
                $result['success'] = "Update Successfully";
                $customerAddress = array();
                foreach ($customer1->getAddresses() as $address) {
//                    echo $address['region_id'];exit;
//        $address=Mage::getModel('customer/address')->load($customerId);
                    $address->setFirstname(isset($name1) ? $name1 : $address['firstname']);
                    $address->setLastname(isset($name2) ? $name2 : $address['lastname']);
                    $address->setCountryId('US');
                    $address->setCity(isset($data['city']) ? $data['city'] : $address['city']);
                    $address->setRegionId(isset($data['region']) ? $data['region'] : $address['region_id']);
                    $address->setPostcode(isset($data['zip']) ? $data['zip'] : $address['postcode']);
                    $address->setTelephone(isset($data['mob_no']) ? $data['mob_no'] : $address['telephone']);
                    $address->setStreet(isset($data['street']) ? $data['street'] : $address['street']);
                    $address->setIsDefaultBilling('1');
                    $address->setIsDefaultShipping('1');
                    $address->setSaveInAddressBook('1');
                }
                $address->save();
                $result['success'] = "Update Successfully";
                echo json_encode($result);
            }
        } catch (Exception $ex) {
            echo json_encode($ex->getMessage());
        }
    }

    public function getProductPriceByIDAction() {
        if ($this->getRequest()->getParam("product_id")) {
            $product_id = $this->getRequest()->getParam("product_id");
            $_product = Mage::getModel('catalog/product')->load($product_id);
            echo $_product->getPrice();
            //echo json_encode($_product);
        } else {
            $dataId['msg'] = "No data found";
            if (isset($dataId) && $dataId) {
                echo json_encode($dataId);
            }
        }
    }

    public function updateTechLocationAction() {
        if ($this->getRequest()->getParams()) {
            $tech_id = $this->getRequest()->getParam("tech_id");
            $filter = Mage::getModel('customer/customer')->getCollection()->addAttributeToFilter("entity_id", array("eq" => $tech_id));
            if (isset($filter) && $filter->getData()) {
                $latitude = $this->getRequest()->getParam("latitude");
                $longitude = $this->getRequest()->getParam("longitude");
                $customer1 = Mage::getModel('customer/customer')->load($tech_id);
                $customer1->setLatitude(isset($latitude) ? $latitude : "")
                        ->setLongitude(isset($longitude) ? $longitude : "")
                        ->setUpdatedAt(date("Y-m-d H:i:s"));
                try {
                    $customer1->save();
                    echo "Success";
                } catch (Exception $e) {
                    echo $e->getTraceAsString();
                }
            } else {
                $validId['id'] = "Please Provide Valid ID";
                if (isset($validId) && $validId) {
                    echo json_encode($validId);
                }
            }
        } else {
            $found['record'] = "Provide data";
            if (isset($found) && $found) {
                echo json_encode($found);
            }
        }
    }

    public function appointmentBookingAction() {
        if ($this->getRequest()->getPost()) {
            $data = $this->getRequest()->getPost();
            $a_date = $data['appoint_date'];
            $a_time = $data['appoint_time'];
//            $dateTime = strtotime("$a_time");
            $stringDate = strtotime($a_date);
//            $time=  strtotime($a_time);
            if (isset($data['cust_email']) && $data['cust_email']) {
                $location = $data['appointment_location'];
//                $probType=$data['problem_type'];
                $probDiscription = $data['problem_discription'];
                $appointmentId = $this->generateUniqueIdAction($data['cust_email']);
                $customer = $this->customerByEmailIdAction($data['cust_email']);
                if (isset($data['tech_email']) && $data['tech_email']) {
                    $technicion = $this->customerByEmailIdAction($data['tech_email']);
                }
                $customerId = $customer['entity_id'];
                $technicionId = isset($technicion['entity_id']) ? $technicion['entity_id'] : '';
                $currentDateTime = Mage::getModel('core/date')->date('Y-m-d H:i:s');
                $bookedModel = Mage::getModel('booked/booked');
                $appointProb = Mage::getModel('problem/appointment');
                if (isset($bookedModel) && $bookedModel) {
                    $bookedModel->setAppointId($appointmentId);
                    $bookedModel->setCustId($customerId);
                    $bookedModel->setServiceId($data['service_id']);
                    $bookedModel->setBrand($data['brand']);
                    $bookedModel->setModelNo($data['model_no']);
                    $bookedModel->setSerialNo($data['serial_no']);
                    $bookedModel->setAppointDate($stringDate);
//                    $bookedModel->setAppointTime(date("H:i:s", $dateTime));
                    $bookedModel->setAppointTime($a_time);
                    $bookedModel->setAppointLocation(isset($location) ? $location : '');
                    $bookedModel->setTechId(isset($technicionId) ? $technicionId : '');
                    $bookedModel->setCreatedAt($currentDateTime);
                    $bookedModel->save();
                    $appointProb->setProblemDescription(isset($probDiscription) ? $probDiscription : '');
                    $appointProb->setAppointmentId($bookedModel->getId());
                    $appointProb->save();

                    $jsonUniqueId['key'][] = array("appointment_id" => $bookedModel->getId(), "appointment_key" => $appointmentId);
                    echo json_encode($jsonUniqueId);
                }
            }
        }
    }

    public function SearchTechnicianAction() {
        if ($this->getRequest()->getParams()) {
            $keyword = $this->getRequest()->getParam("keyword");
            $type = $this->getRequest()->getParam("type");
            $timeslot_start_time = $this->getRequest()->getParam("timeslot_start_time");
            $timeslot_end_time = $this->getRequest()->getParam("timeslot_end_time");


            if ($timeslot_start_time != '' && $timeslot_end_time != '') {
                $string_to_match = strtotime($timeslot_start_time) . "~" . strtotime($timeslot_end_time);
            } else {
                $string_to_match = '';
            }

            $latitude = $this->getRequest()->getParam("latitude");
            $longitude = $this->getRequest()->getParam("longitude");
            if (trim($string_to_match) != '') {
//            echo $string_to_match;exit;
                $allDataOnDate = $availOnDate = Mage::getModel('availability/availability')
                                ->getCollection()->addFieldToFilter('time_slot', array("like" => '%' . $string_to_match . '%'));
                if (isset($allDataOnDate) && $allDataOnDate) {
                    foreach ($allDataOnDate as $techs) {
                        $emailArr[] = $techs['email'];
                    }
                }
            }
            $final_response = array();
            if ($type == 'R') {
                if (trim($string_to_match) != '') {
                    $customers = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect(array('latitude', 'longitude', 'lastname', 'firstname', 'average_rating'))
                                    ->addAttributeToFilter(array(array('attribute' => 'firstname', 'like' => '%' . $keyword . '%'), array('attribute' => 'lastname', 'like' => '%' . $keyword . '%')))
                                    ->addAttributeToFilter('email', array("in" => $emailArr))->setOrder('average_rating', 'desc')->load()->toArray();
                } else {
                    $customers = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect(array('latitude', 'longitude', 'lastname', 'firstname', 'average_rating'))
                                    ->setOrder('average_rating', 'desc')->load()->toArray();
                }

                foreach ($customers as $customer) {
                    $customerObj = Mage::getModel('customer/customer')->load($customer['entity_id']);
                    $response['id'] = $customer['entity_id'];
                    $response['email'] = $customer['email'];
                    $response['fname'] = $customer['firstname'];
                    $response['lname'] = $customer['lastname'];
                    $response['rating'] = round($customerObj->getData('average_rating') * 2) / 2;
                    $response['image'] = $customerObj->getData('sstech_profileimage') != '' ? Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'customer_image/' . $customerObj->getData('sstech_profileimage') : '';
                    $tech_latitude = $customerObj->getData('latitude');
                    $tech_longitude = $customerObj->getData('longitude');

                    if ($tech_latitude != '' && $tech_longitude != '') {
                        $response['time'] = (round($this->getDistanceAction($latitude, $longitude, $tech_latitude, $tech_longitude), 2)) / 60;
                        //$response['distance'] = $this->getDistanceAction(20.671165, 81.5625, 20.285213, 80.639648);
                    } else {
                        $response['time'] = 0;
                    }
                    $final_response['technician'][] = $response;
                }
            } else if ($type == 'T') {
                if (trim($string_to_match) != '') {
                    $customers = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect(array('latitude', 'longitude', 'lastname', 'firstname', 'average_rating'))
                            ->addAttributeToFilter(array(array('attribute' => 'firstname', 'like' => '%' . $keyword . '%'), array('attribute' => 'lastname', 'like' => '%' . $keyword . '%')))
                            ->addAttributeToFilter('email', array("in" => $emailArr));
                } else {
                    $customers = Mage::getModel('customer/customer')->getCollection()
                                    ->addAttributeToFilter(array(array('attribute' => 'firstname', 'like' => '%' . $keyword . '%'), array('attribute' => 'lastname', 'like' => '%' . $keyword . '%')))
                                    ->addAttributeToSelect(array('latitude', 'longitude', 'lastname', 'firstname', 'average_rating'))->load()->toArray();
                }


                foreach ($customers as $customer) {
                    $customerObj = Mage::getModel('customer/customer')->load($customer['entity_id']);
                    $response['id'] = $customer['entity_id'];
                    $response['email'] = $customer['email'];
                    $response['fname'] = $customer['firstname'];
                    $response['lname'] = $customer['lastname'];
                    $response['rating'] = round($customerObj->getData('average_rating') * 2) / 2;
                    $response['image'] = $customerObj->getData('sstech_profileimage') != '' ? Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'customer_image/' . $customerObj->getData('sstech_profileimage') : '';
                    $tech_latitude = trim($customerObj->getData('latitude'));
                    $tech_longitude = trim($customerObj->getData('longitude'));

                    if ($tech_latitude != '' && $tech_longitude != '') {
                        $response['time'] = (round($this->getDistanceAction($latitude, $longitude, $tech_latitude, $tech_longitude), 2)) / 60;
                        //$response['distance'] = $this->getDistanceAction(20.671165, 81.5625, 20.285213, 80.639648);
                    } else {
                        $response['time'] = 0;
                    }
                    $final_response['technician'][] = $response;
                }
                usort($final_response, array($this, "cmpAction"));
            }
            if (count($final_response) == 0) {
                $response['response'] = "No Data Found";
                echo json_encode($response);
            } else {

                foreach ($final_response as $key1 => $fres1) {
                    foreach ($fres1 as $key2 => $fres2) {
                        if ($fres2['time'] == 0) {
                            $final_response[$key1][$key2]['time'] = "No Time Available";
                        } else {
                            $total_mins = $fres2['time'] * 60;
                            $final_response[$key1][$key2]['time'] = floor(($total_mins / 60)) . " Hours " . ($total_mins % 60) . " Minutes";
                        }
                    }
                }
                echo json_encode($final_response, JSON_UNESCAPED_SLASHES);
            }
        }
    }

    function cmpAction($a, $b) {
        return $a["mid"] - $b["mid"];
    }

    public function getDistanceAction($lat1, $lon1, $lat2, $lon2) {

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return ($miles * 1.609344);
    }

    public function getEmailAction($cust_id) {
        if ($cust_id) {
            $filter = Mage::getModel('customer/customer')->getCollection()->addAttributeToFilter("entity_id", array("eq" => $cust_id));
            if (isset($filter) && $filter->getData()) {
                $data = Mage::getModel('customer/customer')->load($cust_id);
                return $data['email'];
            } else {
                $validId['id'] = "Please Provide Valid ID";
                if (isset($validId) && $validId) {
                    echo json_encode($validId);
                }
            }
        }
    }

    public function getNameAction($cust_id) {
        if ($cust_id) {
            $filter = Mage::getModel('customer/customer')->getCollection()->addAttributeToFilter("entity_id", array("eq" => $cust_id));
            if (isset($filter) && $filter->getData()) {
                $data = Mage::getModel('customer/customer')->load($cust_id);
                return $data['firstname'] . " " . $data['lastname'];
            } else {
                $validId['id'] = "Please Provide Valid ID";
                if (isset($validId) && $validId) {
                    echo json_encode($validId);
                }
            }
        }
    }

    public function loadProductByIdAction($pro_id) {
        $data = Mage::getModel('catalog/product')->load($pro_id);
        $data1 = array("id" => $data['entity_id'], "name" => $data['name'], "price" => $data['price']);
        return $data1;
    }

    /**
     * @return json booking id and all details
     */
    public function viewAppointmentAction() {
        $id = $this->getRequest()->getParam('id');
        $date = $this->getRequest()->getParam('date');
        if (isset($id, $date) && $id && $date) {
            $groupId = Mage::getModel('customer/customer')->load($id);
            if ($groupId['group_id'] == 1) {
                $model = Mage::getModel('booked/booked')->getCollection()->addFieldToFilter("cust_id", array("eq" => $id))->addFieldToFilter("appoint_date", array("eq" => date("Y-m-d", strtotime($date))));
            } else {
//              ->addFieldToFilter("tech_id",array("eq" => $id))->addFieldToFilter("appoint_date",array("eq" => date("Y-m-d", strtotime($date))))
                $model = Mage::getModel('booked/booked')->getCollection()->addFieldToFilter("tech_id", array("eq" => $id))->addFieldToFilter("appoint_date", array("eq" => date("Y-m-d", strtotime($date))));
//          Zend_Debug::dump($model->getData());
            }
            foreach ($model as $bookingDetails) {
                $cusEmail = $this->getEmailAction($bookingDetails['cust_id']);
                if (isset($bookingDetails['tech_id']) && $bookingDetails['tech_id']) {
                    $techEmail = $this->getEmailAction($bookingDetails['tech_id']);
                }
                $custName = $this->getNameAction($bookingDetails['cust_id']);
                $techName = $this->getNameAction($bookingDetails['tech_id']);
                $serviceDetails = $this->loadProductByIdAction($bookingDetails['service_id']);
                $appointProb = Mage::getModel('problem/appointment')->getCollection()->addFieldToSelect("problem_description")->addFieldToFilter("appointment_id", array("eq" => $bookingDetails['id']));
                foreach ($appointProb as $rrrr) {
                    $probDiscription = $rrrr['problem_description'];
                    if ($groupId['group_id'] == 1) {
                        $jsonViewByCustomer['details'][] = array("id" => $bookingDetails['id'], "appoint_id" => $bookingDetails['appoint_id'], "customer_email" => $cusEmail, "tech_email" => $techEmail, "customer_name" => $custName, "tech_name" => $techName, "service_name" => $serviceDetails['name'], "service_charge" => $serviceDetails['price'], "brand" => $bookingDetails['brand'], "model_no" => $bookingDetails['model_no'], "serial_no" => $bookingDetails['serial_no'], "appointment_date" => $bookingDetails['appoint_date'], "appointment_time" => $bookingDetails['appoint_time'], "appointment_location" => $bookingDetails['appoint_location'], "problem_description" => $probDiscription);
                    } else {
                        $jsonViewByCustomer['details'][] = array("id" => $bookingDetails['id'], "appoint_id" => $bookingDetails['appoint_id'], "customer_email" => $cusEmail, "tech_email" => $techEmail, "customer_name" => $custName, "tech_name" => $techName, "service_name" => $serviceDetails['name'], "service_charge" => $serviceDetails['price'], "brand" => $bookingDetails['brand'], "model_no" => $bookingDetails['model_no'], "serial_no" => $bookingDetails['serial_no'], "appointment_date" => $bookingDetails['appoint_date'], "appointment_time" => $bookingDetails['appoint_time'], "appointment_location" => $bookingDetails['appoint_location'], "problem_description" => $probDiscription, "status" => "pending");
                    }
                }
            }
            if (isset($jsonViewByCustomer)) {
                echo json_encode($jsonViewByCustomer);
            }
        }
        if (empty($date) && isset($id) && $id) {
            $groupId1 = Mage::getModel('customer/customer')->load($id);
            if ($groupId1['group_id'] == 1) {
                $model = Mage::getModel('booked/booked')->getCollection()->addFieldToFilter("cust_id", array("eq" => $id));
            } else {
                $model = Mage::getModel('booked/booked')->getCollection()->addFieldToFilter("tech_id", array("eq" => $id));
            }
            foreach ($model as $bookingDetails) {
                $cusEmail = $this->getEmailAction($bookingDetails['cust_id']);
                $techEmail = $this->getEmailAction($bookingDetails['tech_id']);
                $custName = $this->getNameAction($bookingDetails['cust_id']);
                $techName = $this->getNameAction($bookingDetails['tech_id']);
                $serviceDetails = $this->loadProductByIdAction($bookingDetails['service_id']);
                $appointProb = Mage::getModel('problem/appointment')->getCollection()->addFieldToSelect("problem_description")->addFieldToFilter("appointment_id", array("eq" => $bookingDetails['id']));
                foreach ($appointProb as $rrrr) {
                    $probDiscription = $rrrr['problem_description'];
//                $jsonViewByCustomer['details'][] = array("id" => $bookingDetails['id'], "appoint_id" => $bookingDetails['appoint_id'], "customer_email" => $cusEmail, "tech_email" => $techEmail, "customer_name" => $custName, "tech_name" => $techName, "service_name" => $serviceDetails['name'], "service_charge" => $serviceDetails['price'], "brand" => $bookingDetails['brand'], "model_no" => $bookingDetails['model_no'], "serial_no" => $bookingDetails['serial_no'], "appointment_date" => $bookingDetails['appoint_date'], "appointment_time" => $bookingDetails['appoint_time']);
                    if ($groupId1['group_id'] == 1) {
                        $jsonViewByCustomer['details'][] = array("id" => $bookingDetails['id'], "appoint_id" => $bookingDetails['appoint_id'], "customer_email" => $cusEmail, "tech_email" => $techEmail, "customer_name" => $custName, "tech_name" => $techName, "service_name" => $serviceDetails['name'], "service_charge" => $serviceDetails['price'], "brand" => $bookingDetails['brand'], "model_no" => $bookingDetails['model_no'], "serial_no" => $bookingDetails['serial_no'], "appointment_date" => $bookingDetails['appoint_date'], "appointment_time" => $bookingDetails['appoint_time'], "appointment_location" => $bookingDetails['appoint_location'], "problem_description" => $probDiscription);
                    } else {
                        $jsonViewByCustomer['details'][] = array("id" => $bookingDetails['id'], "appoint_id" => $bookingDetails['appoint_id'], "customer_email" => $cusEmail, "tech_email" => $techEmail, "customer_name" => $custName, "tech_name" => $techName, "service_name" => $serviceDetails['name'], "service_charge" => $serviceDetails['price'], "brand" => $bookingDetails['brand'], "model_no" => $bookingDetails['model_no'], "serial_no" => $bookingDetails['serial_no'], "appointment_date" => $bookingDetails['appoint_date'], "appointment_time" => $bookingDetails['appoint_time'], "appointment_location" => $bookingDetails['appoint_location'], "problem_description" => $probDiscription, "status" => "pending");
                    }
                }
            }
            if (isset($jsonViewByCustomer)) {
                echo json_encode($jsonViewByCustomer);
            }
        }
    }

    /**
     * @discription update booking date OR time
     * @return json seccess or failed on rescheduleing bookin date and time
     */
    public function rescheduleBookingAction() {
        if ($this->getRequest()->getPost()) {
            $data = $this->getRequest()->getPost();
            if (isset($data) && $data['id']) {
                $a_date = $data['appoint_date'];
                $a_time = $data['appoint_time'];
//                $dateTime = strtotime("$a_time");
                $stringDate = strtotime($a_date);
                $updateAt = Mage::getModel('core/date')->date('Y-m-d H:i:s');
                $filter = Mage::getModel('booked/booked')->getCollection()->addFieldToFilter("id", array("eq" => $data['id']));
                if (isset($filter) && $filter->getData()) {
                    $bookingDetails = Mage::getModel('booked/booked')->load($data['id']);
                    if (isset($bookingDetails) && $bookingDetails['id']) {
                        $bookingDetails->setAppointDate($stringDate);
                        $bookingDetails->setAppointTime($a_time);
                        $bookingDetails->save();
                        $jsonUniqueId['key'] = array("updated" => "success");
                        echo json_encode($jsonUniqueId);
                    } else {
                        $jsonUniqueId['key'] = array("updated" => "Failed");
                        echo json_encode($jsonUniqueId);
                    }
                } else {
                    $idBook['msg'] = "ID does Not Exist";
                    if (isset($idBook) && $idBook) {
                        echo json_encode($idBook);
                    }
                }
            }
        }
    }

    public function passwordRecoveryAction() {
        $customer_email = $this->getRequest()->getParam("customer_email");
        $customer = $this->customerByEmailIdAction($customer_email);
        if ($customer->getId()) {
            try {
                $newResetPasswordLinkToken = Mage::helper('customer')->generateResetPasswordLinkToken();
                $customer->changeResetPasswordLinkToken($newResetPasswordLinkToken);
                $customer->sendPasswordResetConfirmationEmail();
                $response_array['response'] = "Password Reset Link Sent on your mail address";
            } catch (Exception $exception) {
                Mage::log($exception);
                $response_array['response'] = "Failed";
            }
        } else {
            $response_array['response'] = "Any user with this email doesn't exist.";
        }
        echo json_encode($response_array);
    }

    public function updateAppointmentAction() {
        if ($this->getRequest()->getParam("appoint_id") && $this->getRequest()->getParam("tech_id")) {
            $currentDateTime = Mage::getModel('core/date')->date('Y-m-d H:i:s');
            $apppointmentId = $this->getRequest()->getParam("appoint_id");
            $technicianId = $this->getRequest()->getParam("tech_id");
            $filter = Mage::getModel('booked/booked')->getCollection()->addFieldToFilter("id", array("eq" => $apppointmentId));
            if (isset($filter) && $filter->getData()) {
                $bookedModel = Mage::getModel('booked/booked')->load($apppointmentId);

                //send push notication to device
                $customer_id = $bookedModel->getCustId();
                $techId = $bookedModel->getTechId();
                $Custdata = Mage::getModel('customer/customer')->load($customer_id);
                $customer_name = $Custdata['firstname'] . ' ' . $Custdata['lastname'];
                $appoint_location = $bookedModel->getAppointLocation();
                $techdata = Mage::getModel('customer/customer')->load($techId);
                $technician_name = $techdata->getFirstName() . ' ' . $techdata->getLastName();
                $messageToken = $techdata->getMessageToken();
                $messageString = 'Booking has been Scheduled by ' . $customer_name . ' at ' . $appoint_location;
                if (isset($messageToken) && $messageToken != '') {
                    if (strlen($messageToken) > 64) {
                    $this->andriodNotificationAction($messageToken, $messageString);
                } else {
                    $this->iosNotificationAction($messageToken, $messageString);
                }
                }
                //send push notication to device
                $jobModel = Mage::getModel('booked/job');
                $historyModel = Mage::getModel('booked/jobhistory');
                if (isset($bookedModel) && $bookedModel) {
                    $bookedModel->setTechId($technicianId);
                    $bookedModel->save();
//                $jobModel->setAcceptedBy();
//                $jobModel->setAcceptedTime();
                    $jobModel->setStatus('');
//                $jobModel->setCompletedTime();
//                $jobModel->setCompletedLocation();
                    $jobModel->setAppointmentId($bookedModel->getId());
                    $jobModel->save();
                    $historyModel->setAppointId($bookedModel->getId());
                    $historyModel->setTechId($technicianId);
                    $historyModel->setStatus('');
                    $historyModel->setCreatedAt($currentDateTime);
                    $historyModel->save();
                    $result['success'] = TRUE;
                    $result['id'] = $bookedModel->getId();
                }
                echo json_encode($result);
            } else {
                $prob['msg'] = "Appointment ID dose not exist";
                if (isset($prob) && $prob) {
                    echo json_encode($prob);
                }
            }
        } else {
            $report['msg'] = "Parameters not found";
            if (isset($report) && $report) {
                echo json_encode($report);
            }
        }
    }

    public function andriodNotificationAction($messageToken, $messageString) {
        $data = array('message' => $messageString);
        $ids = array($messageToken);
        //var_dump($ids);exit;
        $apiKey = 'AIzaSyDqfxg6kzlRQHyM4_3jBcxROOboBCn1B6c';
        $url = 'https://android.googleapis.com/gcm/send';
        $post = array(
            'registration_ids' => $ids,
            'data' => $data,
        );
        $headers = array(
            'Authorization: key=' . $apiKey,
            'Content-Type: application/json'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url) or exit("failed 1");
        curl_setopt($ch, CURLOPT_POST, true) or exit("failed 2");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers) or exit("failed 3");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) or exit("failed 4");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post)) or exit("failed 5");
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            //echo 'GCM error: ' . curl_error($ch);
        }
        curl_close($ch);
        //return $result;
    }

    public function iosNotificationAction($messageToken, $messageString) {
        $deviceToken = $messageToken;
        $passphrase = '1234';
        $message = $messageString;
        $badge = 1;

        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', '/home/mightcode/web/mightcode.com/public_html/techmotionusa/app/code/local/Uni/Warranty/controllers/TechmotionDevelopment.pem');
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
        $fp = stream_socket_client(
                'ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

        if (!$fp)
            exit("Failed to connect: $err $errstr" . PHP_EOL);

        $body['aps'] = array(
            'alert' => $message,
            'badge' => $badge,
            'sound' => 'default'
        );
        $payload = json_encode($body);
        $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
        $result = fwrite($fp, $msg, strlen($msg));
        fclose($fp);
    }

    public function confirmationAction() {
        if ($this->getRequest()->getPost()) {
            $status = $this->getRequest()->getParam('status');
            $techId = $this->getRequest()->getParam('tech_id');
            $appointmentId = $this->getRequest()->getParam('appoint_id');
            if ($status) {
                $acceptedAt = Mage::getModel('core/date')->date('Y-m-d H:i:s');
                $jobModel = Mage::getModel('booked/job')->getCollection()->addFieldToFilter("appointment_id", array("eq" => $appointmentId));
                foreach ($jobModel as $changeStatus) {
                    $loadModel = Mage::getModel('booked/job')->load($changeStatus->getId());
                    $loadModel->setAcceptedBy($techId);
                    $loadModel->setStatus($status);
                    $loadModel->setAppointmentId($appointmentId);
                    $loadModel->setAcceptedTime($acceptedAt);
                    $loadModel->save();
                }
                $result['status'] = "confirm";
            } else {
                $bookedModel = Mage::getModel('booked/booked')->load($appointmentId);
                $bookedModel->setTechId();
                $bookedModel->save();
                $result['status'] = "Not confirm";
            }
            if (isset($result) && $result) {
                echo json_encode($result);
            }
        }
    }

    public function jobAction() {
        if ($this->getRequest()->getPost()) {
            $data = $this->getRequest()->getPost();
            $location = $data['location'];
//        $image=$data['image'];
            $techId = $data['tech_id'];
            $appointmentId = $data['appoint_id'];
            $status = $data['status'];
            $currentDateTime = Mage::getModel('core/date')->date('Y-m-d H:i:s');
            $jobModel = Mage::getModel('booked/job')->getCollection()->addFieldToFilter("appointment_id", array("eq" => $appointmentId));
            if (isset($jobModel)) {
                foreach ($jobModel as $jobId) {
                    $jId = $jobId['id'];
                    $updateJob = Mage::getModel('booked/job')->load($jId);
                    if ($status == 1) {
                        $location = $data['location'];
                        if (isset($_FILES['signature']['name'])) {
                            if ($_FILES['signature']['name']) {
                                $mediaPath = Mage::getBaseDir('media') . DS;
                                $folderName = 'signature_image';
                                $sourcePath = $mediaPath . $folderName . DS . $data['status'];
                                if (!file_exists($sourcePath)) {
                                    mkdir($sourcePath, 0777, TRUE);
                                    chmod($sourcePath, 0777);
                                }
                                $ext = explode(".", $_FILES['signature']['name']);
                                $newFileName = 'File-' . time() . "." . $ext[1];
                                $finalPath = $sourcePath . DS . $newFileName;
                                $pathToDb = strstr($finalPath, $data['status']);
                                move_uploaded_file($_FILES['signature']['tmp_name'], $finalPath);
                                $image = $pathToDb;
                            }
                        }
                        $updateJob->setAcceptedBy($techId);
                        $updateJob->setCompletedLocation($location);
                        $updateJob->setCompletedTime($currentDateTime);
                        $updateJob->setStatus(2);
                        $updateJob->setWorkDescription(isset($data['work_description']) ? $data['work_description'] : '');
                        if (isset($image) && $image) {
                            $updateJob->setSignatureImg($image);
                        }
                        $updateJob->save();
                        $result['status'] = "Task Completed at" . " " . $currentDateTime;
                    } else {
                        $updateJob->delete();
                        $updateJob->save();
                        $result['status'] = "Success";
                    }
                }
            } else {
                $result['status'] = "Appointment Id Does Not Exiest";
            }
            echo json_encode($result);
        }
    }

    public function serviceFeedbackAction() {
        if ($this->getRequest()->getPost()) {
            $data = $this->getRequest()->getPost();
            $bookedModel = Mage::getModel('booked/booked')->getCollection()->addFieldToFilter("appoint_id", array("eq" => $data['unique_id']));
            if (isset($bookedModel) && $bookedModel->getData()) {
                foreach ($bookedModel as $saveFeed) {
                    $loadBooked = Mage::getModel('booked/booked')->load($saveFeed->getId());
                    $loadBooked->setFeedback($data['feedback']);
                    $loadBooked->save();
                }
                $result['feedback'] = "customer feedback added sucessfully";
                echo json_encode($result);
            } else {
                $prob['msg'] = " " . $data['unique_id'] . " " . "dose not exist";
                if (isset($prob) && $prob) {
                    echo json_encode($prob);
                }
            }
        }
    }

    public function changePasswordAction() {
        if ($this->getRequest()->getPost()) {
            $data = $this->getRequest()->getPost();
            if (isset($data['id'], $data['old_password'], $data['group'], $data['new_password']) && $data['id'] && $data['old_password'] && $data['group'] && $data['new_password']) {
                $filter = Mage::getModel('customer/customer')->getCollection()->addAttributeToFilter("entity_id", array("eq" => $data['id']));
                if (isset($filter) && $filter->getData()) {
                    $customer = Mage::getModel('customer/customer')->load($data['id']);
                    $email = $customer->getEmail();
                    $loginCheck = $this->loginApiAction($email, $data['old_password'], $data['group']);
                    if (array_key_exists("error", $loginCheck)) {
                        $result['status'] = "wrong old password";
                    } else {
                        if (array_key_exists("unique_id", $loginCheck)) {
                            if ($loginCheck['unique_id'] == $data['id']) {
                                $customer->setPassword($data['new_password']);
                                $customer->save();
                                $result['status'] = "password change successfully";
                            }
                        } else {
                            $result['status'] = "Unable to change password";
                        }
                    }

                    echo json_encode($result);
                } else {
                    $validId['id'] = "Please Provide Valid ID";
                    if (isset($validId) && $validId) {
                        echo json_encode($validId);
                    }
                }
            } else {
                $errorMsg["erreo"] = "Required Data not are found";
                if (isset($errorMsg) && $errorMsg) {
                    echo json_encode($errorMsg);
                }
            }
        }
    }

}
