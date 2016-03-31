function custom_email_validation_filter($result, $tag) {
        $type = $tag['type'];
        $name = $tag['name'];
        if ($name == 'your-email') { // Only apply to fields with the form field name of "company-email"
            $the_value = $_POST[$name];
            if (!preg_match('/^[a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~\.-]+$/', $the_value)) {
                $result['valid'] = false;
                $result['reason'][$name] = 'Invalid Email Address';
            }
        }
        return $result;
    }

    add_filter('wpcf7_validate_email', 'custom_email_validation_filter', 10, 2); // Email field
    add_filter('wpcf7_validate_email*', 'custom_email_validation_filter', 10, 2); // Req. Email field
