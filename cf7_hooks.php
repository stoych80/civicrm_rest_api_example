<?php
defined('ABSPATH') || die('do not access this file directly');
class cf7_hooks {
	// class instance
	private static $instance;
	private static $remote_uq74f_civicrm_url='https://example.com/crm/';
	private static $remote_uq74f_civicrm_api_key='***';
	private static $remote_uq74f_civicrm_site_key='***';

	// class constructor
	public function __construct() {
		/*
		add_filter('wpcf7_mail_components', function ($components, $wpcf7_form_object, $wpcf7_mail_object) {
			if ($wpcf7_form_object->id() == 7) {
				$url = strtok($_SERVER['HTTP_REFERER'], '?');
				$the_slug = str_replace('/','',str_replace('/event-pro/','',str_replace(get_site_url(), '', $url)));
				if (($post = get_page_by_path($the_slug, OBJECT, 'ait-event-pro')) && ($wp_user = get_user_by('id',$post->post_author))) {
					$components['recipient']=$wp_user->user_email;
					$components['subject'].=' "'.$post->post_title.'"';
					$components['body']='"'.$post->post_title.'", TP: '.$wp_user->display_name.' ('.$wp_user->user_email.')'."\n".$components['body'];
				}
			}
			return $components;
		},10,3);*/
		add_action('wp', [$this,'wp']);
		add_filter('wpcf7_skip_mail', [$this,'wpcf7_skip_mail'],10,2);
		add_filter('wpcf7_validate', [$this,'wpcf7_validate'],10,2);
	}
	
