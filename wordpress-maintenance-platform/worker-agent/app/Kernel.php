<?php
namespace App;


use phpseclib\Crypt\RSA;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class Kernel
{

	const HEADER_SIGNATURE_NAME = 'WPS-Signature';

	/** @var Kernel */
	static public $instance;

	/** @var \Symfony\Component\Filesystem\Filesystem */
	public $fs;

	/** @var Request */
	protected $request;

	/** @var array */
	protected $errors = array();

	/** @var array */
	protected $exceptions = array();


	public function __construct()
	{
		// Errors log to json answer
		set_error_handler(array($this, 'errorHandler'), E_ALL & ~E_NOTICE & ~E_DEPRECATED);

		// Exceptions log to json answer
		set_exception_handler(array($this, 'exceptionHandler'));

		$this->fs = new Filesystem();

		static::$instance = $this;
	}


	public function handle(Request $request)
	{
		$this->request = $request;

		$request->attributes->set('started', WORKER_STARTED);

		$route = $request->get('r');
		if (!$route) {
			return $this->createResponse(array('version' => WORKER_VERSION));
		}

		/*if ($_response = $this->lock($request)) {
			return $_response;
		}*/

		$middleware = array('wpRoot', 'validateRequest', 'tokenizeRequest', 'dispatch');

		try {
			foreach ($middleware as $fn) {
				$_response = call_user_func(array($this, $fn), $request);
				if ($_response !== null) {
					$response = $_response;
					break;
				}
			}
		}
		catch (\Exception $e) {
			$response = $this->createExceptionResponse($e);
		}

		$this->unlock();

		if (isset($response)) {
			return $this->createResponse($response);
		}

		return $this->createResponse('', 204);
	}


	/**
	 * Locking process
	 *
	 * @param Request $request
	 * @return null|\Symfony\Component\HttpFoundation\Response
	 */
	protected function lock($request)
	{
		if (is_file(WORKER_LOC_FILE)) {
			if (time() - (int) file_get_contents(WORKER_LOC_FILE) < $request->get('lock_timeout', 60)) {
				return $this->createResponse(array('locked' => true), 503);
			}
		}

		try {
			@$this->fs->dumpFile(WORKER_LOC_FILE, time());
			@$this->fs->chmod(WORKER_LOC_FILE, 0777);
		}
		catch (\Exception $e) {}

		return null;
	}


	/**
	 * Unlocking
	 */
	public function unlock()
	{
		try {
			if (is_file(WORKER_LOC_FILE)) {
				@$this->fs->remove(WORKER_LOC_FILE);
			}
		}
		catch (\Exception $e) {}
	}


	/**
	 * Start finding WordPress installation base
	 *
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function wpRoot($request)
	{
		$wp_root = wp_root($request->get('wp_dir', WORKER_ROOT));
		$request->attributes->set('wp_root', $wp_root);

		return null;
	}


	/**
	 * @param Request $request
	 *
	 * @return bool|\Symfony\Component\HttpFoundation\Response
	 */
	protected function validateRequest($request)
	{
		if ($token = $request->get('token')) {
			$tokenFile = WORKER_STORAGE_DIR . '/runtime/tokens/' . $token;

			if ($this->fs->exists($tokenFile)) {
				$expires = file_get_contents($tokenFile);
				$this->fs->remove($tokenFile);
				if (time() < $expires) {
					return null;
				}

				return $this->createResponse(array('error' => 'token_expired'), 401);
			}
		}

		$signature = $request->headers->get(self::HEADER_SIGNATURE_NAME);

		if (!$signature) {
			return $this->createResponse(array('error' => 'signature_not_found'), 401);
		}

		$keyName = strstr($signature, '/', true);
		$keyFile = WORKER_KEYS_DIR . '/' . $keyName . '.pub';

		if (!file_exists($keyFile)) {
			return $this->createResponse(array('error' => 'signature_key_not_found'), 401);
		}

		$signature = substr(strstr($signature, '/'), 1);
		$signature = base64_decode($signature);

		$query = $request->query->all();
		$post  = $request->request->all();
		ksort($query);
		ksort($post);
		array_walk_recursive($post, function (&$data) {
			$data = (string) $data;
		});
		$query = json_encode($query);
		$post  = json_encode($post);

		$rsa = new RSA();
		$rsa->loadKey(file_get_contents($keyFile));

		if (!$rsa->verify($query . $post, $signature)) {
			return $this->createResponse(array('error' => 'signature_invalid', 'request' => $query . $post), 401);
		}

		return null;
	}


	/**
	 * Create redirect to the same request with one time token
	 *
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function tokenizeRequest($request)
	{
		if (($tokenize = $request->get('tokenize')) && $request->isMethod('GET')) {

			$token = md5(time() . rand());
			$expires = time() + 60;
			$this->fs->dumpFile(WORKER_STORAGE_DIR . '/runtime/tokens/' . $token, $expires);

			$uri = str_replace('tokenize=' . $tokenize, 'token=' . $token, $request->getUri());

			return new RedirectResponse($uri);
		}

		return null;
	}


	/**
	 * Dispatch request to controller action
	 *
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function dispatch($request)
	{
		$route      = array_filter(explode('.', $request->get('r')));
		$controller = array_shift($route);
		$action     = array_shift($route);

		if (!$action) {
			$action = 'index';
		}

		$controllerClass = 'App\\Controller\\' . ucfirst($controller) . 'Controller';

		if (!class_exists($controllerClass)) {
			return $this->routeNotFound();
		}

		/** @var Controller $controller */
		$controller = new $controllerClass;

		if (!method_exists($controller, $action)) {
			return $this->routeNotFound();
		}

		/*$response = $this->validateRequest($request);
		if ($response instanceof Response) {
			return $response;
		}*/

		return call_user_func(array($controller, $action));
	}


	/**
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}


	/**
	 * @return Request
	 */
	public function getRequest()
	{
		return $this->request;
	}


	/**
	 * Application error handler
	 *
	 * @param $code
	 * @param $message
	 * @param $file
	 * @param $line
	 *
	 * @return bool
	 */
	public function errorHandler($code, $message, $file, $line)
	{
		$ignoreMessages = array(
			'Invalid signature',
		);

		foreach ($ignoreMessages as $m) {
			if ($m == $message) {
				return false;
			}
		}

		if (defined('WP_DEBUG') && WP_DEBUG) {
			$backtrace = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1);
		}

		$file .= ':' . $line;
		$this->errors[] = compact('message', 'code', 'file', 'backtrace');

		return false;
	}


	/**
	 * Main exception handler
	 *
	 * @param \Throwable $e
	 */
	public function exceptionHandler($e)
	{
		$this->unlock();
		$this->sendResponse($this->createExceptionResponse($e));
	}


	/**
	 * @param \Throwable $e
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function createExceptionResponse($e)
	{
		return $this->createResponse(array(
			'handler'   => 'app',
			'exception' => array(
				'message' => $e->getMessage(),
				'file'    => $e->getFile() . ':' . $e->getLine(),
				'class'   => get_class($e),
				'code'    => $e->getCode(),
			),
		), 500);
	}


	/**
	 * Create Response from mixed content
	 *
	 * @param mixed $response
	 * @param int $status
	 * @param array $headers
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function createResponse($response, $status = null, $headers = null)
	{
		if (is_array($response)) {

			if (!empty($this->exceptions)) {
				$response['exceptions'] = $this->exceptions;
			}

			if (!empty($this->errors)) {
				$response['errors']  = $this->errors;
				$response['handler'] = 'app';
			}

			$response = new JsonResponse($response);
		}

		if (!($response instanceof Response)) {
			$response = new Response($response);
		}

		if (!is_null($status)) {
			$response->setStatusCode($status);
		}

		if (is_array($headers)) {
			$response->headers->add($headers);
		}

		$headers = array(
			'WPS-Memory' => memory_get_usage(),
			'WPS-Memory-Peak' => memory_get_peak_usage(),
		);
		if ($started = $this->request->attributes->get('started')) {
			$headers['WPS-Execution-Time'] = microtime(true) - $started;
		}

		$response->headers->add($headers);

		return $response;
	}


	protected function sendResponse($response, $status = null, $headers = null)
	{
		return $this->createResponse($response, $status, $headers)->send();
	}


	protected function routeNotFound()
	{
		return $this->createResponse(array('error' => 'route_not_found'));
	}

}