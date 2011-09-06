<?php
/*
Plugin Name: Feedback Screen with Annotation
Plugin URI: NONE
Description: Feedback Screen with Annotation enables visitor to send feedback about your website with some annotation and the screenshot.
Version: 1.0.0
Author: me@sulaeman.com
Author URI: http://www.sulaeman.com
License: A "Slug" license name e.g. GPL2
*/

// Register the installation and uninstallation function
register_activation_hook(__FILE__, 'fi_fdbck_install');
register_deactivation_hook(__FILE__, 'fi_fdbck_uninstall');

add_action('init', 'fi_fdbck_init');
add_action('wp_head', 'fi_fdbck_wp_head');
add_action('wp_footer', 'fi_fdbck_wp_footer');

function fi_fdbck_init()
{
  if ( ! is_admin() )
  {
    $feedbackNone = (isset($_POST['fi_fdbck_nonce'])) ? $_POST['fi_fdbck_nonce'] : '';
    
    if(wp_verify_nonce($feedbackNone, 'fi-fdbck-send-feedback-now'))
    {
      $error = FALSE;
      $errorDesc = __('Problem sending feedback, try again.', 'fi_fdbck');
      
      $uploadDir = wp_upload_dir();
      $name = (isset($_POST['fdbck_name'])) ? $_POST['fdbck_name'] : '';
      $email = (isset($_POST['fdbck_email'])) ? $_POST['fdbck_email'] : '';
      $feedback = (isset($_POST['fdbck_feedback'])) ? $_POST['fdbck_feedback'] : '';
      $screen = (isset($_POST['fdbck_screen'])) ? $_POST['fdbck_screen'] : '';
      
      if (empty($name))
      {
        $error = TRUE;
      }
      
      if (empty($email))
      {
        $error = TRUE;
      }
      
      if (empty($feedback))
      {
        $error = TRUE;
      }
      
      if (empty($screen))
      {
        $error = TRUE;
      }
      
      if (empty($uploadDir))
      {
        $error = TRUE;
      }
      
      if (!$error)
      {
        $dir = $uploadDir['basedir'].'/feedback';
        
        fi_fdbck_mkdir($dir);
        
        if (!is_writable($dir))
        {
          $error = TRUE;
          $errorDesc = __('Problem creating screenshot folder, try again.', 'fi_fdbck');
        }
      }
      
      if (!$error)
      {
        $file = time().'.jpg';
        $fileUrl = $uploadDir['baseurl'].'/feedback/'.$file;
        $filePath = $dir.'/'.$file;
        
        $image = imagecreatefromstring(base64_decode(str_replace('data:image/jpeg;base64,', '', $screen)));
        imagejpeg($image, $filePath);
        imagedestroy($image);
        
        if (!is_file($filePath))
        {
          $error = TRUE;
          $errorDesc = __('Problem creating screenshot, try again.', 'fi_fdbck');
        }
      }
      
      if (!$error)
      {
        // Set correct file permissions
        $stat = stat( dirname( $file ));
        $perms = $stat['mode'] & 0000777;
        @chmod( $filePath, $perms );
        
        // Send the feedback
        require_once('class.phpmailer.php');
        
        $mail = new PHPMailer();
        $mail->IsMail();
    		
    		$mail->AddAttachment($filePath);
    		$mail->SetFrom($email, $name);
    		$mail->Subject = 'Feedback from '.$name;
    		$mail->Body = $feedback;
    		$mail->AddAddress(get_bloginfo('admin_email'));
    		
    		if (!$mail->Send())
    		{
    		  @unlink($file);
    		  $error = TRUE;
    		}
    		
    		$mail->ClearAddresses();
    		$mail->SetFrom(get_bloginfo('admin_email'), get_bloginfo('name'));
    		$mail->Subject = 'We have received your feedback';
    		$mail->Body = $feedback;
    		$mail->AddAddress($email, $name);
    		
    		$mail->Send();
      }
      
      if ($error)
      {
        echo '<script type="text/javascript">';
        echo 'parent.fi_fdbck_error("'.__('Problem sending feedback, try again.', 'fi_fdbck').'");';
        echo '</script>';
      }
      else
      {
        echo '<script type="text/javascript">';
        echo 'parent.fi_fdbck_success("'.__('Successfully sent.', 'fi_fdbck').'");';
        echo '</script>';
      }
      
      exit();
    }
    
    wp_enqueue_style( 'fi-fdbck', plugins_url( 'resources/static/styles/style.css' , __FILE__ ) );
    
    wp_enqueue_script( 'html2canvas', plugins_url( 'resources/static/scripts/html2canvas.min.js' , __FILE__ ), array(), '0.27' );
    wp_enqueue_script( 'fi-fdbck', plugins_url( 'resources/static/scripts/fi.feedback.min.js' , __FILE__ ), array(
      'jquery', 'html2canvas'
    ), '1.0');
  }
}

