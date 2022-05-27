<?php
class class_ajax{
    public function __construct(){
        $this->init();
    }

    public function init(){
        
        add_action("wp_ajax_update_student_country_cih_ci", array($this,"update_student_country_cih_ci_fn"));
        add_action("wp_ajax_nopriv_update_student_country_cih_ci", array($this,"update_student_country_cih_ci_fn"));

        add_action("wp_ajax_get_countries_list", array($this,"get_countries_list_fn"));
        add_action("wp_ajax_nopriv_get_countries_list", array($this,"get_countries_list_fn"));
		
		add_action("wp_ajax_update_ci_user_data", array($this, "update_ci_user_data_fn"));
        add_action("wp_ajax_nopriv_update_ci_user_data", array($this, "update_ci_user_data_fn"));
		
		add_action("wp_ajax_get_ci_user_edit_window", array($this, "get_ci_user_edit_window_fn"));
        add_action("wp_ajax_nopriv_get_ci_user_edit_window", array($this, "get_ci_user_edit_window_fn"));
		
		add_action("wp_ajax_update_teachers_data", array($this, "update_teachers_data_fn"));
        add_action("wp_ajax_nopriv_update_teachers_data", array($this, "update_teachers_data_fn"));
		
		add_action("wp_ajax_get_teachers_edit_window", array($this, "get_teachers_edit_window_fn"));
        add_action("wp_ajax_nopriv_get_teachers_edit_window", array($this, "get_teachers_edit_window_fn"));

        add_action("wp_ajax_pull_course_from_course_bank", array($this,"pull_course_from_course_bank"));
        add_action("wp_ajax_nopriv_pull_course_from_course_bank", array($this,"pull_course_from_course_bank"));

        add_action("wp_ajax_remove_course_from_course_bank", array($this,"remove_course_from_course_bank"));
        add_action("wp_ajax_nopriv_remove_course_from_course_bank", array($this,"remove_course_from_course_bank"));
        
        add_action("wp_ajax_addcourse_to_course_bank", array($this,"addcourse_to_course_bank"));
        add_action("wp_ajax_nopriv_addcourse_to_course_bank", array($this,"addcourse_to_course_bank"));
        
        add_action("wp_ajax_approve_student", array($this,"approve_student_fn"));
        add_action("wp_ajax_nopriv_approve_student", array($this,"approve_student_fn"));
    }

