<?php
session_start();
//if (!is_user_logged_in()) {
//    wp_redirect(site_url('login'), 302);
//    exit;
//}
if (is_user_logged_in()) {
    $id = get_current_user_id();
    $userMetaData = get_user_meta($id);
    $mapData = $wpdb->get_row("SELECT * FROM wp_map_planning_entity WHERE customer_id=$id");
}
?>
<?php
$unitOption = array(0 => 'sqm', 1 => 'sqft', 2 => 'square Yard(s)');
$langOption = array(0 => 'Hindi', 1 => 'English');
$msg = "";
$data = $_POST;
$unit = isset($data['unit']) ? $data['unit'] : "";
unset($data['unit']);
$fl = 0;
$errFl = 0;
try {
    if ($data && empty($data)) {
        throw new Exception('Empty fields not allowed!');
    }
    if ($data) {

        if (isset($data['captcha_code']) && $data['captcha_code'] != $_SESSION['captcha_code']) {
            throw new Exception('Invalid or empty Captcha Code!');
        }
        unset($data['captcha_code']);

        $data['customer_id'] = $id;
        $data['created_on'] = date('Y-m-d H:i:s');
        if (!empty($data['language']))
            $data['language'] = implode(',', $data['language']);
        $data['unit'] = $unit;

        $cid = $wpdb->get_var("SELECT id FROM wp_map_planning_entity WHERE customer_id=$id");

        if ($cid) {
            $data['updated_on'] = date('Y-m-d H:i:s');
            $result = $wpdb->update('wp_map_planning_entity', $data, array('customer_id' => $id));
            $msg = "Your request has been successfully updated.";
            $errFl = 1;
        } else {
            $data['customer_id'] = $id;
            $data['created_on'] = date('Y-m-d H:i:s');
            $result = $wpdb->insert('wp_map_planning_entity', $data);
            $serviceData = $wpdb->get_row("SELECT user_id FROM wp_service_entity WHERE user_id=$id");
            if (isset($serviceData->user_id) && $serviceData->user_id) {
                $result = $wpdb->update('wp_service_entity', array('user_id' => $id, 'service_planning' => 1), array('user_id' => $id));
            } else {
                $wpdb->insert('wp_service_entity', array('user_id' => $id, 'service_planning' => 1));
            }
            update_user_meta($id, 'service_count', $userMetaData['service_count'][0] + 1);

            $msg = "Your request has been successfully saved. We will contact you soon.";
            $errFl = 1;
        }
        $mapData = $wpdb->get_row("SELECT * FROM wp_map_planning_entity WHERE customer_id=$id");

        if (is_wp_error($result)) {
//            echo $result->get_error_message();
            throw new Exception('An error occured! Try again!');
        }
    }
} catch (Exception $ex) {
    $msg = $ex->getMessage();
    $errFl = 2;
}
?>
<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the wordpress construct of pages
 * and that other 'pages' on your wordpress site will use a
 * different template.
 *
 * @package WordPress
 * @subpackage wpbootstrap
 * @since wpbootstrap 0.1
 */
get_header();
?>
<?php
if ($errFl == 1)
    $class = ' success';
else if ($errFl == 2)
    $class = ' error';
else
    $class = '';
?>
<div class="msg<?php echo $class; ?>">
    <span><?php echo isset($msg) ? $msg : ""; ?></span>
    <span class="close">X</span>
