<?php

if (version_compare(phpversion(), '5.3.0', '<') === true) {
	header('HTTP/1.1 500 Internal Server Error', true);
	echo  json_encode(array(
		'error' => 'php_version',
		'php_version' => phpversion(),
	));
	exit;
}


require_once __DIR__ . '/inc/bootstrap.php';

// Handle request and send response
\App\kernel()->handle(Symfony\Component\HttpFoundation\Request::createFromGlobals())->send();
