<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Request Data helper
 *
 * Keeping data alive over requests
 *
 * @package    Req
 * @author     Maxim Kerstens
 * @copyright  (c) 2013 Maxim Kerstens
 * @license    MIT
 * @link       http://github.com/happyDemon/RD/
 */

class Kohana_RD {

	// Message types
	const ERROR = 'error';
	const SUCCESS = 'success';
	const INFO = 'info';

	/**
	 * @var  string  session key used for storing messages
	 */
	public static $storage_key = 'RD_MSG';

	/**
	 * @var array Stores messages created during this page load
	 */
	protected static $_msg = array();

	/**
	 * Set a new message.
	 *
	 *     RD::set(RD::SUCCESS, 'Your account has been deleted');
	 *
	 *     // Embed some values with sprintf
	 *     RD::set(RD::ERROR, '%s is not writable', array($file));
	 *
	 *     // Embed some values with strtr
	 *     RD::set(RD::ERROR, ':file is not writable',
	 *         array(':file' => $file));
	 *
	 * @param   string  $type    message type (e.g. RD::SUCCESS)
	 * @param   mixed   $text    message text OR array of messages
	 * @param   array   $values  values to replace with __() (mostly used in flash messages)
	 * @param   mixed   $data    custom data (mostly used in ajax returns) if set to true it'll copy $values into it
	 */
	public static function set($type, $text, array $values = NULL, $data = NULL)
	{
		if (is_array($text))
		{
			foreach ($text as $message)
			{
				// Recursively set each message
				RD::set($type, $message);
			}

			return;
		}

		if ($values != null)
		{
			$text = __($text, $values);
		}

		if($data === true)
		{
			$data = $values;
		}

		self::$_msg[] = array(
			'type' => $type,
			'value' => $text,
			'data' => $data,
		);
	}

	public static function set_array($type, $data) {
		self::$_msg[] = array(
			'type' => $type,
			'value' => null,
			'data' => $data
		);
	}

	/**
	 * Set a new message using the `messages/RD` file.
	 *
	 *     // The array path to the message
	 *     RD::error('user.login.error');
	 *
	 *     // Embed some values
	 *     RD::success('user.login.success', array($username));
	 *
	 * @param  string  $type  message type (e.g. RD::SUCCESS)
	 * @param  array   $arg   remaining parameters
	 * @uses   __()
	 */
	public static function __callStatic($type, $arg)
	{
		RD::set($type, $arg[0], Arr::get($arg, 1), Arr::get($arg, 2));
	}

	/**
	 * Get messages.
	 *
	 *     $messages = RD::get();
	 *
	 *     // Get error messages
	 *     $error_messages = RD::get(RD::ERROR);
	 *
	 *     // Get error AND alert messages
	 *     $messages = RD::get(array(RD::ERROR, RD::ALERT));
	 *
	 *     // Get everything except error AND alert messages
	 *     $messages = RD::get(array(1 => array(RD::ERROR, RD::ALERT)));
	 *
	 *     // Customize the default value
	 *     $error_messages = RD::get(RD::ERROR, 'my default value');
	 *
	 * @param   mixed  $type         message type (e.g. RD::SUCCESS, array(RD::ERROR, RD::ALERT))
	 * @param   mixed  $default      default value to return
	 * @param   bool   $delete       delete the messages?
	 * @param   bool   $return_array also return messages that were stored as arrays?
	 * @return  mixed
	 */
	public static function get($type = NULL, $default = NULL, $delete = FALSE, $return_array=true)
	{
		// Load existing messages
		$messages = Session::instance()->get(RD::$storage_key);

		if ($messages === NULL && count(self::$_msg) == 0)
		{
			// No messages found
			if($return_array == true)
				return $default;
			else
			{
				$output = array();

				foreach(self::$_msg as $msg) {
					if(!is_array($msg['value']))
						$output[] = $msg;
				}

				return $output;
			}
		}

		if ($type !== NULL)
		{
			// Will hold the filtered set of messages to return
			$return = array();

			// Store the remainder in case `delete` OR `get_once` is called
			$remainder = array();

			foreach ($messages as $message)
			{
				if (($message['type'] === $type)
					OR (is_array($type) AND in_array($message['type'], $type))
					OR (is_array($type) AND Arr::is_assoc($type) AND !in_array($message['type'], $type[1]))
				)
				{
					if($return_array == true)
						$return[] = $message;
					else if($return_array == false && !is_array($message))
						$return[] = $message;
					else
						$remainder[] = $message;
				}
				else
				{
					$remainder[] = $message;
				}
			}

			if (empty($return))
			{
				// No messages of '$type' to return
				return $default;
			}

			$messages = $return;
		}

		if ($delete === TRUE)
		{
			if ($type === NULL OR empty($remainder))
			{
				// Nothing to save, delete the key from memory
				RD::delete();
			}
			else
			{
				// Override messages with the remainder to simulate a deletion
				Session::instance()->set(RD::$storage_key, $remainder);
			}
		}

		return $messages;
	}

