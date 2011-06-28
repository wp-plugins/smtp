<?php
/*
Plugin Name: SMTP
Version: 1.0
Plugin URI: http://hel.io/wordpress/smtp
Description: Allows you to configure and use a SMTP server (such as Gmail) for sending emails.
Author: Sorin Iclanzan
Author URI: http://hel.io/
*/

// This is run when you activate the plugin, adding the default options to the database
register_activation_hook(__FILE__,'smtp_activation');
function smtp_activation() {
	
	// Default options
	$smtp_options = array (
        'host' => 'http://localhost',
        'port' => '25',
        'smtp_secure' => '',
        'username' => '',
        'password' => ''
    );
	
	// Add options
	add_option('smtp_options',$smtp_options);
	
}

// Add options page in the admin menu
add_action('admin_menu','smtp_menu');
function smtp_menu() {
	add_options_page('SMTP Settings', 'SMTP', 'manage_options', 'smtp', 'smtp_options_page');
}

// Add "Settings" link to the plugins page
add_filter( 'plugin_action_links', 'smtp_action_links',10,2);
function smtp_action_links( $links, $file ) {
	if ( $file != plugin_basename( __FILE__ ))
		return $links;

	$settings_link = '<a href="options-general.php?page=smtp">Settings</a>';

	array_unshift( $links, $settings_link );

	return $links;
}

// Display options page
function smtp_options_page() {
    
    // Send test email if requested
    if (isset($_POST['smtp_test']) && $_POST['smtp_test'] == 'Send' && isset($_POST['to']) && is_email($_POST['to'])) {
            
            $to = $_POST['to'];
            $subject = 'SMTP Test';
            $message = 'If you received this email it means you have configured SMTP correctly on your Wordpress website.';
    
            // Send the test mail
            $result = wp_mail($to, $subject, $message);
            
            // Notify user of the result
            if ($result) {
                ?>
                <div id="message" class="updated fade">
                <p><strong>Test Email Sent</strong></p>
                <p>The test email was sent successfully!</p>
                </div>
                <?php
            }
            else {
                ?>
                <div id="message" class="error">
                <p><strong>Send Error</strong></p>
                <p>There was an error while trying to send the test email. Please check the connection details.</p>
                </div>
                <?php
            }    
	}
	
    ?>
    <div class="wrap">
        <h2>SMTP Settings</h2>
        
        <form action="options.php" method="post">
            <?php settings_fields('smtp_options'); ?>
            <?php do_settings_sections('smtp'); ?>
            <p class="submit">
                <input name="submit" type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
            </p>
        </form>
        
        <h3>Send a Test Email</h3>
        <p>Enter an email address below to send a test message.</p>
        <form action="options-general.php?page=smtp" method="post">
            <table class="optiontable form-table">
                <tr valign="top">
                    <th scope="row"><label for="to">To:</label></th>
                    <td>
                        <input name="to" type="text" id="to" value="" class="regular-text" />
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="smtp_test" id="smtp_test" class="button-primary" value="Send" />
            </p>
        </form>
        
    </div>
    <?php
}

// Register settings, add sections and fields
add_action('admin_init', 'smtp_admin_init');
function smtp_admin_init(){
    register_setting( 'smtp_options', 'smtp_options', 'smtp_options_validate' );
    add_settings_section('smtp_main', 'Settings', 'smtp_section', 'smtp');
    add_settings_field('host', 'Host', 'smtp_host', 'smtp', 'smtp_main');
    add_settings_field('encryption', 'Encryption', 'smtp_encryption', 'smtp', 'smtp_main');
    add_settings_field('username', 'Username', 'smtp_username', 'smtp', 'smtp_main');
    add_settings_field('password', 'Password', 'smtp_password', 'smtp', 'smtp_main');
}

function smtp_section() {
    echo '<p>Please enter your SMTP connection details.</p>';
}

function smtp_host() {
    $options = get_option('smtp_options');
    echo "
        <input id='host' name='smtp_options[host]' type='text' class='regular-text' value='{$options['host']}' />
        <label for='port'>Port</label>
        <input id='port' name='smtp_options[port]' type='text' class='small-text' value='{$options['port']}' />
    ";
}

function smtp_encryption() {
    $options = get_option('smtp_options');
    echo "
        <label><input name='smtp_options[smtp_secure]' type='radio' class='tog' value='' ". checked('', $options['smtp_secure'], false) . " /> <span>None</span></label><br/>
        <label><input name='smtp_options[smtp_secure]' type='radio' class='tog' value='ssl' " . checked('ssl', $options['smtp_secure'], false) . " /> <span>SSL</span></label><br/>
        <label><input name='smtp_options[smtp_secure]' type='radio' class='tog' value='tls' " . checked('tls', $options['smtp_secure'], false) . " /> <span>TLS</span></label>
    ";
}

function smtp_username() {
    $options = get_option('smtp_options');
    echo "<input id='username' name='smtp_options[username]' type='text' class='regular-text' value='{$options['username']}' />";
}
function smtp_password() {
    $options = get_option('smtp_options');
    echo "<input id='password' name='smtp_options[password]' type='text' class='regular-text' value='{$options['password']}' />";
}

function smtp_options_validate($input) {
    $smtp_options = get_option('smtp_options');
    
    $input['host'] = stripslashes(wp_filter_kses(addslashes(strip_tags($input['host']))));
    if ($input['host'] == '')
        $input['host'] = $smtp_options['host'];
    
    $input['port'] = absint($input['port']);
    if ($input['port'] == 0 || $input['port'] == 1)
        $input['port'] = $smtp_options['port'];
        
    if ($input['smtp_secure'] != '' && $input['smtp_secure'] != 'ssl' && $input['smtp_secure'] != 'tls')
        $input['smtp_secure'] = $smtp_options['smtp_secure'];
        
    $input['username'] = stripslashes(wp_filter_kses(addslashes(strip_tags($input['username']))));
    if ($input['username'] == '')
        $input['username'] = $smtp_options['username'];
        
    $input['password'] = stripslashes(wp_filter_kses(addslashes(strip_tags($input['password']))));
    if ($input['password'] == '')
        $input['password'] = $smtp_options['password'];
    
    return $input;
}

// This makes the magic happen
add_action('phpmailer_init','smtp_phpmailer_init');
function smtp_phpmailer_init($phpmailer) {
		
    $smtp_options = get_option('smtp_options');
    $admin_info = get_userdata(1);
    
    // Set Mailer value
    $phpmailer->Mailer = 'smtp';
    
    // Set From value
    $phpmailer->From = $admin_info->user_email;
    
    // Set FromName value
    $phpmailer->FromName = $admin_info->display_name;
    
    // Set SMTPSecure value
    $phpmailer->SMTPSecure = $smtp_options['smtp_secure'];
        
    // Set Host value
    $phpmailer->Host = $smtp_options['host'];
    
    // Set Port value
    $phpmailer->Port = $smtp_options['port'];
        
    // If usrname option is not blank we have to use authentication
    if ($smtp_options['username'] != '') {
        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = $smtp_options['username'];
        $phpmailer->Password = $smtp_options['password'];
    }
}