function fi_fdbck_wp_head()
{
  echo '<script type="text/javascript">';
  echo 'var BASE_URL = "'.esc_url( home_url( '/' ) ).'";';
  echo 'var GENERATING_STATUS = "'.__('Generating annotation...', 'fi_fdbck').'";';
  echo 'jQuery(document).ready(function(){';
  echo 'jQuery("body").fiFeedback();';
  echo '});';
  echo '</script>';
}

function fi_fdbck_wp_footer()
{
  ?>
  <!-- Feedback -->
  <div id="fi-fdbck-overlay">&nbsp;</div>
  <div id="fi-fdbck-container" class="fi-fdbck-ignore-annotate fi-fdbck-ignore-render" style="display:none;">
    <div id="fi-fdbck-content" class="fi-fdbck-ignore-annotate fi-fdbck-ignore-render">
      <div class="fi-fdbck-control fi-fdbck-ignore-annotate fi-fdbck-ignore-render fi-fdbck-clearfix">
        <a id="fi-fdbck-control" class="fi-fdbck-ignore-annotate fi-fdbck-ignore-render" href="#"><?php _e('Feedback', 'fi_fdbck'); ?></a>
      </div>
      <div class="fi-fdbck-textarea fi-fdbck-ignore-annotate fi-fdbck-ignore-render">
        <div class="fi-fdbck-textarea-background fi-fdbck-ignore-annotate fi-fdbck-ignore-render">
          <form id="fi-fdbck-frm" class="fi-fdbck-ignore-annotate fi-fdbck-ignore-render" name="fi-fdbck-frm" action="" method="POST" target="fi-fdbck-ifrm">
            <div class="fi-fdbck-textarea-wrapper fi-fdbck-ignore-annotate fi-fdbck-ignore-render"> 
              <input type="text" name="fdbck_name" class="fi-fdbck-text fi-fdbck-ignore-annotate fi-fdbck-ignore-render" value="" placeholder="<?php _e('Your name', 'fi_fdbck'); ?>" />
              <input type="text" name="fdbck_email" class="fi-fdbck-text fi-fdbck-ignore-annotate fi-fdbck-ignore-render" value="" placeholder="<?php _e('Your email', 'fi_fdbck'); ?>" />
              <textarea name="fdbck_feedback" class="fi-fdbck-text fi-fdbck-ignore-annotate fi-fdbck-ignore-render" placeholder="<?php _e('Tell use if you experience any problems, comments, or suggestions here', 'fi_fdbck'); ?>" rows="10"></textarea>
              <textarea id="fi-fdbck-screen" class="fi-fdbck-ignore-annotate fi-fdbck-ignore-render" name="fdbck_screen" style="display:none;"></textarea>
              <?php wp_nonce_field( 'fi-fdbck-send-feedback-now', 'fi_fdbck_nonce' ); ?>
            </div>
            <div class="fi-fdbck-post-tools fi-fdbck-clearfix fi-fdbck-ignore-annotate fi-fdbck-ignore-render">
              <button type="button" id="fi-fdbck-button" class="fi-fdbck-button fi-fdbck-ignore-annotate fi-fdbck-ignore-render"><?php _e('Submit', 'fi_fdbck'); ?></button>
              <button type="button" id="fi-fdbck-blackout-button" class="fi-fdbck-button fi-fdbck-ignore-annotate fi-fdbck-ignore-annotate fi-fdbck-ignore-render"><?php _e('Blackout', 'fi_fdbck'); ?></button>
              <button type="button" id="fi-fdbck-hightlight-button" class="fi-fdbck-button fi-fdbck-button-selected fi-fdbck-ignore-annotate fi-fdbck-ignore-render"><?php _e('Highlight', 'fi_fdbck'); ?></button>
              <button type="button" id="fi-fdbck-minimize-button" class="fi-fdbck-button fi-fdbck-ignore-annotate fi-fdbck-ignore-render"><?php _e('Minimize', 'fi_fdbck'); ?></button>
              <div id="fi-fdbck-loading">
                <img src="<?php echo plugins_url( 'resources/static/images/loading.gif' , __FILE__ ); ?>" />
                <label><?php _e('&nbsp;&nbsp;sending your feedback...', 'fi_fdbck'); ?></label>
              </div>
              <label id="fi-fdbck-status"></label>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <!-- end of feedback -->
  <?php
}

function fi_fdbck_install()
{}

function fi_fdbck_uninstall()
{}

function fi_fdbck_mkdir( $target )
{
  // from php.net/mkdir user contributed notes
  $target = str_replace( '//', '/', $target );
  if ( file_exists( $target ) )
  {
    return @is_dir( $target );
  }

  // Attempting to create the directory may clutter up our display.
  if ( @mkdir( $target ) )
  {
    $stat = @stat( dirname( $target ) );
    $dir_perms = $stat['mode'] & 0007777;    // Get the permission bits.
    @chmod( $target, $dir_perms );

    return TRUE;
  }
  elseif ( is_dir( dirname( $target ) ) )
  {
    return FALSE;
  }

  // If the above failed, attempt to create the parent node, then try again.
  if ( ( $target != '/' ) && ( fi_fdbck_mkdir( dirname( $target ) ) ) )
  {
    return fi_fdbck_mkdir( $target );
  }

  return FALSE;
}