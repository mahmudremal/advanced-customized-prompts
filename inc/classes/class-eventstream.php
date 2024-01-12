<?php
/**
 * LoadmorePosts
 *
 * @package SOSPopsProject
 */
namespace SOSPOPSPROJECT\inc;
use SOSPOPSPROJECT\inc\Traits\Singleton;
use \WP_Query;use \WP_Error;
class Eventstream {
	use Singleton;
	private $headerSent;
	private $stored_path;
	protected function __construct() {
		// Defining global value
		$this->headerSent = false;
		// load class.
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		
		$this->stored_path = SOSPOPSPROJECT_DIR_PATH . '/assets/temp/sospopsproject_event_stream.json';
		/**
		 * Actions
		 */
		add_action('sospopsproject/event/stream/init', [$this, 'init_event_stream'], 10, 1);
		add_action('sospopsproject/event/stream/send', [$this, 'send_event_stream'], 10, 1);
		add_action('sospopsproject/event/stream/break', [$this, 'break_event_stream'], 10, 1);
		add_action('sospopsproject/event/stream/close', [$this, 'close_event_stream'], 10, 1);


		add_filter('sospopsproject/event/stream/register', [$this, 'register_event_stream'], 10, 1);
		add_filter('sospopsproject/event/stream/get', [$this, 'get_event_stream'], 10, 1);
		add_filter('sospopsproject/event/stream/trash', [$this, 'trash_event_stream'], 10, 1);
		
		add_action('rest_api_init', [$this, 'rest_api_init'], 10, 1);
		
	}
	public function rest_api_init($rest_server) {
		register_rest_route('sospopsproject/event', '/stream/run', [
			'methods'	=> 'GET',
			'callback'	=> [$this, 'run_event_stream'],
		]);
	}
	public function before_event_stream($args) {
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache');
		$this->headerSent = true;
	}
	public function after_event_stream($args) {
		// ob_flush();
		flush();sleep(1);
	}
	public function init_event_stream($args) {
		$serverTime = time();
		$args['startsat'] = 'Server starting time: ' . date("h:i:s", $serverTime);
		if (is_array($args)) {$args['hook'] = ['started_event'];}
		// $this->send_event_stream($args);
	}
	public function send_event_stream($message) {
		if ($this->headerSent === false) {
			$this->before_event_stream($args);
		}
		
		$event_id = uniqid();
		if (is_array($message) || is_object($message)) {
			$message = json_encode($message);
		}

		echo "id: $event_id" . PHP_EOL;
		echo "data: $message" . PHP_EOL;
		echo PHP_EOL;

	}
	public function break_event_stream($message) {
		// $this->send_event_stream($message);

		$this->after_event_stream($args);
	}
	public function close_event_stream($message) {
		if (is_array($message)) {$message['hook'] = ['close_event'];} else {$message = 'close_event';}
		$this->send_event_stream($message);

		$this->after_event_stream($args);
	}

	public function get_event_stream($default) {
		$fileContents = file_get_contents($this->stored_path);
		return maybe_unserialize($fileContents);
		// return get_option('sospopsproject_event_stream', $default);
	}
	public function register_event_stream($data) {
		$streamFile = fopen($this->stored_path, "w") or wp_die("Unable to open file!");
		fwrite($streamFile, maybe_serialize($data));
		fclose($streamFile);
		// update_option('sospopsproject_event_stream', $data);
		return true;
	}
	public function trash_event_stream($data) {
		unlink($this->stored_path);
		// delete_option('sospopsproject_event_stream');
		return true;
	}
	public function run_event_stream($request) {
		$data = (array) apply_filters('sospopsproject/event/stream/get', []);
		if (is_array($data) && isset($data['hook'])) {
			do_action($data['hook'], $data);
		} else {
			do_action('sospopsproject/event/stream/init', [
				'message'	=> 'Connected',
				'status'	=> true,
				'total'		=> 0
			]);
			do_action('sospopsproject/event/stream/send', [
				'message'	=> 'Failed',
				'type'		=> 'failed',
				'total'		=> 0,
				'done'		=> 0
			]);
			do_action('sospopsproject/event/stream/close', [
				'message'	=> 'Finished',
				'type'		=> 'finish',
				'status'	=> true
			]);
		}
	}
}