	/**
	 * Get messages once.
	 *
	 *     $messages = RD::get_once();
	 *
	 *     // Get error messages
	 *     $error_messages = RD::get_once(RD::ERROR);
	 *
	 *     // Get error AND alert messages
	 *     $error_messages = RD::get_once(array(RD::ERROR, RD::ALERT));
	 *
	 *     // Get everything except error AND alert messages
	 *     $messages = RD::get_once(array(1 => array(RD::ERROR, RD::ALERT)));
	 *
	 *     // Customize the default value
	 *     $error_messages = RD::get_once(RD::ERROR, 'my default value');
	 *
	 * @param   mixed  $type     message type (e.g. RD::SUCCESS, array(RD::ERROR, RD::ALERT))
	 * @param   mixed  $default  default value to return
	 * @return  mixed
	 */
	public static function get_once($type = NULL, $default = NULL, $return_array=true)
	{
		return RD::get($type, $default, TRUE, $return_array);
	}

	/**
	 * Get messages that haven't been stored yet.
	 *
	 * @param string|null $type Which type of messages should be returned
	 * @return array
	 */
	public static function get_current($type = NULL) {
		$output = array();
		if($type == null) {
			$output = self::$_msg;
		}
		else if(is_array($type))
		{
			foreach(self::$_msg as $msg) {
				if(in_array($msg['type'], $type))
					$output[] = $msg;
			}
		}
		else {
			foreach(self::$_msg as $msg) {
				if($msg['type'] == $type)
					$output[] = $msg;
			}
		}

		if(count($output) > 0)
			return $output;

		return null;
	}

	/**
	 * Delete messages.
	 *
	 *     RD::delete();
	 *
	 *     // Delete error messages
	 *     RD::delete(RD::ERROR);
	 *
	 *     // Delete error AND alert messages
	 *     RD::delete(array(RD::ERROR, RD::ALERT));
	 *
	 * @param  mixed  $type  message type (e.g. RD::SUCCESS, array(RD::ERROR, RD::ALERT))
	 */
	public static function delete($type = NULL)
	{
		if ($type === NULL)
		{
			// Delete everything!
			Session::instance()->delete(RD::$storage_key);
		}
		else
		{
			// Deletion by type happens in get(), too weird?
			RD::get($type, NULL, TRUE);
		}
	}

	/**
	 * Check if there were any messages added to the current request
	 * @return bool
	 */
	public static function has_messages() {
		return (count(self::$_msg) > 0);
	}

	/**
	 * Store the messages that have been created.
	 *
	 * @var bool $keep_alive Previously stored messages will be lost if false
	 */
	public static function persist($keep_alive=true) {
		$msg = self::$_msg;

		if($keep_alive == true)
		{
			$msg = array_merge(Session::instance()->get(RD::$storage_key, array()), $msg);
		}

		// Store the updated messages
		if(count($msg) > 0)
		{
			Session::instance()->set(RD::$storage_key, $msg);
		}

	}

} // End RD