</div>
<div class="main-wrapper col-sm-12">
    <div class="row">    
        <!--        <div class="col-sm-3 border-right" style="display: none;">
                    <div class="col-sm-12 advertise">
                        <div class="row">
                            <h2 class="page-title"><a href="http://housemap.in/sample-map">View More Sample Map</a></h2>
                            <div class="border-visible">
        <?php // get_sidebar('map'); ?>
                            </div>
                        </div>
                    </div>
                </div>-->
        <div class="col-sm-12 columns clearfix">
            <div class="inner-content">
                <h2 class="page-title">Welcome to planning section</h2>
                <h2 class="subpage-title">(It will help us to draw your house map)</h2>
                <a class="button btn btn-top fr" href="<?php echo site_url('sample-map'); ?>">View Sample Maps</a>
                <h5 class="sub-page">Fill details of your plot in the form given below</h5>
                <form name="planningform" id="planningform" method="post" action="" class="popup-form">
                    <div class="form-wrap">
                        <h4 class="form-heading">Detail of Plot</h4>
                        <div class="form-wrap-in">
                            <!--                            <div class="field">
                                                            <label>Name</label>
                                                            <div class="input-box"><input type="text" name="name" value="<?php // echo isset($userMetaData['first_name'][0]) ? $userMetaData['first_name'][0] . " " . $userMetaData['last_name'][0] : '';        ?>" class="required form-control" disabled="disabled"></div>
                                                        </div>
                                                        <div class="field">
                                                            <label>Age</label>
                                                            <div class="input-box"><input type="text" name="age" value="<?php // echo isset($userMetaData['age'][0]) ? $userMetaData['age'][0] : '';        ?>" class="required integer form-control" disabled="disabled"></div>
                                                        </div>
                                                        <div class="field">
                                                            <label>Mob. No.</label>
                                                            <div class="input-box"><input type="text" name="mobile" value="<?php // echo isset($userMetaData['mobileno'][0]) ? $userMetaData['mobileno'][0] : '';        ?>" class="required form-control" disabled="disabled"></div>
                                                        </div>
                                                        <div class="field">
                                                            <label>City</label>
                                                            <div class="input-box"><input type="text" name="city" value="<?php // echo isset($userMetaData['city'][0]) ? $userMetaData['city'][0] : '';        ?>" class="required form-control" disabled="disabled"></div>
                                                        </div>
                                                        <div class="field">
                                                            <label>District</label>
                                                            <div class="input-box"><input type="text" name="district" value="<?php // echo isset($userMetaData['district'][0]) ? $userMetaData['district'][0] : '';        ?>" class="required form-control" disabled="disabled"></div>
                                                        </div>
                                                        <div class="field">
                                                            <label>State</label>
                                                            <div class="input-box"><input type="text" name="state" value="<?php // echo isset($userMetaData['state'][0]) ? $userMetaData['state'][0] : '';        ?>" class="required form-control" disabled="disabled"></div>
                                                        </div>-->
                            <div class="field">
                                <label>Address of plot</label>
                                <div class="input-box"><input type="text" name="plot_address" value="<?php echo isset($mapData->plot_address) ? $mapData->plot_address : isset($_POST['plot_address'])? $_POST['plot_address']:''; ?>" class="required form-control"/></div>
                            </div>
                            <div class="field">
                                <label>Land mark near of plot</label>
                                <div class="input-box"><input type="text" name="plot_landmark" value="<?php echo isset($mapData->plot_landmark) ? $mapData->plot_landmark :isset($_POST['plot_landmark'])? $_POST['plot_landmark']:'';?>" class="required form-control"/></div>
                            </div>
                            <div class="field">
                                <label>Meeting Time</label>
                                <div class="input-box"><input type="text" id="datepicker" name="discusstime" value="<?php echo isset($mapData->discusstime) ? $mapData->discusstime : isset($_POST['discusstime'])? $_POST['discusstime']:''; ?>" class="required form-control"/></div>                            
                            </div>
                            <div class="field">
                                <label>Area of plot</label>
                                <div class="input-box">
                                    <input type="text" name="area" value="<?php echo isset($mapData->area) ? $mapData->area : isset($_POST['area'])? $_POST['area']:''; ?>" class="required number form-control"/>
                                    <select class="area-select form-control" name="unit">
                                        <?php foreach ($unitOption as $value) { ?>
                                            <option value="<?Php echo $value; ?>"<?php echo ($value == (isset($mapData->unit) ? $mapData->unit : '')) ? 'selected' : isset($_POST['unit'])? $_POST['unit']:''; ?>><?php echo $value; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-wrap">
                        <h4 class="form-heading">Arm Of Plot</h4>
                        <div class="form-wrap-in">
                            <div class="field">
                                <label>North Arm</label>
                                <div class="input-box"><input type="text" name="northarm" value="<?php echo isset($mapData->northarm) ? $mapData->northarm : isset($_POST['northarm'])? $_POST['northarm']:''; ?>" class="required form-control"/></div>
                            </div>
                            <div class="field">
                                <label>South Arm</label>
                                <div class="input-box"><input type="text" name="southarm" value="<?php echo isset($mapData->southarm) ? $mapData->southarm : isset($_POST['southarm'])? $_POST['southarm']:''; ?>" class="required form-control"/></div>
                            </div>
                            <div class="field">
                                <label>East Arm</label>
                                <div class="input-box"><input type="text" name="eastarm" value="<?php echo isset($mapData->southarm) ? $mapData->southarm : isset($_POST['southarm'])? $_POST['southarm']:''; ?>" class="required form-control"/></div>
                            </div>
                            <div class="field">
                                <label>West Arm</label>
                                <div class="input-box"><input type="text" name="westarm" value="<?php echo isset($mapData->westarm) ? $mapData->westarm : isset($_POST['westarm'])? $_POST['westarm']:''; ?>" class="required form-control"/></div>
                            </div>
                            <div class="field">
                                <label class="lg-label">NE Corner to SW Corner (Diagonal)</label>
                                <div class="input-box"><input type="text" name="diagonal" value="<?php echo isset($mapData->diagonal) ? $mapData->diagonal : isset($_POST['diagonal'])? $_POST['diagonal']:'';?>" class="required form-control"/></div>
                            </div>
                        </div>
                    </div>


                    <div class="form-wrap">
                        <h4 class="form-heading">Boundary Of Plot </h4>
                        <div class="form-wrap-in">
                            <div class="field">
                                <label>North</label>
                                <div class="input-box"><input type="text" name="boundary_north" value="<?php echo isset($mapData->boundary_north) ? $mapData->boundary_north :isset($_POST['boundary_north'])? $_POST['boundary_north']:''; ?>" class="required form-control"/></div>
                            </div>
                            <div class="field">
                                <label>South</label>
                                <div class="input-box"><input type="text" name="boundary_south" value="<?php echo isset($mapData->boundary_south) ? $mapData->boundary_south : isset($_POST['boundary_south'])? $_POST['boundary_south']:''; ?>" class="required form-control"/></div>
                            </div>
                            <div class="field">
                                <label>East</label>
                                <div class="input-box"><input type="text" name="boundary_east" value="<?php echo isset($mapData->boundary_east) ? $mapData->boundary_east : isset($_POST['boundary_east'])? $_POST['boundary_east']:''; ?>" class="required form-control"/></div>
                            </div>
                            <div class="field">
                                <label>West</label>
                                <div class="input-box"><input type="text" name="boundary_west" value="<?php echo isset($mapData->boundary_west) ? $mapData->boundary_west : isset($_POST['boundary_west'])? $_POST['boundary_west']:''; ?>" class="required form-control"/></div>
                            </div>
                            <div class="field">
                                <label class="lg-label">Width of road in front of plot</label>
                                <div class="input-box"><input type="text" name="roadwidth" value="<?php echo isset($mapData->roadwidth) ? $mapData->roadwidth : isset($_POST['roadwidth'])? $_POST['roadwidth']:''; ?>" class="required number form-control"/></div>
                            </div>
                            <div class="field">
                                <label class="lg-label">Select Language</label>
                                <div class="input-box">
                                    <?php
                                    $language = array();
                                    if (isset($mapData->language)) {
                                        $language = explode(',', $mapData->language);
                                    }
                                    foreach ($langOption as $value) {
                                        ?>
                                        <div class="input-checkbox">
                                            <label><?php echo $value; ?></label>
                                            <input type="checkbox" value="<?php echo $value; ?>"  name="language[]" class="required" <?php echo (in_array($value, $language)) ? 'checked=checked' : ''; ?>>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="field">
                                <label class="lg-label">Please mention your requirement</label>
                                <div class="input-box"><textarea class="form-control" name="requirement" ><?php echo isset($mapData->requirement) ? $mapData->requirement : isset($_POST['requirement'])? $_POST['requirement']:''; ?></textarea></div>
                            </div>
                        </div>
                        <p><strong>Note:</strong>&nbsp;&nbsp;Planning Services are free of cost.</p>
                    </div>
                    <div><label>Enter the text shown in image below</label></div>
                    <div>
                        <img src="<?php echo site_url('captcha'); ?>"/>
                        <input type="text" name="captcha_code">
                    </div>
                    <div class="button-set">
                        <input type="submit"  value="Submit" id="planningmap" class="button-summit <?php echo(!is_user_logged_in()) ? "disabled" : '' ?>" disabled="disabled">
                        <a class="button btn btn-top fr" href="<?php echo site_url('sample-map'); ?>">View Sample Maps</a>
                    </div>                
                </form>
            </div>
        </div>

        <!--        <div class="col-sm-3" style="display: none;">  
                    <div class="col-sm-12 advertise">
                        <div class="row">
                                <br/>
        <?php // get_sidebar('right-planning'); ?>
                        </div>
                    </div>    
                </div>-->
    </div>

</div>
<?php
if (!is_user_logged_in()) {
    get_user();
}
get_footer();
?>

<script type="text/javascript">
    (function($) {
        $(document).ready(function()
        {
           
            $("[name='area']").keypress(function(e) {
                if (String.fromCharCode(e.which).match(/[A-Za-z!@#~`$%^&*()_+\-=\[\]{};':"\\|,<>\/?\s]/)) {
                    e.preventDefault();
                }
            });
            $("[name='roadwidth']").keypress(function(e) {
                if (String.fromCharCode(e.which).match(/[A-Za-z!@#$%~`^&*()_+\-=\[\]{};':"\\|,<>\/?\s]/)) {
                    e.preventDefault();
                }
            });

            $("#planningmap").prop("disabled", "disabled");
            $("#planningmap").prop("disabled", !$("#planningmap").prop("disabled"));

        });
    })(jQuery);
</script>
