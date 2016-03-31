function ajax_form() {
    global $wpdb;
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $sql = $wpdb->insert('wp_customers', array(
        'name' => $name,
        'email' => $email,
        'address' => $address,
        'phone' => $phone
    ));
    if ($sql === FALSE) {
        echo "Error";
    } else {
        echo "Customer '" . $name . "' successfully added, row ID is " . $wpdb->insert_id;
    }
    die();
}

add_action('wp_ajax_ajax_form', 'ajax_form'); //admin side
add_action('wp_ajax_nopriv_ajax_form', 'ajax_form'); //for frontend





function ajax_form() {
    global $wpdb;
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $sql = $wpdb->insert('wp_customers', array(
        'name' => $name,
        'email' => $email,
        'address' => $address,
        'phone' => $phone
    ));
    if ($sql === FALSE) {
        echo "Error";
    } else {
        echo "Customer '" . $name . "' successfully added, row ID is " . $wpdb->insert_id;
    }
    die();
}

add_action('wp_ajax_ajax_form', 'ajax_form'); //admin side
add_action('wp_ajax_nopriv_ajax_form', 'ajax_form'); //for frontend

function validate_phone() {
    global $wpdb;
    $phone = $_POST['phone'];
    $result = $wpdb->get_results("SELECT * FROM $wpdb->usermeta WHERE meta_key = 'mobileno'");
    foreach ($result as $mesg) {
        $mobArr[] = $mesg->meta_value;
    }
    if (in_array($phone, $mobArr)) {
//            $res['message'] = 'Phone no already exist.';
            $res['success'] = TRUE;
        } else {
            $res['error'] = TRUE;
        }
        echo json_encode($res);exit;
    }

    add_action('wp_ajax_validate_phone', 'validate_phone'); //admin side
    add_action('wp_ajax_nopriv_validate_phone', 'validate_phone'); //for frontend

    add_action('show_user_profile', 'yoursite_extra_user_profile_fields');
    add_action('edit_user_profile', 'yoursite_extra_user_profile_fields');

    function yoursite_extra_user_profile_fields($user) {
        ?>
        <h3><?php _e("Extra profile information", "blank"); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="mobileno"><?php _e("Mobile No."); ?></label></th>
                <td>
                    <input type="text" name="mobileno" id="mobileno" class="regular-text" 
                           value="<?php echo esc_attr(get_the_author_meta('mobileno', $user->ID)); ?>" /><br />
                    <span class="description"><?php _e("Please Enter Your Mobile No."); ?></span>
                </td>
            </tr>
        </table>
        <?php
    }

    add_action('personal_options_update', 'yoursite_save_extra_user_profile_fields');
    add_action('edit_user_profile_update', 'yoursite_save_extra_user_profile_fields');

    function yoursite_save_extra_user_profile_fields($user_id) {
        $saved = false;
        if (current_user_can('edit_user', $user_id)) {
            update_user_meta($user_id, 'mobileno', $_POST['mobileno']);
            $saved = true;
        }
        return true;
    }

    function wpdocs_selectively_enqueue_admin_script() {
        wp_enqueue_script('my_custom_script', get_template_directory_uri() . '/js/validate.js', array('jquery'), '1.0');
    }

    add_action('admin_enqueue_scripts', 'wpdocs_selectively_enqueue_admin_script');