	public function wp() {
		if (isset($_GET['MyClient_confirm_newsletter_subscription']) && $_GET['MyClient_confirm_newsletter_subscription']==1 && !empty($_GET['email']) && isset($_GET['hash'])) {
			if ($_GET['hash'] != hash('sha256', '***'.$_GET['email'].'***'.date('Y-m-d')) && $_GET['hash'] != hash('sha256', '***'.$_GET['email'].'***'.date('Y-m-d', strtotime('-1 day')))) {
				wp_safe_redirect('/?the_msg='.urlencode('Your link has expired. You have to subscribe again.').'&is_error=1');exit;
			}
			//check if the user is not there already
			$response = wp_remote_post(self::$remote_uq74f_civicrm_url.'sites/all/modules/civicrm/extern/rest.php', array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => array(
					'entity' => 'Contact',
					'action' => 'get',
					'api_key' => self::$remote_uq74f_civicrm_api_key,
					'key' => self::$remote_uq74f_civicrm_site_key,
					'json' => '{"sequential":1,"email":"'.$_GET['email'].'"}',
				),
				'cookies' => array()
				)
			);
			if (is_wp_error($response)) {
				wp_mail('stoycho@example.com', 'MyClient WP error posting to Footer sign up civicrm 1', $response->get_error_message());
				wp_safe_redirect('/?the_msg='.urlencode($response->get_error_message()).'&is_error=1');exit;
			} else {
				$response['body'] = json_decode($response['body']);
				if (!empty($response['body']->is_error) && !empty($response['body']->error_message)) {
					wp_mail('stoycho@example.com', 'MyClient civicrm error posting to Footer sign up civicrm 1', $response['body']->error_message);
					wp_safe_redirect('/?the_msg='.urlencode($response['body']->error_message).'&is_error=1');exit;
				}
			}
			$new_contact_id = null;
			$existing_contact_id = $response['body']->values ? $response['body']->values[0]->contact_id : null;
			if (!$existing_contact_id) {
				$json = '{"contact_type":"Individual","source":"MyClient website Footer sign up form","email":"'.$_GET['email'].'"}';
				$response = wp_remote_post(self::$remote_uq74f_civicrm_url.'sites/all/modules/civicrm/extern/rest.php', array(
					'method' => 'POST',
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking' => true,
					'headers' => array(),
					'body' => array(
						'entity' => 'Contact',
						'action' => 'create',
						'api_key' => self::$remote_uq74f_civicrm_api_key,
						'key' => self::$remote_uq74f_civicrm_site_key,
						'json' => $json
					),
					'cookies' => array()
					)
				);
				if (is_wp_error($response)) {
					wp_mail('stoycho@example.com', 'MyClient WP error posting to Footer sign up civicrm 2', $response->get_error_message());
					wp_safe_redirect('/?the_msg='.urlencode($response->get_error_message()).'&is_error=1');exit;
				} else {
					$response['body'] = json_decode($response['body']);
					if (!empty($response['body']->is_error) && !empty($response['body']->error_message)) {
						wp_mail('stoycho@example.com', 'MyClient civicrm error posting to Footer sign up civicrm 2', $response['body']->error_message);
						wp_safe_redirect('/?the_msg='.urlencode($response['body']->error_message).'&is_error=1');exit;
					}
				}
				$new_contact_id = $response['body']->id;
			}
			//Add contact to the mailing group 'technology news'
			if ($existing_contact_id || $new_contact_id) {
				$json = '{"sequential":1,"title":"technology news","is_active":1}';
				$response = wp_remote_post(self::$remote_uq74f_civicrm_url.'sites/all/modules/civicrm/extern/rest.php', array(
					'method' => 'POST',
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking' => true,
					'headers' => array(),
					'body' => array(
						'entity' => 'Group',
						'action' => 'get',
						'api_key' => self::$remote_uq74f_civicrm_api_key,
						'key' => self::$remote_uq74f_civicrm_site_key,
						'json' => $json
					),
					'cookies' => array()
					)
				);
				if (is_wp_error($response)) {
					wp_mail('stoycho@example.com', 'MyClient WP error posting to Footer sign up civicrm 4', $response->get_error_message());
					wp_safe_redirect('/?the_msg='.urlencode($response->get_error_message()).'&is_error=1');exit;
				} else {
					$response['body'] = json_decode($response['body']);
					if (!empty($response['body']->is_error) && !empty($response['body']->error_message)) {
						wp_mail('stoycho@example.com', 'MyClient civicrm error posting to Footer sign up civicrm 4', $response['body']->error_message);
						wp_safe_redirect('/?the_msg='.urlencode($response['body']->error_message).'&is_error=1');exit;
					}
				}
				$technology_news_group_id = $response['body']->values ? $response['body']->values[0]->id : null;
				if ($technology_news_group_id) {
					$json = '{"contact_id":'.($existing_contact_id ? $existing_contact_id : $new_contact_id).',"group_id":'.$technology_news_group_id.',"status":"Added"}';
					$response = wp_remote_post(self::$remote_uq74f_civicrm_url.'sites/all/modules/civicrm/extern/rest.php', array(
						'method' => 'POST',
						'timeout' => 45,
						'redirection' => 5,
						'httpversion' => '1.0',
						'blocking' => true,
						'headers' => array(),
						'body' => array(
							'entity' => 'GroupContact',
							'action' => 'create',
							'api_key' => self::$remote_uq74f_civicrm_api_key,
							'key' => self::$remote_uq74f_civicrm_site_key,
							'json' => $json
						),
						'cookies' => array()
						)
					);
					if (is_wp_error($response)) {
						wp_mail('stoycho@example.com', 'MyClient WP error posting to Footer sign up civicrm 5', $response->get_error_message());
						wp_safe_redirect('/?the_msg='.urlencode($response->get_error_message()).'&is_error=1');exit;
					} else {
						$response['body'] = json_decode($response['body']);
						if (!empty($response['body']->is_error) && !empty($response['body']->error_message)) {
							wp_mail('stoycho@example.com', 'MyClient civicrm error posting to Footer sign up civicrm 5', $response['body']->error_message);
							wp_safe_redirect('/?the_msg='.urlencode($response['body']->error_message).'&is_error=1');exit;
						}
					}
				}
			}
			wp_safe_redirect('/?the_msg='.urlencode('Thank you for confirming your newsletter subscription.'));exit;
		}
	}
	
	public function wpcf7_skip_mail($skip_mail, $contact_form) {
		if ($contact_form->id()==87) { //Footer sign up
			$skip_mail = true;
			$submission = WPCF7_Submission::get_instance();
			$posted_data = null;
			if ($submission) {
				$posted_data = $submission->get_posted_data();
			}
			if ($posted_data) {
	//			$posted_data['your-email'];
				//Trigger the confirm subscriptions email
//				$site_name = get_option('blogname');
				$site_url = get_site_url();
				$hash = hash('sha256', '***'.$posted_data['your-email'].'***'.date('Y-m-d'));
				add_filter('wp_mail_content_type',function($type) {return "text/html";});
				wp_mail($posted_data['your-email'], 'Confirm your subscription','Thank you for signing up to receive eLife\'s bi-monthly technology newsletter. Please complete one last step and confirm your request by clicking <a href="'.$site_url.'/?MyClient_confirm_newsletter_subscription=1&email='.urlencode($posted_data['your-email']).'&hash='.urlencode($hash).'">here</a>.<br><br>Best wishes,<br>The team at eLife<br><br><br>The link is valid until '.date('d/m/Y 23:59', strtotime('+1 day'))."<br>(If it wasn't you who subscribed, you can safely ignore this email)");
			}
		}
		return $skip_mail;
	}
	public function wpcf7_validate($result, $tags) {
		//if there are still fields not passing validation, do not process the civicrm stuff.
		if ($result->get_invalid_fields()) {
			return $result;
		}
		$submission = WPCF7_Submission::get_instance();
		$posted_data = null;
		if ($submission) {
			$posted_data = $submission->get_posted_data();
		}
		/*
		if ($posted_data && $submission->get_contact_form()->id()==87) { //Footer sign up
//			$posted_data['your-email'];
			$err_msg = '';
			//check if the contact exists at all OR if the contact exists and it is subscribed to the technology news group
			$response = wp_remote_post(self::$remote_uq74f_civicrm_url.'sites/all/modules/civicrm/extern/rest.php', array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => array(
					'entity' => 'Contact',
					'action' => 'get',
					'api_key' => self::$remote_uq74f_civicrm_api_key,
					'key' => self::$remote_uq74f_civicrm_site_key,
					'json' => '{"sequential":1,"email":"'.$posted_data['your-email'].'"}',
				),
				'cookies' => array()
				)
			);
			if (is_wp_error($response)) {
				wp_mail('stoycho@example.com', 'MyClient WP error posting to Footer sign up civicrm 10', $response->get_error_message());
				$err_msg.="\n".$response->get_error_message();
			} else {
				$response['body'] = json_decode($response['body']);
				if (!empty($response['body']->is_error) && !empty($response['body']->error_message)) {
					wp_mail('stoycho@example.com', 'MyClient civicrm error posting to Footer sign up civicrm 10', $response['body']->error_message);
					$err_msg.="\n".$response['body']->error_message;
				}
			}
			$existing_contact_id = $response['body']->values ? $response['body']->values[0]->contact_id : null;
			if ($existing_contact_id) {
				$err_msg.="\n".'It seems you are already subscribed.';
			}
			//use the 'your-email' field to show the civicrm errors
			if ($err_msg)
				$result->invalidate('your-email', $err_msg);
		}*/
		if ($posted_data && $submission->get_contact_form()->id()==7) { //Contact form 1
//			$posted_data['first-name'];
//			$posted_data['last-name'];
//			$posted_data['your-email'];
//			$posted_data['your-tel'];
//			$posted_data['your-message'];
			$err_msg = '';
			//check if the user is not there already
			$response = wp_remote_post(self::$remote_uq74f_civicrm_url.'sites/all/modules/civicrm/extern/rest.php', array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => array(
					'entity' => 'Contact',
					'action' => 'get',
					'api_key' => self::$remote_uq74f_civicrm_api_key,
					'key' => self::$remote_uq74f_civicrm_site_key,
					'json' => '{"sequential":1,"email":"'.$posted_data['your-email'].'"}',
				),
				'cookies' => array()
				)
			);
			if (is_wp_error($response)) {
				wp_mail('stoycho@example.com', 'MyClient WP error posting to Contact Form civicrm 1', $response->get_error_message());
				$err_msg.="\n".$response->get_error_message();
			} else {
				$response['body'] = json_decode($response['body']);
				if (!empty($response['body']->is_error) && !empty($response['body']->error_message)) {
					wp_mail('stoycho@example.com', 'MyClient civicrm error posting to Contact Form civicrm 1', $response['body']->error_message);
					$err_msg.="\n".$response['body']->error_message;
				}
			}
			$existing_contact_id = $response['body']->values ? $response['body']->values[0]->contact_id : null;
			$json = '{"contact_type":"Individual","first_name":"'.$posted_data['first-name'].'","last_name":"'.$posted_data['last-name'].'"';
			if ($existing_contact_id) {
				$json .= ',"id":"'.$existing_contact_id.'"';
			} else {
				$json .= ',"source":"MyClient website contact form"';
			}
			$json .= '}';
			$response = wp_remote_post(self::$remote_uq74f_civicrm_url.'sites/all/modules/civicrm/extern/rest.php', array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => array(
					'entity' => 'Contact',
					'action' => 'create',
					'api_key' => self::$remote_uq74f_civicrm_api_key,
					'key' => self::$remote_uq74f_civicrm_site_key,
					'json' => $json
				),
				'cookies' => array()
				)
			);
			if (is_wp_error($response)) {
				wp_mail('stoycho@example.com', 'MyClient WP error posting to Contact Form civicrm 2', $response->get_error_message());
				$err_msg.="\n".$response->get_error_message();
			} else {
				$response['body'] = json_decode($response['body']);
				if (!empty($response['body']->is_error) && !empty($response['body']->error_message)) {
					wp_mail('stoycho@example.com', 'MyClient civicrm error posting to Contact Form civicrm 2', $response['body']->error_message);
					$err_msg.="\n".$response['body']->error_message;
				}
			}
			if (!empty($response['body']->id)) {
				$new_contact_id = $response['body']->id;
				if (!$existing_contact_id) {
					//add the email if the contact was not found (if he was found no point to update the same email)
					$json = '{"contact_id":'.$new_contact_id.',"email":"'.$posted_data['your-email'].'","location_type_id":"Main","is_primary":1}';
					$response = wp_remote_post(self::$remote_uq74f_civicrm_url.'sites/all/modules/civicrm/extern/rest.php', array(
						'method' => 'POST',
						'timeout' => 45,
						'redirection' => 5,
						'httpversion' => '1.0',
						'blocking' => true,
						'headers' => array(),
						'body' => array(
							'entity' => 'Email',
							'action' => 'create',
							'api_key' => self::$remote_uq74f_civicrm_api_key,
							'key' => self::$remote_uq74f_civicrm_site_key,
							'json' => $json
						),
						'cookies' => array()
						)
					);
					if (is_wp_error($response)) {
						wp_mail('stoycho@example.com', 'MyClient WP error posting to Contact Form civicrm 3', $response->get_error_message());
						$err_msg.="\n".$response->get_error_message();
					} else {
						$response['body'] = json_decode($response['body']);
						if (!empty($response['body']->is_error) && !empty($response['body']->error_message)) {
							wp_mail('stoycho@example.com', 'MyClient civicrm error posting to Contact Form civicrm 3', $response['body']->error_message);
							$err_msg.="\n".$response['body']->error_message;
						}
					}
				}
				if (!empty($posted_data['your-tel'])) {
					$json = '{"contact_id":'.$new_contact_id.',"phone":"'.$posted_data['your-tel'].'","is_primary":'.($existing_contact_id ? 0 : 1).',"phone_type_id":"Phone","location_type_id":"'.($existing_contact_id ? 'Other' : 'Main').'"}';
					$response = wp_remote_post(self::$remote_uq74f_civicrm_url.'sites/all/modules/civicrm/extern/rest.php', array(
						'method' => 'POST',
						'timeout' => 45,
						'redirection' => 5,
						'httpversion' => '1.0',
						'blocking' => true,
						'headers' => array(),
						'body' => array(
							'entity' => 'Phone',
							'action' => 'create',
							'api_key' => self::$remote_uq74f_civicrm_api_key,
							'key' => self::$remote_uq74f_civicrm_site_key,
							'json' => $json
						),
						'cookies' => array()
						)
					);
					if (is_wp_error($response)) {
						wp_mail('stoycho@example.com', 'MyClient WP error posting to Contact Form civicrm 4', $response->get_error_message());
						$err_msg.="\n".$response->get_error_message();
					} else {
						$response['body'] = json_decode($response['body']);
						if (!empty($response['body']->is_error) && !empty($response['body']->error_message)) {
							wp_mail('stoycho@example.com', 'MyClient civicrm error posting to Contact Form civicrm 4', $response['body']->error_message);
							$err_msg.="\n".$response['body']->error_message;
						}
					}
				}
				if (!empty($posted_data['your-message'])) {
					$json = '{"entity_id":'.$new_contact_id.',"subject":"The message from the MyClient website contact form","note":"'.$posted_data['your-message'].'","privacy":"None","entity_table":"civicrm_contact"}';
					$response = wp_remote_post(self::$remote_uq74f_civicrm_url.'sites/all/modules/civicrm/extern/rest.php', array(
						'method' => 'POST',
						'timeout' => 45,
						'redirection' => 5,
						'httpversion' => '1.0',
						'blocking' => true,
						'headers' => array(),
						'body' => array(
							'entity' => 'Note',
							'action' => 'create',
							'api_key' => self::$remote_uq74f_civicrm_api_key,
							'key' => self::$remote_uq74f_civicrm_site_key,
							'json' => $json
						),
						'cookies' => array()
						)
					);
					if (is_wp_error($response)) {
						wp_mail('stoycho@example.com', 'MyClient WP error posting to Contact Form civicrm 5', $response->get_error_message());
						$err_msg.="\n".$response->get_error_message();
					} else {
						$response['body'] = json_decode($response['body']);
						if (!empty($response['body']->is_error) && !empty($response['body']->error_message)) {
							wp_mail('stoycho@example.com', 'MyClient civicrm error posting to Contact Form civicrm 5', $response['body']->error_message);
							$err_msg.="\n".$response['body']->error_message;
						}
					}
				}
				$json = '{"entity_id":'.$new_contact_id.',"tag_id":"Libero contact form","entity_table":"civicrm_contact"}';
				$response = wp_remote_post(self::$remote_uq74f_civicrm_url.'sites/all/modules/civicrm/extern/rest.php', array(
					'method' => 'POST',
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking' => true,
					'headers' => array(),
					'body' => array(
						'entity' => 'EntityTag',
						'action' => 'get',
						'api_key' => self::$remote_uq74f_civicrm_api_key,
						'key' => self::$remote_uq74f_civicrm_site_key,
						'json' => $json
					),
					'cookies' => array()
					)
				);
				$has_libero_contact_form_tag = false;
				if (is_wp_error($response)) {
					wp_mail('stoycho@example.com', 'MyClient WP error posting to Contact Form civicrm 6', $response->get_error_message());
					$err_msg.="\n".$response->get_error_message();
					$has_libero_contact_form_tag = true; //in case of error do not try to add it further down.
				} else {
					$response['body'] = json_decode($response['body']);
					if (!empty($response['body']->is_error) && !empty($response['body']->error_message)) {
						wp_mail('stoycho@example.com', 'MyClient civicrm error posting to Contact Form civicrm 6', $response['body']->error_message);
						$err_msg.="\n".$response['body']->error_message;
						$has_libero_contact_form_tag = true; //in case of error do not try to add it further down.
					} else {
						$has_libero_contact_form_tag = (bool) $response['body']->values;
					}
				}
				if (!$has_libero_contact_form_tag) {
					$json = '{"entity_id":'.$new_contact_id.',"tag_id":"Libero contact form","entity_table":"civicrm_contact"}';
					$response = wp_remote_post(self::$remote_uq74f_civicrm_url.'sites/all/modules/civicrm/extern/rest.php', array(
						'method' => 'POST',
						'timeout' => 45,
						'redirection' => 5,
						'httpversion' => '1.0',
						'blocking' => true,
						'headers' => array(),
						'body' => array(
							'entity' => 'EntityTag',
							'action' => 'create',
							'api_key' => self::$remote_uq74f_civicrm_api_key,
							'key' => self::$remote_uq74f_civicrm_site_key,
							'json' => $json
						),
						'cookies' => array()
						)
					);
					if (is_wp_error($response)) {
						wp_mail('stoycho@example.com', 'MyClient WP error posting to Contact Form civicrm 7', $response->get_error_message());
						$err_msg.="\n".$response->get_error_message();
					} else {
						$response['body'] = json_decode($response['body']);
						if (!empty($response['body']->is_error) && !empty($response['body']->error_message)) {
							wp_mail('stoycho@example.com', 'MyClient civicrm error posting to Contact Form civicrm 7', $response['body']->error_message);
							$err_msg.="\n".$response['body']->error_message;
						}
					}
				}
			}
			//use the 'your-message' field to show the civicrm errors
			if ($err_msg)
				$result->invalidate('your-message', $err_msg);
		}
		return $result;
	}
	

	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}