    public function approve_student_fn(){
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ajaxnonce' ) ) {
            die ( 'Request is not from valid resource' );
        }
        $studentid = filter_input(INPUT_POST, 'studentid');
        update_user_meta($studentid,'ur_user_status',1);
    }

    public function update_student_country_cih_ci_fn(){
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ajaxnonce' ) ) {
            die ( 'Request is not from valid resource' );
        }
        $userid = filter_input(INPUT_POST,'student');
        $country = filter_input(INPUT_POST,'country');
        $cih = filter_input(INPUT_POST,'cih');
        $ci = filter_input(INPUT_POST,'ci');
        update_user_meta($userid,'user_registration_country',$country);
        update_user_meta($userid,'user_registration_cih',$cih);
        update_user_meta($userid,'user_registration_ci',$ci);
        $country = get_term($country)->name;
        $cih = get_the_title($cih) ? get_the_title($cih) : '';
        $ci = get_the_title($ci) ? get_the_title($ci) : '';
        echo json_encode(array('country'=>$country,'cih'=>$cih,'ci'=>$ci));
        die;
    }

    public function get_countries_list_fn(){
        $terms = get_terms( array(
            'taxonomy' => 'country',
            'hide_empty' => false,
        ) );
        $ctr = '';
        if(isset($_POST['userid']) && intval($_POST['userid']) > 0){
            $user_ID = $_POST['userid'];
            $ctr = get_user_meta($user_ID,'user_registration_country',true);
        }
        $countryHtml = '<option value="">- Select Country -</option>';
        foreach($terms as $country){
            $selected='';
            if($ctr == $country->slug){
                $selected = 'selected="selected"';
            }
            $countryHtml .= '<option value="'.$country->term_id.'" '.$selected.'>'.$country->name.'</option>'; 
        }
        echo $countryHtml; die;
    }
	public function get_ci_user_edit_window_fn(){
        $ctr = '';
		$user_ID = 0;
        if(isset($_POST['userid']) && intval($_POST['userid']) > 0){
            $user_ID = $_POST['userid'];
			$get_user = get_user_by('id', $user_ID );
            $ctr = get_user_meta($user_ID,'user_registration_country',true);
        }
		
		$user_firstname = get_user_meta($user_ID, 'first_name', true);
		$user_lastname = get_user_meta($user_ID, 'last_name', true);
		$user_email = $get_user->user_email;
		
		$firstnameHtml = "<input type='text' name='ci-firstname' id='ci-user-firstname-".$user_ID."' data-studentid='".$user_ID."' value='".$user_firstname."'>";
		$lastnameHtml = "<input type='text' name='ci-lastname' id='ci-user-lastname-".$user_ID."' data-studentid='".$user_ID."' value='".$user_lastname."'>";
		$emailHtml = "<input type='text' name='ci-email' id='ci-user-email-".$user_ID."' data-studentid='".$user_ID."' value='".$user_email."'>";
		
		$terms = get_terms( array(
            'taxonomy' => 'country',
            'hide_empty' => false,
        ) );
        $countryOptions = '<option value="">- Select Country -</option>';
        foreach($terms as $country){
            $selected='';
            if($ctr == $country->term_id){
                $selected = 'selected="selected"';
            }
            $countryOptions .= '<option value="'.$country->term_id.'" '.$selected.'>'.$country->name.'</option>'; 
        }
		$countryHtml = "<select name='ci-country' id='ci-user-country-".$user_ID."' data-studentid='".$user_ID."'>".$countryOptions."</select>";
		
		$return_data['firstnameHtml'] = $firstnameHtml;
		$return_data['lastnameHtml'] = $lastnameHtml;
		$return_data['emailHtml'] = $emailHtml;
		$return_data['countryHtml'] = $countryHtml;
		
        echo json_encode($return_data); die;
	}
	public function update_ci_user_data_fn(){
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ajaxnonce' ) ) {
            die ( 'Request is not from valid resource' );
        }
        $userid = filter_input(INPUT_POST, 'student');
        $country = filter_input(INPUT_POST, 'country');
        $firstname = filter_input(INPUT_POST, 'firstname');
        $lastname = filter_input(INPUT_POST, 'lastname');
		$email = filter_input(INPUT_POST, 'email');
		
		$validate_email = email_exists($email);
		if(!$validate_email || $validate_email == $userid){
			$args = array(
				'ID'         => $userid,
				'user_email' => sanitize_email( $email )
			);
			wp_update_user( $args );
			update_user_meta($userid, 'user_registration_country', $country);
			update_user_meta($userid, 'first_name', $firstname);
			update_user_meta($userid, 'last_name', $lastname);
			
			$country = get_term($country)->name;
			
			$message = __( 'Your Account Updated Successfully, Updated data:' ) . "\r\n\r\n";
			$message .= sprintf( __( 'First Name: %s'), $firstname ) . "\r\n\r\n";
			$message .= sprintf( __( 'Last Name: %s'), $lastname ) . "\r\n\r\n";
			$message .= sprintf( __( 'Email: %s'), $email ) . "\r\n\r\n";
			$message .= sprintf( __( 'Country: %s'), $country ) . "\r\n\r\n";
			$message .= __( 'If this was a mistake, Contact Admin.' ) . "\r\n\r\n";
			wp_mail(sanitize_email( $email ), 'Account Updated', $message);
			
			echo json_encode(array('response'=>1, 'country'=>$country, 'firstname'=>$firstname, 'lastname'=>$lastname, 'email'=>$email )); die;
		}else{
			echo json_encode(array('response'=>0)); die;
		}
    }
	public function get_teachers_edit_window_fn(){
		$user_ID = 0;
        if(isset($_POST['userid']) && intval($_POST['userid']) > 0){
            $user_ID = $_POST['userid'];
			$get_user = get_user_by('id', $user_ID );
        }
		
		$user_firstname = get_user_meta($user_ID, 'first_name', true);
		$user_lastname = get_user_meta($user_ID, 'last_name', true);
		$user_email = $get_user->user_email;
		
		$firstnameHtml = "<input type='text' name='ci-firstname' id='ci-user-firstname-".$user_ID."' data-studentid='".$user_ID."' value='".$user_firstname."'>";
		$lastnameHtml = "<input type='text' name='ci-lastname' id='ci-user-lastname-".$user_ID."' data-studentid='".$user_ID."' value='".$user_lastname."'>";
		$emailHtml = "<input type='text' name='ci-email' id='ci-user-email-".$user_ID."' data-studentid='".$user_ID."' value='".$user_email."'>";
		
		$return_data['firstnameHtml'] = $firstnameHtml;
		$return_data['lastnameHtml'] = $lastnameHtml;
		$return_data['emailHtml'] = $emailHtml;
		//$return_data['countryHtml'] = $countryHtml;
		
        echo json_encode($return_data); die;
	}
	public function update_teachers_data_fn(){
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ajaxnonce' ) ) {
            die ( 'Request is not from valid resource' );
        }
        $userid = filter_input(INPUT_POST, 'student');
        //$country = filter_input(INPUT_POST, 'country');
        $firstname = filter_input(INPUT_POST, 'firstname');
        $lastname = filter_input(INPUT_POST, 'lastname');
		$email = filter_input(INPUT_POST, 'email');
		
		$validate_email = email_exists($email);
		if(!$validate_email || $validate_email == $userid){
			$args = array(
				'ID'         => $userid,
				'user_email' => sanitize_email( $email )
			);
			wp_update_user( $args );
			//update_user_meta($userid, 'user_registration_country', $country);
			update_user_meta($userid, 'first_name', $firstname);
			update_user_meta($userid, 'last_name', $lastname);
			
			$country = get_term($country)->name;
			
			$message = __( 'Your Account Updated Successfully, Updated data:' ) . "\r\n\r\n";
			$message .= sprintf( __( 'First Name: %s'), $firstname ) . "\r\n\r\n";
			$message .= sprintf( __( 'Last Name: %s'), $lastname ) . "\r\n\r\n";
			$message .= sprintf( __( 'Email: %s'), $email ) . "\r\n\r\n";
			///$message .= sprintf( __( 'Country: %s'), $country ) . "\r\n\r\n";
			$message .= __( 'If this was a mistake, Contact Admin.' ) . "\r\n\r\n";
			wp_mail(sanitize_email( $email ), 'Account Updated', $message);
			
			echo json_encode(array('response'=>1, 'firstname'=>$firstname, 'lastname'=>$lastname, 'email'=>$email )); die;
		}else{
			echo json_encode(array('response'=>0)); die;
		}
    }

    public function pull_course_from_course_bank(){
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ajaxnonce' ) ) {
            die ( 'Request is not from valid resource' );
        }
        $courseid = filter_input(INPUT_POST,'courseId');
        $userid = filter_input(INPUT_POST,'userid');
        $courseUsers = (array)get_post_meta($courseid,'pull_by_ci_cih',true);
        $courseUsers[] = $userid;
        $courseUsers = array_filter($courseUsers);
        $courseUsers = array_values(array_unique($courseUsers));
        update_post_meta($courseid,'pull_by_ci_cih',$courseUsers);
        echo "Course pulled successfully.";
        die;
    }

    public function remove_course_from_course_bank(){
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ajaxnonce' ) ) {
            die ( 'Request is not from valid resource' );
        }
        $courseid = filter_input(INPUT_POST,'courseId');
        update_post_meta($courseid,'add_to_course_bank',0);
        echo 'Course removed from course bank';
        die;
    }

    public function addcourse_to_course_bank(){
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ajaxnonce' ) ) {
            die ( 'Request is not from valid resource' );
        }
        $courseid = filter_input(INPUT_POST,'courseId');
        update_post_meta($courseid,'add_to_course_bank',1);
        echo 'Course added to course bank';
        die;
    }
}

$ajaxObj = new class_ajax();