<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Req controller
 *
 * Handles RD output differently based on the request type (standard or AJAX)
 *
 * @package    Req
 * @author     Maxim Kerstens
 * @copyright  (c) 2013 Maxim Kerstens
 * @license    MIT
 * @link       http://github.com/happyDemon/RD/
 */

class Kohana_Controller_Req extends Controller {
	/**
	 * Automatically handle the output of an ajax request or not?
	 * @var bool
	 */
	protected $_handle_ajax = true;

	/**
	 * Handle Request Data persistence or output based on the request type.
	 */
	public function after() {
		if($this->_handle_ajax == true && RD::has_messages()) {
			if($this->request->is_ajax()) {
				$return = array();

				if($messages = RD::get_current(RD::ERROR) != null) {
					$return['status'] = 'error';
					$return['errors'] = $messages;
				}
				else if($messages = RD::get_current(RD::SUCCESS) != null) {
					$return['status'] = 'success';
					$return['response'] = $messages;
				}
				else if($messages = RD::get_current(RD::INFO) != null) {
					$return['status'] = 'info';
					$return['response'] = $messages;
				}
				else {
					$messages = RD::get_current();
					$return['status'] = 'success';
					$return['response'] = $messages;
				}

				$this->request->headers('Content-Type', 'application/json');
				$this->request->body(json_encode($return));
			}
			else {
				//otherwise flash the messages so they can be used on the next page load
				RD::persist();
				parent::after();
			}
		}
		else
			parent::after();
	}

	/**
	 * Get all the persisted Request Data alerts (if any) and parse them into bootstrap HTML alerts
	 * @return string
	 */
	protected function _view_alerts() {
		$msgs = RD::get_once(NULL, array(), FALSE);

		if(count($msgs))
			return View::factory('RD/alerts', array('messages' => $msgs))->render();
		else
			return '';
	}
}