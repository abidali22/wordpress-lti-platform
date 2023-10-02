<?php

class Rest_Lxp_Assignment
{
	/**
	 * Register the REST API routes.
	 */
	public static function init()
	{
		if (!function_exists('register_rest_route')) {
			// The REST API wasn't integrated into core until 4.4, and we support 4.0+ (for now).
			return false;
		}

		register_rest_route('lms/v1', '/assignment/attempted', array(
			array(
				'methods' => WP_REST_Server::ALLMETHODS,
				'callback' => array('Rest_Lxp_Assignment', 'assignment_attempted'),
				'permission_callback' => '__return_true'
			)
		));

		register_rest_route('lms/v1', '/assignment/stats', array(
			array(
				'methods' => WP_REST_Server::ALLMETHODS,
				'callback' => array('Rest_Lxp_Assignment', 'assignment_stats'),
				'permission_callback' => '__return_true'
			)
		));

		register_rest_route('lms/v1', '/assignments/calendar/events', array(
			array(
				'methods' => WP_REST_Server::ALLMETHODS,
				'callback' => array('Rest_Lxp_Assignment', 'calendar_events'),
				'permission_callback' => '__return_true'
			)
		));

		register_rest_route('lms/v1', '/assignment/students', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Assignment', 'get_students'),
				'permission_callback' => '__return_true'
			)
		));

		register_rest_route('lms/v1', '/assignments', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Assignment', 'get_one'),
				'permission_callback' => '__return_true'
			)
		));
		
		register_rest_route('lms/v1', '/assignments/save', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Assignment', 'create'),
				'permission_callback' => '__return_true',
				'args' => array(										
					'student_ids' => array(
						'required' => true,
						'description' => 'assignment students',
						'validate_callback' => function($param, $request, $key) {
							$param = json_decode($param);
							if (count( $param ) > 0) {
								return true;
							} else {
								return false;
							}
						}
					),
					'segments_ids' => array(
						'required' => true,
						'description' => 'assignment trek segments',
						'validate_callback' => function($param, $request, $key) {
							$param = json_decode($param);
							if (count( $param ) > 0) {
								return true;
							} else {
								return false;
							}
						}
					),
					'trek_id' => array(
						'required' => true,
						'type' => 'integer',
						'description' => 'assignment trek id',
						'validate_callback' => function($param, $request, $key) {
							return intval( $param ) > 0;
						}
					),
					'class_id' => array(
						'required' => true,
						'type' => 'integer',
						'description' => 'assignment class id',
						'validate_callback' => function($param, $request, $key) {
							return intval( $param ) > 0;
						}
					),
					'teacher_id' => array(
						'required' => true,
						'type' => 'integer',
						'description' => 'assignment teacher id',
						'validate_callback' => function($param, $request, $key) {
							return intval( $param ) > 0;
						}
					),
					'assignment_post_id' => array(
						'required' => true,
						'type' => 'string',
						'description' => 'assignment post id',
						'validate_callback' => function($param, $request, $key) {
							return strlen( $param ) > 0;
						}
					),
					'calendar_selection_info' => array(
						'required' => true,
						'type' => 'string',
						'description' => 'assignment calendar info id',
						'validate_callback' => function($param, $request, $key) {
							return strlen( $param ) > 0;
						}
					)
			   )
			),
		));
		
		register_rest_route('lms/v1', '/update/assignment', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Assignment', 'update_assignment'),
				'permission_callback' => '__return_true',
				'args' => array(
					'user_email' => array(
					   'required' => true,
					   'type' => 'string',
					   'description' => 'user login name',  
					   'format' => 'email'
				   ),
				   'login_name' => array(
					'required' => true,
					'type' => 'string',
					'description' => 'user login name name'
				),
				'first_name' => array(
					'required' => true,
					'type' => 'string',
					'description' => 'user first name',
				),
				'last_name' => array(
					'required' => true,
					'type' => 'string',
					'description' => 'user last name',
				),
				'id' => array(
					'required' => true,
					'type' => 'integer',
					'description' => 'user account id',
				),
				   
			   )
			),
		));
		
	}

	public static function create($request) {		
		
		$trek_id = $request->get_param('trek_id');
		$trek_post = get_post($trek_id);

		$segments_ids = json_decode($request->get_param('segments_ids'));
		$segments_title = json_decode($request->get_param('segments_title'));
		$class_id = $request->get_param('class_id');
		$group_id = $request->get_param('group_id');
		$calendar_selection_info = json_decode($request->get_param('calendar_selection_info'));
		
		$start = new DateTime($calendar_selection_info->startStr, new DateTimeZone('UTC'));
		$end = new DateTime($calendar_selection_info->endStr, new DateTimeZone('UTC'));
		
		$start_date = $start->format('Y-m-d');
		$start_time = $start->format('H:i:s');
		

		$end_date = $end->format('Y-m-d');
		$end_time = $end->format('H:i:s');
		
		global $wpdb;		
		//foreach ($segments_ids as $segment_id) {
			//$segment = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}trek_sections WHERE id={$segment_id}");	
		foreach ($segments_title as $key => $title) {
			$segment = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}trek_sections WHERE trek_id={$trek_id} AND title='".$title."'");
			$segment_title = ( $segment ) ? $segment->title : $title;
			$segment_id = ( $segment) ? $segment->id : $segments_ids[$key];
			// ============= Assignment Post =================================
			$assignment_teacher_id = $request->get_param('teacher_id');
			$assignment_post_id = intval($request->get_param('assignment_post_id'));
			$assignment_name = $segment_title . ' - ' . $trek_post->post_title;
			
			$assignment_post_arg = array(
				'post_title'    => wp_strip_all_tags($assignment_name),
				'post_content'  => $assignment_name,
				'post_status'   => 'publish',
				'post_author'   => $assignment_teacher_id,
				'post_type'   => TL_ASSIGNMENT_CPT
			);
			if (intval($assignment_post_id) > 0) {
				$assignment_post_arg['ID'] = "$assignment_post_id";
			}
			
			// Insert / Update
			$assignment_post_id = wp_insert_post($assignment_post_arg);

			if(get_post_meta($assignment_post_id, 'lxp_assignment_teacher_id', true)) {
				update_post_meta($assignment_post_id, 'lxp_assignment_teacher_id', $assignment_teacher_id);
			} else {
				add_post_meta($assignment_post_id, 'lxp_assignment_teacher_id', $assignment_teacher_id, true);
			}
			
			delete_post_meta($assignment_post_id, 'lxp_student_ids');
			$student_ids = json_decode($request->get_param('student_ids'));
			foreach ($student_ids as $student_id) {
				add_post_meta($assignment_post_id, 'lxp_student_ids', $student_id);
			}

			if(get_post_meta($assignment_post_id, 'trek_section_id', true)) {
				update_post_meta($assignment_post_id, 'trek_section_id', $segment_id);
			} else {
				add_post_meta($assignment_post_id, 'trek_section_id', $segment_id, true);
			}

			if(get_post_meta($assignment_post_id, 'trek_id', true)) {
				update_post_meta($assignment_post_id, 'trek_id', $trek_id);
			} else {
				add_post_meta($assignment_post_id, 'trek_id', $trek_id, true);
			}

			if(get_post_meta($assignment_post_id, 'class_id', true)) {
				update_post_meta($assignment_post_id, 'class_id', $class_id);
			} else {
				add_post_meta($assignment_post_id, 'class_id', $class_id, true);
			}

			if(get_post_meta($assignment_post_id, 'group_id', true)) {
				update_post_meta($assignment_post_id, 'group_id', $group_id);
			} else {
				add_post_meta($assignment_post_id, 'group_id', $group_id, true);
			}

			if(get_post_meta($assignment_post_id, 'calendar_selection_info', true)) {
				update_post_meta($assignment_post_id, 'calendar_selection_info', json_encode($calendar_selection_info));
			} else {
				add_post_meta($assignment_post_id, 'calendar_selection_info', json_encode($calendar_selection_info), true);
			}

			if(get_post_meta($assignment_post_id, 'start_date', true)) {
				update_post_meta($assignment_post_id, 'start_date', $start_date);
			} else {
				add_post_meta($assignment_post_id, 'start_date', $start_date, true);
			}

			if(get_post_meta($assignment_post_id, 'start_time', true)) {
				update_post_meta($assignment_post_id, 'start_time', $start_time);
			} else {
				add_post_meta($assignment_post_id, 'start_time', $start_time, true);
			}

			if(get_post_meta($assignment_post_id, 'end_date', true)) {
				update_post_meta($assignment_post_id, 'end_date', $end_date);
			} else {
				add_post_meta($assignment_post_id, 'end_date', $end_date, true);
			}

			if(get_post_meta($assignment_post_id, 'end_time', true)) {
				update_post_meta($assignment_post_id, 'end_time', $end_time);
			} else {
				add_post_meta($assignment_post_id, 'end_time', $end_time, true);
			}
		}
		
        return wp_send_json_success("Assignments Created!");
    }

	public static function assignment_attempted($request) {
		$assignment_id = $request->get_param('assignmentId');
		$student_user_id = $request->get_param('userId');
		$student_posts = get_posts(array(
			'post_type' => TL_STUDENT_CPT,
			'meta_query' => array(
				array(
					'key' => 'lxp_student_admin_id',
					'value' => $student_user_id,
					'compare' => '='
				)
			)
		));
		$student_post = $student_posts[0];
		
		if ($student_post) {
			$ok = false;
			$student_id = $student_post->ID;
			// add student_id as a 'attempted' metadata to assignment post
			if ( !in_array($student_id, get_post_meta($assignment_id, 'attempted_students')) ) {
				$ok = add_post_meta($assignment_id, 'attempted_students', $student_id);
			}
			$message = $ok ? "Assignment Attempted!" : "Attemp record not created!";
			return wp_send_json_success($message);
		} else {
			return wp_send_json_error("Assignment Attempt Failed!");
		}
	}

	public static function assignment_stats($request) {
		$assignment_id = $request->get_param('assignment_id');
		$students_ids = get_post_meta($assignment_id, 'lxp_student_ids');
		$q = new WP_Query( array( "post_type" => TL_STUDENT_CPT, 'posts_per_page'   => -1, "post__in" => $students_ids ) );
		$students_posts = $q->get_posts();
		$students = array_map(function ($student) use ($assignment_id) {
			$attempted = self::lxp_user_assignment_attempted($assignment_id, $student->ID);
			$submission = self::lxp_get_assignment_submissions($assignment_id, $student->ID);
			/* 
			if ($attempted && is_null($submission)) {
				$status = 'In Progress';
			}else if ($attempted && !is_null($submission)) {
				$status = 'Completed';
			} else {
				$status = 'To Do';
			}
 			*/
			 $status = 'To Do';
			 if ($attempted && !is_null($submission) && !$submission['lti_user_id'] && !$submission['submission_id']) {
				 $status = 'In Progress';
			 } else if ($attempted && !is_null($submission) && $submission['lti_user_id'] && $submission['submission_id']) {
				 $status = 'Completed';
			 }
			$lxp_student_admin_id = get_post_meta($student->ID, 'lxp_student_admin_id', true);
			$userdata = get_userdata($lxp_student_admin_id);
			$progress = $submission && $submission['score_raw'] && $submission['score_max'] ? $submission['score_raw'] .'/'. $submission['score_max'] : '---';
			$score = $submission && $submission['score_scaled'] ? round(($submission['score_scaled'] * 100), 2) . '%' : '---';
			$data = array("ID" => $student->ID, "name" => $userdata->data->display_name, "status" => $status, "progress" => $progress, "score" => $score);
			return $data;
		} , $students_posts);
		return wp_send_json_success($students);
	}

	public static function lxp_get_assignment_submissions($assignment_id, $student_post_id)
	{
		$query = new WP_Query( array( 'post_type' => TL_ASSIGNMENT_SUBMISSION_CPT , 'posts_per_page'   => -1, 'post_status' => array( 'publish' ), 
									'meta_query' => array(
										array('key' => 'lxp_assignment_id', 'value' => $assignment_id, 'compare' => '='),
										array('key' => 'lxp_student_id', 'value' => $student_post_id, 'compare' => '=')
									)
								)
							);
		$assignment_submission_posts = $query->get_posts();
	
		if ($assignment_submission_posts) {
			$assignment_submission_post = $assignment_submission_posts[0];
			$assignment_submission_post_data = array(
				'ID' => $assignment_submission_post->ID,
				'lxp_assignment_id' => get_post_meta($assignment_submission_post->ID, 'lxp_assignment_id', true),
				'lxp_student_id' => get_post_meta($assignment_submission_post->ID, 'lxp_student_id', true),
				'lti_user_id' => get_post_meta($assignment_submission_post->ID, 'lti_user_id', true),
				'submission_id' => get_post_meta($assignment_submission_post->ID, 'submission_id', true),
				'score_min' => get_post_meta($assignment_submission_post->ID, 'score_min', true),
				'score_max' => get_post_meta($assignment_submission_post->ID, 'score_max', true),
				'score_raw' => get_post_meta($assignment_submission_post->ID, 'score_raw', true),
				'score_scaled' => get_post_meta($assignment_submission_post->ID, 'score_scaled', true),
				'completion' => boolval(get_post_meta($assignment_submission_post->ID, 'completion', true)),
				'duration' => get_post_meta($assignment_submission_post->ID, 'duration', true)
			);
			return $assignment_submission_post_data;
		} else {
			return null;
		}
	}

	public static function lxp_user_assignment_attempted($assignment_id, $user_id) {
		$query = new WP_Query( array( 'post_type' => TL_ASSIGNMENT_CPT , 'posts_per_page'   => -1, 'post_status' => array( 'publish' ), 'p' => $assignment_id,
									'meta_query' => array(
										array('key' => 'attempted_students', 'value' => $user_id, 'compare' => 'IN')
									)
								)
							);
		$assignment_posts = $query->get_posts();
		return count($assignment_posts) > 0 ? true : false;
	}

    public static function get_teacher_assignments_calendar_events($teacher_id) {
		$assignment_query = new WP_Query( array( 
			'post_type' => TL_ASSIGNMENT_CPT, 
			'post_status' => array( 'publish' ),
			'posts_per_page'   => -1,        
			'meta_query' => array(
				array('key' => 'lxp_assignment_teacher_id', 'value' => $teacher_id, 'compare' => 'IN')
			)
		) );
		
		return array_map(function($assignment) {
			$calendar_selection_info = json_decode(get_post_meta($assignment->ID, 'calendar_selection_info', true));
			$trek_section_id = get_post_meta($assignment->ID, 'trek_section_id', true);
			global $wpdb;
    		$trek_section = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}trek_sections WHERE id={$trek_section_id}");
			$trek = get_post(get_post_meta($assignment->ID, 'trek_id', true));
			$event = array();
			$event["id"] = $assignment->ID;
			$event["start"] = $calendar_selection_info && property_exists($calendar_selection_info, 'start') ? $calendar_selection_info->start : '';
			$event["end"] = $calendar_selection_info && property_exists($calendar_selection_info, 'end') ? $calendar_selection_info->end : '';
			$event["allDay"] = $calendar_selection_info && property_exists($calendar_selection_info, 'allDay') ? $calendar_selection_info->allDay : false;
			$event["title"] = $trek_section->title;
			$event["segment"] = implode("-", explode(" ", strtolower($trek_section->title))) ;
			$event['trek'] = $trek ? $trek->post_title : '';
			$event["calendar_selection_info"] = json_encode($calendar_selection_info);
			return $event;
		}, $assignment_query->get_posts());
	}

    public static function calendar_events($request) {
		$userdata = get_userdata($request->get_param('user_id'));
		$teacher_post = self::lxp_get_teacher_post($userdata->data->ID);
		return self::get_teacher_assignments_calendar_events($teacher_post->ID);
	}

	
	public static function lxp_get_teacher_post($lxp_teacher_admin_id)
	{
		$teacher_query = new WP_Query( array( 
			'post_type' => TL_TEACHER_CPT, 
			'post_status' => array( 'publish' ),
			'posts_per_page'   => -1,        
			'meta_query' => array(
				array('key' => 'lxp_teacher_admin_id', 'value' => $lxp_teacher_admin_id, 'compare' => '=')
			)
		) );
		
		$posts = $teacher_query->get_posts();
		return ( count($posts) > 0 ? $posts[0] : null );
	}

    public static function get_students($request) {
		$assignment_id = $request->get_param('assignment_id');
		$lxp_student_ids = get_post_meta($assignment_id, 'lxp_student_ids');
		$students = array_map(function($student_id) { 
			$post = get_post($student_id); 
			$user = get_userdata(get_post_meta($student_id, 'lxp_student_admin_id', true))->data;
			return array('post' => $post, 'user' => $user);
		} , $lxp_student_ids);

		return wp_send_json_success(array("students" => $students));
	}

    public static function get_one($request) {
		$assignment_id = $request->get_param('assignment_id');
		$assignment = get_post($assignment_id);
		$assignment->grade = get_post_meta($assignment_id, 'grade', true);
		$assignment->lxp_assignment_teacher_id = get_post_meta($assignment_id, 'lxp_assignment_teacher_id', true);
		$assignment->lxp_student_ids = get_post_meta($assignment_id, 'lxp_student_ids');
		$assignment->schedule = json_decode(get_post_meta($assignment_id, 'schedule', true));
		return wp_send_json_success(array("assignment" => $assignment));
	}

    public static function update_assignment()
	{
        $user_data = array(
            'ID' => $_POST['id'],
            'user_login' => $_POST['login_name'],
            'first_name' => $_POST['first_name'],
            'last_name' =>$_POST['last_name'],
            'user_email' =>$_POST['user_email'],
            'display_name' =>$_POST['first_name'] . ' ' .$_POST['last_name'],
            'user_pass' =>$_POST['login_pass']
         );
         wp_send_json_success (wp_update_user($user_data));
		 
	}
}
