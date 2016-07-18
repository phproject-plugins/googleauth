<?php
/**
 * @package  GoogleAuth
 * @author   Alan Hardman <alan@phpizza.com>
 * @version  1.0.0
 */

namespace Plugin\GoogleAuth;

class Base extends \Plugin {

	/**
	 * Initialize the plugin, adding authentication hooks and routes
	 */
	public function _load() {
		$f3 = \Base::instance();
		$f3->route("POST /googleauth", "Plugin\GoogleAuth\Base->auth");
        $this->_hook("render.login.after_login", array($this, "loginButton"));
        $this->_hook("render.login.after_footer", array($this, "loginFooter"));
	}

	/**
	 * Check if plugin is installed
	 * @return bool
	 */
	public function _installed() {
		return !!\Base::instance()->get("site.plugins.googleauth.client_id");
	}

	/**
	 * Generate page for admin panel
	 */
	public function _admin() {
		$f3 = \Base::instance();
		if($f3->get("POST.client_id")) {
			\Model\Config::setVal("site.plugins.googleauth.client_id", $f3->get("POST.client_id"));
		}
		echo \Helper\View::instance()->render("googleauth/view/admin.html");
	}

	/**
	 * Display Google login button on login page
	 * @return
	 */
	public function loginButton() {
		if($this->_installed()) {
			echo \Template::instance()->render("googleauth/view/loginbutton.html");
		}
	}

	/**
	 * Output authentication JS on login page
	 * @return
	 */
	public function loginFooter() {
		if($this->_installed()) {
			echo \Template::instance()->render("googleauth/view/loginfooter.html");
		}
	}

	/**
	 * POST /googleauth
	 * Authenticate user with Google OpenID token
	 *
	 * @param \Base $f3
	 */
	public function auth($f3) {
		$token = $f3->get("POST.token");
		$url = "https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=";
		$result = \Web::instance()->request($url . urlencode($token));
		$obj = json_decode($result['body']);
		if(!headers_sent()) {
			header("Content-type: application/json");
		}
		if($obj->email) {
			$user = new \Model\User;
			$user->load(array("email = ?", $obj->email));
			if($user->id) {
				$session = new \Model\Session($user->id);
				$session->setCurrent();
				echo json_encode(array('error' => null));
			} else {
				echo json_encode(array('error' => 'The email address "' . $obj->email . '" is not registered on this site.'));
			}
		} else {
			echo json_encode(array('error' => 'An unknown error occurred logging in.'));
		}
	}

}
