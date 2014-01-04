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

abstract class Kohana_Controller_Req extends Controller {
	/**
	 * Automatically handle the output of an ajax request or not?
	 * @var bool
	 */
	protected $_handle_ajax = true;

	/**
	 * Handle Request Data persistence or output based on the request type.
	 */
	public function after() {
		if($this->_handle_ajax == true)
		{
			if($this->request->is_ajax())
			{
				$return = array();

				if(RD::has_messages() == false)
				{
					$return['status'] = 'success';
					$return['response'] = [''];
				}
				else if(RD::get_current(RD::ERROR) != null)
				{
					$return['status'] = 'error';
					$return['errors'] = RD::get_current(RD::ERROR);
				}
				else if(RD::get_current(array(RD::SUCCESS, RD::INFO)) != null)
				{
					$return['status'] = 'success';
					$return['response'] = RD::get_current(array(RD::SUCCESS, RD::INFO, RD::WARNING));
				}
				else
				{
					$return['status'] = 'success';
					$return['response'] = RD::get_current();
				}

				$this->response->headers('Content-Type', 'application/json');
				$this->response->body(json_encode($return));
			}
			else
			{
				//otherwise flash the messages so they can be used on the next page load
				if(RD::has_messages())
					RD::persist();

				parent::after();
			}
		}
		else
			parent::after();
	}

	/**
	 * @var bool Remove messages that are loaded in the view
	 */
	protected $_dump_all_alerts = false;

	/**
	 * Get all the persisted Request Data alerts (if any) and parse them into bootstrap HTML alerts
	 * @return string
	 */
	protected function _view_alerts() {
		$msgs = RD::get_once(NULL, array(), FALSE);

		if(count($msgs))
			return View::factory('RD/alerts', array('messages' => $msgs))->render();
		else if(RD::has_messages())
		{
			return View::factory('RD/alerts', array('messages' => RD::get_current(null, $this->_dump_all_alerts)))->render();
		}
		else
			return '';
	}

	/**
	 * @param string|Route $uri where to redirect to
	 * @param null   $code      Which redirect code to use (302 default)
	 * @param bool   $if_ajax   Should the redirect be performed during an ajax request?
	 */
	public static function redirect($uri='', $code=null, $if_ajax=false)
	{
		// if this is called during ajax request, check if we should actually redirect
		if(Request::initial()->is_ajax() && $if_ajax == false)
			return;

		if($code == null)
		{
			$code = 302;
		}

		RD::persist();

		return parent::redirect($uri, $code);
	}
}