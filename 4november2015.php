user-edit.php

<?php
/**
 * Edit user administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */
/** WordPress Administration Bootstrap */
require_once( dirname(__FILE__) . '/admin.php' );

wp_reset_vars(array('action', 'user_id', 'wp_http_referer'));

$user_id = (int) $user_id;
$current_user = wp_get_current_user();
if (!defined('IS_PROFILE_PAGE'))
    define('IS_PROFILE_PAGE', ( $user_id == $current_user->ID));

if (!$user_id && IS_PROFILE_PAGE)
    $user_id = $current_user->ID;
elseif (!$user_id && !IS_PROFILE_PAGE)
    wp_die(__('Invalid user ID.'));
elseif (!get_userdata($user_id))
    wp_die(__('Invalid user ID.'));

wp_enqueue_script('user-profile');

$title = IS_PROFILE_PAGE ? __('Profile') : __('Edit User');
if (current_user_can('edit_users') && !IS_PROFILE_PAGE)
    $submenu_file = 'users.php';
else
    $submenu_file = 'profile.php';

if (current_user_can('edit_users') && !is_user_admin())
    $parent_file = 'users.php';
else
    $parent_file = 'profile.php';

$profile_help = '<p>' . __('Your profile contains information about you (your &#8220;account&#8221;) as well as some personal options related to using WordPress.') . '</p>' .
        '<p>' . __('You can change your password, turn on keyboard shortcuts, change the color scheme of your WordPress administration screens, and turn off the WYSIWYG (Visual) editor, among other things. You can hide the Toolbar (formerly called the Admin Bar) from the front end of your site, however it cannot be disabled on the admin screens.') . '</p>' .
        '<p>' . __('Your username cannot be changed, but you can use other fields to enter your real name or a nickname, and change which name to display on your posts.') . '</p>' .
        '<p>' . __('You can log out of other devices, such as your phone or a public computer, by clicking the Log Out of All Other Sessions button.') . '</p>' .
        '<p>' . __('Required fields are indicated; the rest are optional. Profile information will only be displayed if your theme is set up to do so.') . '</p>' .
        '<p>' . __('Remember to click the Update Profile button when you are finished.') . '</p>';

get_current_screen()->add_help_tab(array(
    'id' => 'overview',
    'title' => __('Overview'),
    'content' => $profile_help,
));

get_current_screen()->set_help_sidebar(
        '<p><strong>' . __('For more information:') . '</strong></p>' .
        '<p>' . __('<a href="https://codex.wordpress.org/Users_Your_Profile_Screen" target="_blank">Documentation on User Profiles</a>') . '</p>' .
        '<p>' . __('<a href="https://wordpress.org/support/" target="_blank">Support Forums</a>') . '</p>'
);

$wp_http_referer = remove_query_arg(array('update', 'delete_count'), $wp_http_referer);

$user_can_edit = current_user_can('edit_posts') || current_user_can('edit_pages');

/**
 * Optional SSL preference that can be turned on by hooking to the 'personal_options' action.
 *
 * @since 2.7.0
 *
 * @param object $user User data object
 */
function use_ssl_preference($user) {
    ?>
    <tr class="user-use-ssl-wrap">
        <th scope="row"><?php _e('Use https') ?></th>
        <td><label for="use_ssl"><input name="use_ssl" type="checkbox" id="use_ssl" value="1" <?php checked('1', $user->use_ssl); ?> /> <?php _e('Always use https when visiting the admin'); ?></label></td>
    </tr>
    <?php
}

/**
 * Filter whether to allow administrators on Multisite to edit every user.
 *
 * Enabling the user editing form via this filter also hinges on the user holding
 * the 'manage_network_users' cap, and the logged-in user not matching the user
 * profile open for editing.
 *
 * The filter was introduced to replace the EDIT_ANY_USER constant.
 *
 * @since 3.0.0
 *
 * @param bool $allow Whether to allow editing of any user. Default true.
 */
if (is_multisite() && !current_user_can('manage_network_users') && $user_id != $current_user->ID && !apply_filters('enable_edit_any_user_configuration', true)
) {
    wp_die(__('You do not have permission to edit this user.'));
}

// Execute confirmed email change. See send_confirmation_on_profile_email().
if (is_multisite() && IS_PROFILE_PAGE && isset($_GET['newuseremail']) && $current_user->ID) {
    $new_email = get_option($current_user->ID . '_new_email');
    if ($new_email['hash'] == $_GET['newuseremail']) {
        $user = new stdClass;
        $user->ID = $current_user->ID;
        $user->user_email = esc_html(trim($new_email['newemail']));
        if ($wpdb->get_var($wpdb->prepare("SELECT user_login FROM {$wpdb->signups} WHERE user_login = %s", $current_user->user_login)))
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->signups} SET user_email = %s WHERE user_login = %s", $user->user_email, $current_user->user_login));
        wp_update_user($user);
        delete_option($current_user->ID . '_new_email');
        wp_redirect(add_query_arg(array('updated' => 'true'), self_admin_url('profile.php')));
        die();
    }
} elseif (is_multisite() && IS_PROFILE_PAGE && !empty($_GET['dismiss']) && $current_user->ID . '_new_email' == $_GET['dismiss']) {
    delete_option($current_user->ID . '_new_email');
    wp_redirect(add_query_arg(array('updated' => 'true'), self_admin_url('profile.php')));
    die();
}

