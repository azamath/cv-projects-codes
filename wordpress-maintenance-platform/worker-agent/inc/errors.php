<?php

// Turn off errors due to json answer
error_reporting(0);
ini_set('display_errors', 'Off');

// Send result as json if app suddenly stops
register_shutdown_function('worker_shutdown_error_handler');

function worker_shutdown_error_handler()
{
	if (headers_sent()) {
		return;
	}

	if (is_file(WORKER_LOC_FILE)) {
		@unlink(WORKER_LOC_FILE);
	}

	$error = error_get_last();

	if ($error !== null && $error['type'] & ~E_NOTICE & ~E_DEPRECATED) {
		$code    = $error["type"];
		$file    = str_replace(realpath(__DIR__ . '/../../') . '/', '', $error["file"]) . ':' . $error["line"];
		$message = $error["message"];

		$result  = array(
			'errors'  => array(compact('message', 'file', 'code', 'backtrace')),
			'handler' => 'shutdown',
		);

		header('HTTP/1.1 500 Internal Server Error', true);
		header('Content-Type: application/json', true);
		header('WPS-Memory: ' . memory_get_usage(), true);
		header('WPS-Memory-Peak: ' . memory_get_peak_usage(), true);

		echo json_encode($result);
	}
}


// Provide uninterrupted execution
@set_time_limit(0);
@ignore_user_abort(true);