switch ($action) {
    case 'update':

        check_admin_referer('update-user_' . $user_id);

        if (!current_user_can('edit_user', $user_id))
            wp_die(__('You do not have permission to edit this user.'));

        if (IS_PROFILE_PAGE) {
            /**
             * Fires before the page loads on the 'Your Profile' editing screen.
             *
             * The action only fires if the current user is editing their own profile.
             *
             * @since 2.0.0
             *
             * @param int $user_id The user ID.
             */
            do_action('personal_options_update', $user_id);
        } else {
            /**
             * Fires before the page loads on the 'Edit User' screen.
             *
             * @since 2.7.0
             *
             * @param int $user_id The user ID.
             */
            do_action('edit_user_profile_update', $user_id);
        }

// Update the email address in signups, if present.
        if (is_multisite()) {
            $user = get_userdata($user_id);

            if ($user->user_login && isset($_POST['email']) && is_email($_POST['email']) && $wpdb->get_var($wpdb->prepare("SELECT user_login FROM {$wpdb->signups} WHERE user_login = %s", $user->user_login))) {
                $wpdb->query($wpdb->prepare("UPDATE {$wpdb->signups} SET user_email = %s WHERE user_login = %s", $_POST['email'], $user_login));
            }
        }

// Update the user.
        $errors = edit_user($user_id);

// Grant or revoke super admin status if requested.
        if (is_multisite() && is_network_admin() && !IS_PROFILE_PAGE && current_user_can('manage_network_options') && !isset($super_admins) && empty($_POST['super_admin']) == is_super_admin($user_id)) {
            empty($_POST['super_admin']) ? revoke_super_admin($user_id) : grant_super_admin($user_id);
        }

        if (!is_wp_error($errors)) {
            $redirect = add_query_arg('updated', true, get_edit_user_link($user_id));
            if ($wp_http_referer)
                $redirect = add_query_arg('wp_http_referer', urlencode($wp_http_referer), $redirect);
            wp_redirect($redirect);
            exit;
        }

    default:
        $profileuser = get_user_to_edit($user_id);

        if (!current_user_can('edit_user', $user_id))
            wp_die(__('You do not have permission to edit this user.'));

        $sessions = WP_Session_Tokens::get_instance($profileuser->ID);

        include(ABSPATH . 'wp-admin/admin-header.php');
        ?>

        <?php if (!IS_PROFILE_PAGE && is_super_admin($profileuser->ID) && current_user_can('manage_network_options')) { ?>
            <div class="updated"><p><strong><?php _e('Important:'); ?></strong> <?php _e('This user has super admin privileges.'); ?></p></div>
        <?php } ?>
        <?php if (isset($_GET['updated'])) : ?>
            <div id="message" class="updated notice is-dismissible">
                <?php if (IS_PROFILE_PAGE) : ?>
                    <p><strong><?php _e('Profile updated.') ?></strong></p>
                <?php else: ?>
                    <p><strong><?php _e('User updated.') ?></strong></p>
                <?php endif; ?>
                <?php if ($wp_http_referer && !IS_PROFILE_PAGE) : ?>
                    <p><a href="<?php echo esc_url($wp_http_referer); ?>"><?php _e('&larr; Back to Users'); ?></a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['failed'])) : ?>
            <div id="message" class="error">
                <?php if (IS_PROFILE_PAGE) : ?>
                    <p><strong><?php _e('Number Already exists.') ?></strong></p>
                <?php else: ?>
                    <p><strong><?php _e('Number Already exists.') ?></strong></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($errors) && is_wp_error($errors)) : ?>
            <div class="error"><p><?php echo implode("</p>\n<p>", $errors->get_error_messages()); ?></p></div>
        <?php endif; ?>

        <div class="wrap" id="profile-page">
            <h2>
                <?php
                echo esc_html($title);
                if (!IS_PROFILE_PAGE) {
                    if (current_user_can('create_users')) {
                        ?>
                        <a href="user-new.php" class="add-new-h2"><?php echo esc_html_x('Add New', 'user'); ?></a>
                    <?php } elseif (is_multisite() && current_user_can('promote_users')) { ?>
                        <a href="user-new.php" class="add-new-h2"><?php echo esc_html_x('Add Existing', 'user'); ?></a>
                        <?php
                    }
                }
                ?>
            </h2>
            <form id="your-profile" action="<?php echo esc_url(self_admin_url(IS_PROFILE_PAGE ? 'profile.php' : 'user-edit.php' )); ?>" method="post" novalidate="novalidate"<?php
            /**
             * Fires inside the your-profile form tag on the user editing screen.
             *
             * @since 3.0.0
             */
            do_action('user_edit_form_tag');
            ?>>
                      <?php wp_nonce_field('update-user_' . $user_id) ?>
                      <?php if ($wp_http_referer) : ?>
                    <input type="hidden" name="wp_http_referer" value="<?php echo esc_url($wp_http_referer); ?>" />
                <?php endif; ?>
                <p>
                    <input type="hidden" name="from" value="profile" />
                    <input type="hidden" name="checkuser_id" value="<?php echo get_current_user_id(); ?>" />
                </p>

                <h3><?php _e('Personal Options'); ?></h3>

                <table class="form-table">
                    <?php if (!( IS_PROFILE_PAGE && !$user_can_edit )) : ?>
                        <tr class="user-rich-editing-wrap">
                            <th scope="row"><?php _e('Visual Editor'); ?></th>
                            <td><label for="rich_editing"><input name="rich_editing" type="checkbox" id="rich_editing" value="false" <?php if (!empty($profileuser->rich_editing)) checked('false', $profileuser->rich_editing); ?> /> <?php _e('Disable the visual editor when writing'); ?></label></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (count($_wp_admin_css_colors) > 1 && has_action('admin_color_scheme_picker')) : ?>
                        <tr class="user-admin-color-wrap">
                            <th scope="row"><?php _e('Admin Color Scheme') ?></th>
                            <td><?php
                                /**
                                 * Fires in the 'Admin Color Scheme' section of the user editing screen.
                                 *
                                 * The section is only enabled if a callback is hooked to the action,
                                 * and if there is more than one defined color scheme for the admin.
                                 *
                                 * @since 3.0.0
                                 * @since 3.8.1 Added `$user_id` parameter.
                                 *
                                 * @param int $user_id The user ID.
                                 */
                                do_action('admin_color_scheme_picker', $user_id);
                                ?></td>
                        </tr>
                        <?php
                    endif; // $_wp_admin_css_colors
                    if (!( IS_PROFILE_PAGE && !$user_can_edit )) :
                        ?>
                        <tr class="user-comment-shortcuts-wrap">
                            <th scope="row"><?php _e('Keyboard Shortcuts'); ?></th>
                            <td><label for="comment_shortcuts"><input type="checkbox" name="comment_shortcuts" id="comment_shortcuts" value="true" <?php if (!empty($profileuser->comment_shortcuts)) checked('true', $profileuser->comment_shortcuts); ?> /> <?php _e('Enable keyboard shortcuts for comment moderation.'); ?></label> <?php _e('<a href="https://codex.wordpress.org/Keyboard_Shortcuts" target="_blank">More information</a>'); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="show-admin-bar user-admin-bar-front-wrap">
                        <th scope="row"><?php _e('Toolbar'); ?></th>
                        <td><fieldset><legend class="screen-reader-text"><span><?php _e('Toolbar') ?></span></legend>
                                <label for="admin_bar_front">
                                    <input name="admin_bar_front" type="checkbox" id="admin_bar_front" value="1"<?php checked(_get_admin_bar_pref('front', $profileuser->ID)); ?> />
                                    <?php _e('Show Toolbar when viewing site'); ?></label><br />
                            </fieldset>
                        </td>
                    </tr>
                    <?php
                    /**
                     * Fires at the end of the 'Personal Options' settings table on the user editing screen.
                     *
                     * @since 2.7.0
                     *
                     * @param WP_User $profileuser The current WP_User object.
                     */
                    do_action('personal_options', $profileuser);
                    ?>

                </table>
                <?php
                if (IS_PROFILE_PAGE) {
                    /**
                     * Fires after the 'Personal Options' settings table on the 'Your Profile' editing screen.
                     *
                     * The action only fires if the current user is editing their own profile.
                     *
                     * @since 2.0.0
                     *
                     * @param WP_User $profileuser The current WP_User object.
                     */
                    do_action('profile_personal_options', $profileuser);
                }
                ?>

                <h3><?php _e('Name') ?></h3>

                <table class="form-table">
                    <tr class="user-user-login-wrap">
                        <th><label for="user_login"><?php _e('Username'); ?></label></th>
                        <td><input type="text" name="user_login" id="user_login" value="<?php echo esc_attr($profileuser->user_login); ?>" disabled="disabled" class="regular-text" /> <span class="description"><?php _e('Usernames cannot be changed.'); ?></span></td>
                    </tr>

                    <?php if (!IS_PROFILE_PAGE && !is_network_admin()) : ?>
                        <tr class="user-role-wrap"><th><label for="role"><?php _e('Role') ?></label></th>
                            <td><select name="role" id="role">
                                    <?php
// Compare user role against currently editable roles
                                    $user_roles = array_intersect(array_values($profileuser->roles), array_keys(get_editable_roles()));
                                    $user_role = reset($user_roles);

// print the full list of roles with the primary one selected.
                                    wp_dropdown_roles($user_role);

// print the 'no role' option. Make it selected if the user has no role yet.
                                    if ($user_role)
                                        echo '<option value="">' . __('&mdash; No role for this site &mdash;') . '</option>';
                                    else
                                        echo '<option value="" selected="selected">' . __('&mdash; No role for this site &mdash;') . '</option>';
                                    ?>
                                </select></td></tr>
                        <?php
                    endif; //!IS_PROFILE_PAGE

                    if (is_multisite() && is_network_admin() && !IS_PROFILE_PAGE && current_user_can('manage_network_options') && !isset($super_admins)) {
                        ?>
                        <tr class="user-super-admin-wrap"><th><?php _e('Super Admin'); ?></th>
                            <td>
                                <?php if ($profileuser->user_email != get_site_option('admin_email') || !is_super_admin($profileuser->ID)) : ?>
                                    <p><label><input type="checkbox" id="super_admin" name="super_admin"<?php checked(is_super_admin($profileuser->ID)); ?> /> <?php _e('Grant this user super admin privileges for the Network.'); ?></label></p>
                                <?php else : ?>
                                    <p><?php _e('Super admin privileges cannot be removed because this user has the network admin email.'); ?></p>
                                <?php endif; ?>
                            </td></tr>
                    <?php } ?>

                    <tr class="user-first-name-wrap">
                        <th><label for="first_name"><?php _e('First Name') ?></label></th>
                        <td><input type="text" name="first_name" id="first_name" value="<?php echo esc_attr($profileuser->first_name) ?>" class="regular-text" /></td>
                    </tr>

                    <tr class="user-last-name-wrap">
                        <th><label for="last_name"><?php _e('Last Name') ?></label></th>
                        <td><input type="text" name="last_name" id="last_name" value="<?php echo esc_attr($profileuser->last_name) ?>" class="regular-text" /></td>
                    </tr>

                    <tr class="user-nickname-wrap">
                        <th><label for="nickname"><?php _e('Nickname'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
                        <td><input type="text" name="nickname" id="nickname" value="<?php echo esc_attr($profileuser->nickname) ?>" class="regular-text" /></td>
                    </tr>

                    <tr class="user-display-name-wrap">
                        <th><label for="display_name"><?php _e('Display name publicly as') ?></label></th>
                        <td>
                            <select name="display_name" id="display_name">
                                <?php
                                $public_display = array();
                                $public_display['display_nickname'] = $profileuser->nickname;
                                $public_display['display_username'] = $profileuser->user_login;

                                if (!empty($profileuser->first_name))
                                    $public_display['display_firstname'] = $profileuser->first_name;

                                if (!empty($profileuser->last_name))
                                    $public_display['display_lastname'] = $profileuser->last_name;

                                if (!empty($profileuser->first_name) && !empty($profileuser->last_name)) {
                                    $public_display['display_firstlast'] = $profileuser->first_name . ' ' . $profileuser->last_name;
                                    $public_display['display_lastfirst'] = $profileuser->last_name . ' ' . $profileuser->first_name;
                                }

                                if (!in_array($profileuser->display_name, $public_display)) // Only add this if it isn't duplicated elsewhere
                                    $public_display = array('display_displayname' => $profileuser->display_name) + $public_display;

                                $public_display = array_map('trim', $public_display);
                                $public_display = array_unique($public_display);

                                foreach ($public_display as $id => $item) {
                                    ?>
                                    <option <?php selected($profileuser->display_name, $item); ?>><?php echo $item; ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>

                <h3><?php _e('Contact Info') ?></h3>

                <table class="form-table">
                    <tr class="user-email-wrap">
                        <th><label for="email"><?php _e('E-mail'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
                        <td><input type="email" name="email" id="email-validate" value="<?php echo esc_attr($profileuser->user_email) ?>" class="regular-text ltr" />
                            <?php
                            $new_email = get_option($current_user->ID . '_new_email');
                            if ($new_email && $new_email['newemail'] != $current_user->user_email && $profileuser->ID == $current_user->ID) :
                                ?>
                                <div class="updated inline">
                                    <p><?php printf(__('There is a pending change of your e-mail to <code>%1$s</code>. <a href="%2$s">Cancel</a>'), $new_email['newemail'], esc_url(self_admin_url('profile.php?dismiss=' . $current_user->ID . '_new_email'))); ?></p>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr class="user-url-wrap">
                        <th><label for="url"><?php _e('Website') ?></label></th>
                        <td><input type="url" name="url" id="url" value="<?php echo esc_attr($profileuser->user_url) ?>" class="regular-text code" /></td>
                    </tr>

                    <?php
                    foreach (wp_get_user_contact_methods($profileuser) as $name => $desc) {
                        ?>
                        <tr class="user-<?php echo $name; ?>-wrap">
                            <th><label for="<?php echo $name; ?>">
                                    <?php
                                    /**
                                     * Filter a user contactmethod label.
                                     *
                                     * The dynamic portion of the filter hook, `$name`, refers to
                                     * each of the keys in the contactmethods array.
                                     *
                                     * @since 2.9.0
                                     *
                                     * @param string $desc The translatable label for the contactmethod.
                                     */
                                    echo apply_filters("user_{$name}_label", $desc);
                                    ?>
                                </label></th>
                            <td><input type="text" name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="<?php echo esc_attr($profileuser->$name) ?>" class="regular-text" /></td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>

                <h3><?php IS_PROFILE_PAGE ? _e('About Yourself') : _e('About the user'); ?></h3>

                <table class="form-table">
                    <tr class="user-description-wrap">
                        <th><label for="description"><?php _e('Biographical Info'); ?></label></th>
                        <td><textarea name="description" id="description" rows="5" cols="30"><?php echo $profileuser->description; // textarea_escaped    ?></textarea>
                            <p class="description"><?php _e('Share a little biographical information to fill out your profile. This may be shown publicly.'); ?></p></td>
                    </tr>

                    <?php
                    /** This filter is documented in wp-admin/user-new.php */
                    $show_password_fields = apply_filters('show_password_fields', true, $profileuser);
                    if ($show_password_fields) :
                        ?>
                        <tr id="password" class="user-pass1-wrap">
                            <th><label for="pass1"><?php _e('New Password'); ?></label></th>
                            <td>
                                <input class="hidden" value=" " /><!-- #24364 workaround -->
                                <input type="password" name="pass1" id="pass1" class="regular-text" size="16" value="" autocomplete="off" />
                                <p class="description"><?php _e('If you would like to change the password type a new one. Otherwise leave this blank.'); ?></p>
                            </td>
                        </tr>
                        <tr class="user-pass2-wrap">
                            <th scope="row"><label for="pass2"><?php _e('Repeat New Password'); ?></label></th>
                            <td>
                                <input name="pass2" type="password" id="pass2" class="regular-text" size="16" value="" autocomplete="off" />
                                <p class="description"><?php _e('Type your new password again.'); ?></p>
                                <br />
                                <div id="pass-strength-result"><?php _e('Strength indicator'); ?></div>
                                <p class="description indicator-hint"><?php echo wp_get_password_hint(); ?></p>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php if (IS_PROFILE_PAGE && count($sessions->get_all()) === 1) : ?>
                        <tr class="user-sessions-wrap hide-if-no-js">
                            <th>&nbsp;</th>
                            <td aria-live="assertive">
                                <div class="destroy-sessions"><button type="button" disabled class="button button-secondary"><?php _e('Log Out of All Other Sessions'); ?></button></div>
                                <p class="description">
                                    <?php _e('You are only logged in at this location.'); ?>
                                </p>
                            </td>
                        </tr>
                    <?php elseif (IS_PROFILE_PAGE && count($sessions->get_all()) > 1) : ?>
                        <tr class="user-sessions-wrap hide-if-no-js">
                            <th>&nbsp;</th>
                            <td aria-live="assertive">
                                <div class="destroy-sessions"><button type="button" class="button button-secondary" id="destroy-sessions"><?php _e('Log Out of All Other Sessions'); ?></button></div>
                                <p class="description">
                                    <?php _e('Left your account logged in at a public computer? Lost your phone? This will log you out everywhere except your current browser.'); ?>
                                </p>
                            </td>
                        </tr>
                    <?php elseif (!IS_PROFILE_PAGE && $sessions->get_all()) : ?>
                        <tr class="user-sessions-wrap hide-if-no-js">
                            <th>&nbsp;</th>
                            <td>
                                <p><button type="button" class="button button-secondary" id="destroy-sessions"><?php _e('Log Out of All Sessions'); ?></button></p>
                                <p class="description">
                                    <?php
                                    /* translators: 1: User's display name. */
                                    printf(__('Log %s out of all sessions'), $profileuser->display_name);
                                    ?>
                                </p>
                            </td>
                        </tr>
                    <?php endif; ?>

                </table>

                <?php
                if (IS_PROFILE_PAGE) {
                    /**
                     * Fires after the 'About Yourself' settings table on the 'Your Profile' editing screen.
                     *
                     * The action only fires if the current user is editing their own profile.
                     *
                     * @since 2.0.0
                     *
                     * @param WP_User $profileuser The current WP_User object.
                     */
                    do_action('show_user_profile', $profileuser);
                } else {
                    /**
                     * Fires after the 'About the User' settings table on the 'Edit User' screen.
                     *
                     * @since 2.0.0
                     *
                     * @param WP_User $profileuser The current WP_User object.
                     */
                    do_action('edit_user_profile', $profileuser);
                }
                ?>

                <?php
                /**
                 * Filter whether to display additional capabilities for the user.
                 *
                 * The 'Additional Capabilities' section will only be enabled if
                 * the number of the user's capabilities exceeds their number of
                 * of roles.
                 *
                 * @since 2.8.0
                 *
                 * @param bool    $enable      Whether to display the capabilities. Default true.
                 * @param WP_User $profileuser The current WP_User object.
                 */
                if (count($profileuser->caps) > count($profileuser->roles) && apply_filters('additional_capabilities_display', true, $profileuser)
                ) :
                    ?>
                    <h3><?php _e('Additional Capabilities'); ?></h3>
                    <table class="form-table">
                        <tr class="user-capabilities-wrap">
                            <th scope="row"><?php _e('Capabilities'); ?></th>
                            <td>
                                <?php
                                $output = '';
                                foreach ($profileuser->caps as $cap => $value) {
                                    if (!$wp_roles->is_role($cap)) {
                                        if ('' != $output)
                                            $output .= ', ';
                                        $output .= $value ? $cap : sprintf(__('Denied: %s'), $cap);
                                    }
                                }
                                echo $output;
                                ?>
                            </td>
                        </tr>
                    </table>
                <?php endif; ?>

                <input type="hidden" name="action" value="update" />
                <input type="hidden" name="user_id" id="user_id" value="<?php echo esc_attr($user_id); ?>" />

                <?php submit_button(IS_PROFILE_PAGE ? __('Update Profile') : __('Update User') ); ?>

            </form>
        </div>
        <?php
        break;
}
?>
<script type="text/javascript">
    if (window.location.hash == '#password') {
        document.getElementById('pass1').focus();
    }
</script>


user-new.php


<?php
/**
 * New User Administration Screen.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** WordPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

if ( is_multisite() ) {
	if ( ! current_user_can( 'create_users' ) && ! current_user_can( 'promote_users' ) )
		wp_die( __( 'Cheatin&#8217; uh?' ), 403 );
} elseif ( ! current_user_can( 'create_users' ) ) {
	wp_die( __( 'Cheatin&#8217; uh?' ), 403 );
}

if ( is_multisite() ) {
	function admin_created_user_email( $text ) {
		$roles = get_editable_roles();
		$role = $roles[ $_REQUEST['role'] ];
		/* translators: 1: Site name, 2: site URL, 3: role */
		return sprintf( __( 'Hi,
You\'ve been invited to join \'%1$s\' at
%2$s with the role of %3$s.
If you do not want to join this site please ignore
this email. This invitation will expire in a few days.

Please click the following link to activate your user account:
%%s' ), get_bloginfo( 'name' ), home_url(), wp_specialchars_decode( translate_user_role( $role['name'] ) ) );
	}
	add_filter( 'wpmu_signup_user_notification_email', 'admin_created_user_email' );
}

if ( isset($_REQUEST['action']) && 'adduser' == $_REQUEST['action'] ) {
	check_admin_referer( 'add-user', '_wpnonce_add-user' );

	$user_details = null;
	$user_email = wp_unslash( $_REQUEST['email'] );
	if ( false !== strpos( $user_email, '@' ) ) {
		$user_details = get_user_by( 'email', $user_email );
	} else {
		if ( is_super_admin() ) {
			$user_details = get_user_by( 'login', $user_email );
		} else {
			wp_redirect( add_query_arg( array('update' => 'enter_email'), 'user-new.php' ) );
			die();
		}
	}

	if ( !$user_details ) {
		wp_redirect( add_query_arg( array('update' => 'does_not_exist'), 'user-new.php' ) );
		die();
	}

	if ( ! current_user_can('promote_user', $user_details->ID) )
		wp_die( __( 'Cheatin&#8217; uh?' ), 403 );

	// Adding an existing user to this blog
	$new_user_email = $user_details->user_email;
	$redirect = 'user-new.php';
	$username = $user_details->user_login;
	$user_id = $user_details->ID;
	if ( ( $username != null && !is_super_admin( $user_id ) ) && ( array_key_exists($blog_id, get_blogs_of_user($user_id)) ) ) {
		$redirect = add_query_arg( array('update' => 'addexisting'), 'user-new.php' );
	} else {
		if ( isset( $_POST[ 'noconfirmation' ] ) && is_super_admin() ) {
			add_existing_user_to_blog( array( 'user_id' => $user_id, 'role' => $_REQUEST[ 'role' ] ) );
			$redirect = add_query_arg( array('update' => 'addnoconfirmation'), 'user-new.php' );
		} else {
			$newuser_key = substr( md5( $user_id ), 0, 5 );
			add_option( 'new_user_' . $newuser_key, array( 'user_id' => $user_id, 'email' => $user_details->user_email, 'role' => $_REQUEST[ 'role' ] ) );

			$roles = get_editable_roles();
			$role = $roles[ $_REQUEST['role'] ];
			/* translators: 1: Site name, 2: site URL, 3: role, 4: activation URL */
			$message = __( 'Hi,

You\'ve been invited to join \'%1$s\' at
%2$s with the role of %3$s.

Please click the following link to confirm the invite:
%4$s' );
			wp_mail( $new_user_email, sprintf( __( '[%s] Joining confirmation' ), wp_specialchars_decode( get_option( 'blogname' ) ) ), sprintf( $message, get_option( 'blogname' ), home_url(), wp_specialchars_decode( translate_user_role( $role['name'] ) ), home_url( "/newbloguser/$newuser_key/" ) ) );
			$redirect = add_query_arg( array('update' => 'add'), 'user-new.php' );
		}
	}
	wp_redirect( $redirect );
	die();
} elseif ( isset($_REQUEST['action']) && 'createuser' == $_REQUEST['action'] ) {
	check_admin_referer( 'create-user', '_wpnonce_create-user' );

	if ( ! current_user_can('create_users') )
		wp_die( __( 'Cheatin&#8217; uh?' ), 403 );

	if ( ! is_multisite() ) {
		$user_id = edit_user();

		if ( is_wp_error( $user_id ) ) {
			$add_user_errors = $user_id;
		} else {
			if ( current_user_can( 'list_users' ) )
				$redirect = 'users.php?update=add&id=' . $user_id;
			else
				$redirect = add_query_arg( 'update', 'add', 'user-new.php' );
			wp_redirect( $redirect );
			die();
		}
	} else {
		// Adding a new user to this site
		$new_user_email = wp_unslash( $_REQUEST['email'] );
		$user_details = wpmu_validate_user_signup( $_REQUEST['user_login'], $new_user_email );
		if ( is_wp_error( $user_details[ 'errors' ] ) && !empty( $user_details[ 'errors' ]->errors ) ) {
			$add_user_errors = $user_details[ 'errors' ];
		} else {
			/**
			 * Filter the user_login, also known as the username, before it is added to the site.
			 *
			 * @since 2.0.3
			 *
			 * @param string $user_login The sanitized username.
			 */
			$new_user_login = apply_filters( 'pre_user_login', sanitize_user( wp_unslash( $_REQUEST['user_login'] ), true ) );
			if ( isset( $_POST[ 'noconfirmation' ] ) && is_super_admin() ) {
				add_filter( 'wpmu_signup_user_notification', '__return_false' ); // Disable confirmation email
				add_filter( 'wpmu_welcome_user_notification', '__return_false' ); // Disable welcome email
			}
			wpmu_signup_user( $new_user_login, $new_user_email, array( 'add_to_blog' => $wpdb->blogid, 'new_role' => $_REQUEST['role'] ) );
			if ( isset( $_POST[ 'noconfirmation' ] ) && is_super_admin() ) {
				$key = $wpdb->get_var( $wpdb->prepare( "SELECT activation_key FROM {$wpdb->signups} WHERE user_login = %s AND user_email = %s", $new_user_login, $new_user_email ) );
				wpmu_activate_signup( $key );
				$redirect = add_query_arg( array('update' => 'addnoconfirmation'), 'user-new.php' );
			} else {
				$redirect = add_query_arg( array('update' => 'newuserconfirmation'), 'user-new.php' );
			}
			wp_redirect( $redirect );
			die();
		}
	}
}

$title = __('Add New User');
$parent_file = 'users.php';

$do_both = false;
if ( is_multisite() && current_user_can('promote_users') && current_user_can('create_users') )
	$do_both = true;

$help = '<p>' . __('To add a new user to your site, fill in the form on this screen and click the Add New User button at the bottom.') . '</p>';

if ( is_multisite() ) {
	$help .= '<p>' . __('Because this is a multisite installation, you may add accounts that already exist on the Network by specifying a username or email, and defining a role. For more options, such as specifying a password, you have to be a Network Administrator and use the hover link under an existing user&#8217;s name to Edit the user profile under Network Admin > All Users.') . '</p>' .
	'<p>' . __('New users will receive an email letting them know they&#8217;ve been added as a user for your site. This email will also contain their password. Check the box if you don&#8217;t want the user to receive a welcome email.') . '</p>';
} else {
	$help .= '<p>' . __('You must assign a password to the new user, which they can change after logging in. The username, however, cannot be changed.') . '</p>' .
	'<p>' . __('New users will receive an email letting them know they&#8217;ve been added as a user for your site. By default, this email will also contain their password. Uncheck the box if you don&#8217;t want the password to be included in the welcome email.') . '</p>';
}

$help .= '<p>' . __('Remember to click the Add New User button at the bottom of this screen when you are finished.') . '</p>';

get_current_screen()->add_help_tab( array(
	'id'      => 'overview',
	'title'   => __('Overview'),
	'content' => $help,
) );

get_current_screen()->add_help_tab( array(
'id'      => 'user-roles',
'title'   => __('User Roles'),
'content' => '<p>' . __('Here is a basic overview of the different user roles and the permissions associated with each one:') . '</p>' .
				'<ul>' .
				'<li>' . __('Subscribers can read comments/comment/receive newsletters, etc. but cannot create regular site content.') . '</li>' .
				'<li>' . __('Contributors can write and manage their posts but not publish posts or upload media files.') . '</li>' .
				'<li>' . __('Authors can publish and manage their own posts, and are able to upload files.') . '</li>' .
				'<li>' . __('Editors can publish posts, manage posts as well as manage other people&#8217;s posts, etc.') . '</li>' .
				'<li>' . __('Administrators have access to all the administration features.') . '</li>' .
				'</ul>'
) );

get_current_screen()->set_help_sidebar(
    '<p><strong>' . __('For more information:') . '</strong></p>' .
    '<p>' . __('<a href="https://codex.wordpress.org/Users_Add_New_Screen" target="_blank">Documentation on Adding New Users</a>') . '</p>' .
    '<p>' . __('<a href="https://wordpress.org/support/" target="_blank">Support Forums</a>') . '</p>'
);

wp_enqueue_script('wp-ajax-response');
wp_enqueue_script('user-profile');

/**
 * Filter whether to enable user auto-complete for non-super admins in Multisite.
 *
 * @since 3.4.0
 *
 * @param bool $enable Whether to enable auto-complete for non-super admins. Default false.
 */
if ( is_multisite() && current_user_can( 'promote_users' ) && ! wp_is_large_network( 'users' )
	&& ( is_super_admin() || apply_filters( 'autocomplete_users_for_site_admins', false ) )
) {
	wp_enqueue_script( 'user-suggest' );
}

require_once( ABSPATH . 'wp-admin/admin-header.php' );

if ( isset($_GET['update']) ) {
	$messages = array();
	if ( is_multisite() ) {
		switch ( $_GET['update'] ) {
			case "newuserconfirmation":
				$messages[] = __('Invitation email sent to new user. A confirmation link must be clicked before their account is created.');
				break;
			case "add":
				$messages[] = __('Invitation email sent to user. A confirmation link must be clicked for them to be added to your site.');
				break;
			case "addnoconfirmation":
				$messages[] = __('User has been added to your site.');
				break;
			case "addexisting":
				$messages[] = __('That user is already a member of this site.');
				break;
			case "does_not_exist":
				$messages[] = __('The requested user does not exist.');
				break;
			case "enter_email":
				$messages[] = __('Please enter a valid email address.');
				break;
		}
	} else {
		if ( 'add' == $_GET['update'] )
			$messages[] = __('User added.');
	}
}
?>
<div class="wrap">
<h2 id="add-new-user"> <?php
if ( current_user_can( 'create_users' ) ) {
	echo _x( 'Add New User', 'user' );
} elseif ( current_user_can( 'promote_users' ) ) {
	echo _x( 'Add Existing User', 'user' );
} ?>
</h2>

<?php if ( isset($errors) && is_wp_error( $errors ) ) : ?>
	<div class="error">
		<ul>
		<?php
			foreach ( $errors->get_error_messages() as $err )
				echo "<li>$err</li>\n";
		?>
		</ul>
	</div>
<?php endif;

if ( ! empty( $messages ) ) {
	foreach ( $messages as $msg )
		echo '<div id="message" class="updated notice is-dismissible"><p>' . $msg . '</p></div>';
} ?>

<?php if ( isset($add_user_errors) && is_wp_error( $add_user_errors ) ) : ?>
	<div class="error">
		<?php
			foreach ( $add_user_errors->get_error_messages() as $message )
				echo "<p>$message</p>";
		?>
	</div>
<?php endif; ?>
<div id="ajax-response"></div>

<?php
if ( is_multisite() ) {
	if ( $do_both )
		echo '<h3 id="add-existing-user">' . __('Add Existing User') . '</h3>';
	if ( !is_super_admin() ) {
		echo '<p>' . __( 'Enter the email address of an existing user on this network to invite them to this site. That person will be sent an email asking them to confirm the invite.' ) . '</p>';
		$label = __('E-mail');
		$type  = 'email';
	} else {
		echo '<p>' . __( 'Enter the email address or username of an existing user on this network to invite them to this site. That person will be sent an email asking them to confirm the invite.' ) . '</p>';
		$label = __('E-mail or Username');
		$type  = 'text';
	}
?>
<form method="post" name="adduser" id="adduser" class="validate" novalidate="novalidate"<?php
	/**
	 * Fires inside the adduser form tag.
	 *
	 * @since 3.0.0
	 */
	do_action( 'user_new_form_tag' );
?>>
<input name="action" type="hidden" value="adduser" />
<?php wp_nonce_field( 'add-user', '_wpnonce_add-user' ) ?>

<table class="form-table">
	<tr class="form-field form-required">
		<th scope="row"><label for="adduser-email"><?php echo $label; ?></label></th>
		<td><input name="email" type="<?php echo $type; ?>" id="adduser-email" class="wp-suggest-user" value="" /></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="adduser-role"><?php _e('Role'); ?></label></th>
		<td><select name="role" id="adduser-role">
			<?php wp_dropdown_roles( get_option('default_role') ); ?>
			</select>
		</td>
	</tr>
<?php if ( is_super_admin() ) { ?>
	<tr>
		<th scope="row"><label for="adduser-noconfirmation"><?php _e('Skip Confirmation Email') ?></label></th>
		<td><label for="adduser-noconfirmation"><input type="checkbox" name="noconfirmation" id="adduser-noconfirmation" value="1" /> <?php _e( 'Add the user without sending an email that requires their confirmation.' ); ?></label></td>
	</tr>
<?php } ?>
</table>
<?php
/**
 * Fires at the end of the new user form.
 *
 * Passes a contextual string to make both types of new user forms
 * uniquely targetable. Contexts are 'add-existing-user' (Multisite),
 * and 'add-new-user' (single site and network admin).
 *
 * @since 3.7.0
 *
 * @param string $type A contextual string specifying which type of new user form the hook follows.
 */
do_action( 'user_new_form', 'add-existing-user' );
?>
<?php submit_button( __( 'Add Existing User' ), 'primary', 'adduser', true, array( 'id' => 'addusersub' ) ); ?>
</form>
<?php
} // is_multisite()

if ( current_user_can( 'create_users') ) {
	if ( $do_both )
		echo '<h3 id="create-new-user">' . __( 'Add New User' ) . '</h3>';
?>
<p><?php _e('Create a brand new user and add them to this site.'); ?></p>
<form method="post" name="createuser" id="createuser" class="validate" novalidate="novalidate"<?php
	/** This action is documented in wp-admin/user-new.php */
	do_action( 'user_new_form_tag' );
?>>
<input name="action" type="hidden" value="createuser" />
<?php wp_nonce_field( 'create-user', '_wpnonce_create-user' ); ?>
<?php
// Load up the passed data, else set to a default.
$creating = isset( $_POST['createuser'] );

$new_user_login = $creating && isset( $_POST['user_login'] ) ? wp_unslash( $_POST['user_login'] ) : '';
$new_user_firstname = $creating && isset( $_POST['first_name'] ) ? wp_unslash( $_POST['first_name'] ) : '';
$new_user_lastname = $creating && isset( $_POST['last_name'] ) ? wp_unslash( $_POST['last_name'] ) : '';
$new_user_email = $creating && isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '';
$new_user_uri = $creating && isset( $_POST['url'] ) ? wp_unslash( $_POST['url'] ) : '';
$new_user_role = $creating && isset( $_POST['role'] ) ? wp_unslash( $_POST['role'] ) : '';
$new_user_send_password = $creating && isset( $_POST['send_password'] ) ? wp_unslash( $_POST['send_password'] ) : '';
$new_user_ignore_pass = $creating && isset( $_POST['noconfirmation'] ) ? wp_unslash( $_POST['noconfirmation'] ) : '';

?>
<table class="form-table">
	<tr class="form-field form-required">
		<th scope="row"><label for="user_login"><?php _e('Username'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
		<td><input name="user_login" type="text" id="user_login" value="<?php echo esc_attr($new_user_login); ?>" aria-required="true" /></td>
	</tr>
	<tr class="form-field form-required">
		<th scope="row"><label for="email"><?php _e('E-mail'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
		<td><input name="email" type="email" id="email" value="<?php echo esc_attr( $new_user_email ); ?>" /></td>
	</tr>
<?php if ( !is_multisite() ) { ?>
	<tr class="form-field">
		<th scope="row"><label for="first_name"><?php _e('First Name') ?> </label></th>
		<td><input name="first_name" type="text" id="first_name" value="<?php echo esc_attr($new_user_firstname); ?>" /></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="last_name"><?php _e('Last Name') ?> </label></th>
		<td><input name="last_name" type="text" id="last_name" value="<?php echo esc_attr($new_user_lastname); ?>" /></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="url"><?php _e('Website') ?></label></th>
		<td><input name="url" type="url" id="url" class="code" value="<?php echo esc_attr( $new_user_uri ); ?>" /></td>
	</tr>
<?php
/**
 * Filter the display of the password fields.
 *
 * @since 1.5.1
 *
 * @param bool $show Whether to show the password fields. Default true.
 */
if ( apply_filters( 'show_password_fields', true ) ) : ?>
	<tr class="form-field form-required">
		<th scope="row"><label for="pass1"><?php _e('Password'); ?> <span class="description"><?php /* translators: password input field */_e('(required)'); ?></span></label></th>
		<td>
			<input class="hidden" value=" " /><!-- #24364 workaround -->
			<input name="pass1" type="password" id="pass1" autocomplete="off" />
		</td>
	</tr>
	<tr class="form-field form-required">
		<th scope="row"><label for="pass2"><?php _e('Repeat Password'); ?> <span class="description"><?php /* translators: password input field */_e('(required)'); ?></span></label></th>
		<td>
		<input name="pass2" type="password" id="pass2" autocomplete="off" />
		<br />
		<div id="pass-strength-result"><?php _e('Strength indicator'); ?></div>
		<p class="description indicator-hint"><?php echo wp_get_password_hint(); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php _e('Send Password?') ?></th>
		<td><label for="send_password"><input type="checkbox" name="send_password" id="send_password" value="1" <?php checked( $new_user_send_password ); ?> /> <?php _e('Send this password to the new user by email.'); ?></label></td>
	</tr>
<?php endif; ?>
<?php } // !is_multisite ?>
	<tr class="form-field">
		<th scope="row"><label for="role"><?php _e('Role'); ?></label></th>
		<td><select name="role" id="role">
			<?php
			if ( !$new_user_role )
				$new_user_role = !empty($current_role) ? $current_role : get_option('default_role');
			wp_dropdown_roles($new_user_role);
			?>
			</select>
		</td>
	</tr>
	<?php if ( is_multisite() && is_super_admin() ) { ?>
	<tr>
		<th scope="row"><label for="noconfirmation"><?php _e('Skip Confirmation Email') ?></label></th>
		<td><label for="noconfirmation"><input type="checkbox" name="noconfirmation" id="noconfirmation" value="1" <?php checked( $new_user_ignore_pass ); ?> /> <?php _e( 'Add the user without sending an email that requires their confirmation.' ); ?></label></td>
	</tr>
	<?php } ?>
</table>

<?php
/** This action is documented in wp-admin/user-new.php */
do_action( 'user_new_form', 'add-new-user' );
?>

<?php submit_button( __( 'Add New User' ), 'primary', 'createuser', true, array( 'id' => 'createusersub' ) ); ?>

</form>
<?php } // current_user_can('create_users') ?>
</div>
<?php
include( ABSPATH . 'wp-admin/admin-footer.php' );



user-profile.min.js

!function (a) {
    function b() {
        var b, c = a("#pass1").val(), d = a("#pass2").val();
        if (a("#pass-strength-result").removeClass("short bad good strong"), !c)
            return void a("#pass-strength-result").html(pwsL10n.empty);
        switch (b = wp.passwordStrength.meter(c, wp.passwordStrength.userInputBlacklist(), d)) {
            case 2:
                a("#pass-strength-result").addClass("bad").html(pwsL10n.bad);
                break;
            case 3:
                a("#pass-strength-result").addClass("good").html(pwsL10n.good);
                break;
            case 4:
                a("#pass-strength-result").addClass("strong").html(pwsL10n.strong);
                break;
            case 5:
                a("#pass-strength-result").addClass("short").html(pwsL10n.mismatch);
                break;
            default:
                a("#pass-strength-result").addClass("short").html(pwsL10n["short"])
            }
    }
    a(document).ready(function () {
        var c, d, e, f, g = a("#display_name");
        a("#pass1").val("").on("input propertychange", b), a("#pass2").val("").on("input propertychange", b), a("#pass-strength-result").show(), a(".color-palette").click(function () {
            a(this).siblings('input[name="admin_color"]').prop("checked", !0)
        }), g.length && a("#first_name, #last_name, #nickname").bind("blur.user_profile", function () {
            var b = [], c = {display_nickname: a("#nickname").val() || "", display_username: a("#user_login").val() || "", display_firstname: a("#first_name").val() || "", display_lastname: a("#last_name").val() || ""};
            c.display_firstname && c.display_lastname && (c.display_firstlast = c.display_firstname + " " + c.display_lastname, c.display_lastfirst = c.display_lastname + " " + c.display_firstname), a.each(a("option", g), function (a, c) {
                b.push(c.value)
            }), a.each(c, function (d, e) {
                if (e) {
                    var f = e.replace(/<\/?[a-z][^>]*>/gi, "");
                    c[d].length && -1 === a.inArray(f, b) && (b.push(f), a("<option />", {text: f}).appendTo(g))
                }
            })
        }), c = a("#color-picker"), d = a("#colors-css"), e = a("input#user_id").val(), f = a('input[name="checkuser_id"]').val(), c.on("click.colorpicker", ".color-option", function () {
            var b, c = a(this);
            if (!c.hasClass("selected") && (c.siblings(".selected").removeClass("selected"), c.addClass("selected").find('input[type="radio"]').prop("checked", !0), e === f)) {
                if (0 === d.length && (d = a('<link rel="stylesheet" />').appendTo("head")), d.attr("href", c.children(".css_url").val()), "undefined" != typeof wp && wp.svgPainter) {
                    try {
                        b = a.parseJSON(c.children(".icon_colors").val())
                    } catch (g) {
                    }
                    b && (wp.svgPainter.setColors(b), wp.svgPainter.paint())
                }
                a.post(ajaxurl, {action: "save-user-color-scheme", color_scheme: c.children('input[name="admin_color"]').val(), nonce: a("#color-nonce").val()}).done(function (b) {
                    b.success && a("body").removeClass(b.data.previousScheme).addClass(b.data.currentScheme)
                })
            }
        })
    }), a("#destroy-sessions").on("click", function (b) {
        var c = a(this);
        wp.ajax.post("destroy-sessions", {nonce: a("#_wpnonce").val(), user_id: a("#user_id").val()}).done(function (a) {
            c.prop("disabled", !0), c.siblings(".notice").remove(), c.before('<div class="notice notice-success inline"><p>' + a.message + "</p></div>")
        }).fail(function (a) {
            c.siblings(".notice").remove(), c.before('<div class="notice notice-error inline"><p>' + a.message + "</p></div>")
        }), b.preventDefault()
    })
}(jQuery);

user-profile.js

/* global ajaxurl, pwsL10n */
(function($){

	function check_pass_strength() {
		var pass1 = $('#pass1').val(), pass2 = $('#pass2').val(), strength;

		$('#pass-strength-result').removeClass('short bad good strong');
		if ( ! pass1 ) {
			$('#pass-strength-result').html( pwsL10n.empty );
			return;
		}

		strength = wp.passwordStrength.meter( pass1, wp.passwordStrength.userInputBlacklist(), pass2 );

		switch ( strength ) {
			case 2:
				$('#pass-strength-result').addClass('bad').html( pwsL10n.bad );
				break;
			case 3:
				$('#pass-strength-result').addClass('good').html( pwsL10n.good );
				break;
			case 4:
				$('#pass-strength-result').addClass('strong').html( pwsL10n.strong );
				break;
			case 5:
				$('#pass-strength-result').addClass('short').html( pwsL10n.mismatch );
				break;
			default:
				$('#pass-strength-result').addClass('short').html( pwsL10n['short'] );
		}
	}

	$(document).ready( function() {
		var $colorpicker, $stylesheet, user_id, current_user_id,
			select = $( '#display_name' );

		$('#pass1').val('').on( 'input propertychange', check_pass_strength );
		$('#pass2').val('').on( 'input propertychange', check_pass_strength );
		$('#pass-strength-result').show();
		$('.color-palette').click( function() {
			$(this).siblings('input[name="admin_color"]').prop('checked', true);
		});

		if ( select.length ) {
			$('#first_name, #last_name, #nickname').bind( 'blur.user_profile', function() {
				var dub = [],
					inputs = {
						display_nickname  : $('#nickname').val() || '',
						display_username  : $('#user_login').val() || '',
						display_firstname : $('#first_name').val() || '',
						display_lastname  : $('#last_name').val() || ''
					};

				if ( inputs.display_firstname && inputs.display_lastname ) {
					inputs.display_firstlast = inputs.display_firstname + ' ' + inputs.display_lastname;
					inputs.display_lastfirst = inputs.display_lastname + ' ' + inputs.display_firstname;
				}

				$.each( $('option', select), function( i, el ){
					dub.push( el.value );
				});

				$.each(inputs, function( id, value ) {
					if ( ! value ) {
						return;
					}

					var val = value.replace(/<\/?[a-z][^>]*>/gi, '');

					if ( inputs[id].length && $.inArray( val, dub ) === -1 ) {
						dub.push(val);
						$('<option />', {
							'text': val
						}).appendTo( select );
					}
				});
			});
		}

		$colorpicker = $( '#color-picker' );
		$stylesheet = $( '#colors-css' );
		user_id = $( 'input#user_id' ).val();
		current_user_id = $( 'input[name="checkuser_id"]' ).val();

		$colorpicker.on( 'click.colorpicker', '.color-option', function() {
			var colors,
				$this = $(this);

			if ( $this.hasClass( 'selected' ) ) {
				return;
			}

			$this.siblings( '.selected' ).removeClass( 'selected' );
			$this.addClass( 'selected' ).find( 'input[type="radio"]' ).prop( 'checked', true );

			// Set color scheme
			if ( user_id === current_user_id ) {
				// Load the colors stylesheet.
				// The default color scheme won't have one, so we'll need to create an element.
				if ( 0 === $stylesheet.length ) {
					$stylesheet = $( '<link rel="stylesheet" />' ).appendTo( 'head' );
				}
				$stylesheet.attr( 'href', $this.children( '.css_url' ).val() );

				// repaint icons
				if ( typeof wp !== 'undefined' && wp.svgPainter ) {
					try {
						colors = $.parseJSON( $this.children( '.icon_colors' ).val() );
					} catch ( error ) {}

					if ( colors ) {
						wp.svgPainter.setColors( colors );
						wp.svgPainter.paint();
					}
				}

				// update user option
				$.post( ajaxurl, {
					action:       'save-user-color-scheme',
					color_scheme: $this.children( 'input[name="admin_color"]' ).val(),
					nonce:        $('#color-nonce').val()
				}).done( function( response ) {
					if ( response.success ) {
						$( 'body' ).removeClass( response.data.previousScheme ).addClass( response.data.currentScheme );
					}
				});
			}
		});
	});

	$( '#destroy-sessions' ).on( 'click', function( e ) {
		var $this = $(this);

		wp.ajax.post( 'destroy-sessions', {
			nonce: $( '#_wpnonce' ).val(),
			user_id: $( '#user_id' ).val()
		}).done( function( response ) {
			$this.prop( 'disabled', true );
			$this.siblings( '.notice' ).remove();
			$this.before( '<div class="notice notice-success inline"><p>' + response.message + '</p></div>' );
		}).fail( function( response ) {
			$this.siblings( '.notice' ).remove();
			$this.before( '<div class="notice notice-error inline"><p>' + response.message + '</p></div>' );
		});

		e.preventDefault();
	});

})(jQuery);



<?php
include( ABSPATH . 'wp-admin/admin-footer.php');
