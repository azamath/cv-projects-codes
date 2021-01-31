<?php
namespace App\Controller;


use App\Controller;
use App\Utils\Arr;
use App\Utils\Str;
use App\Utils\Wp;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;


class ScanController extends Controller
{
	
	protected $result = array();

	/**
	 * Log identifier
	 * @var string
	 */
	protected $log = null;

	/**
	 * Log file handler
	 * @var resource
	 */
	protected $log_file;


	public function __construct()
	{
		if ($this->log = $this->getRequest('log')) {
			$file = WORKER_RUNTIME_DIR . sprintf('/scan-%s.log', $this->log);
			$this->result['log_file'] = $file;

			$this->log_file = fopen($file, 'a');

			if (!$this->log_file) {
				$this->log = false;
			}
		}
	}


	function __destruct()
	{
		if ($this->log_file) {
			fclose($this->log_file);
		}
	}


	public function integrity()
	{
		Wp::constants();

		$signature_wp = $this->getSignature('wordpress');
		$signature_pl = $this->getSignature('plugins');

		$finder = Finder::create()->files()->in(rtrim(ABSPATH, '/'))->ignoreUnreadableDirs();

		foreach (array_diff(array_keys($signature_wp), array('wp-content/')) as $item) {
			$finder->path('/^' . preg_quote(trim($item, '/')) . '/');
		}

		// Append plugins dir iterator.
		$finder->append(Finder::create()->ignoreUnreadableDirs()->files()->in(WP_PLUGIN_DIR));

		// Append themes dir iterator
		$finder->append(Finder::create()->ignoreUnreadableDirs()->files()->in(WP_CONTENT_DIR . '/themes'));

		/** @var SplFileInfo $file */
		foreach ($finder as $file) {
			// Check plugins integrity
			if (strpos($file, WP_PLUGIN_DIR) !== false && dirname($file) != rtrim(WP_PLUGIN_DIR, '/')) {
				$this->integrityCheck($file, $signature_pl, WP_PLUGIN_DIR);
			}
			// Check themes integrity
			elseif (strpos($file, WP_CONTENT_DIR . '/themes') !== false && dirname($file) != WP_CONTENT_DIR . '/themes') {
				//todo: use themes signatures
				$this->integrityCheck($file, array(), WP_CONTENT_DIR . '/themes');
			}
			// Check WordPress and themes integrity
			else {
				$this->integrityCheck($file, $signature_wp, ABSPATH);
			}
		}

		// Record skipped dirs
		foreach (Finder::create()
			->ignoreUnreadableDirs()
			->directories()
			->depth('==0')
			->in(rtrim(WP_CONTENT_DIR, '/'))
			->notPath('/^(plugins|themes)/') as $directory) {
			$this->result['skipped'][] = str_replace(WORKER_ROOT, '', $directory);
		}

		if (is_array($this->result['report']))
			$this->result['report'] = array_values($this->result['report']);

		return $this->result;
	}


	public function find()
	{
		$finder = Finder::create()->files()->ignoreUnreadableDirs();
		$finder->in($this->getRequest()->get('in', WORKER_ROOT));

		$args = array('exclude', 'path', 'not_path', 'name', 'not_name', 'size', 'date', 'depth');
		foreach ($args as $arg) {
			$method = lcfirst(join('', explode(' ', ucwords(str_replace('_', ' ', $arg)))));
			foreach ((array) $this->getRequest()->get($arg, array()) as $_val) {
				$finder->$method($_val);
			}
		}

		/** @var SplFileInfo $file */
		foreach ($finder as $file) {
			if ($ops = $this->getRequest()->get('op', 'report')) {
				$ops = !is_array($ops) ? explode(',', $ops) : $ops;
				foreach ($ops as $op) {
					$methodName = $op . 'File';
					if (method_exists($this, $methodName)) {
						$this->{$methodName}($file);
					}
				}
			}
		}

		if (is_array($this->result['report']))
			$this->result['report'] = array_values($this->result['report']);

		return $this->result;
	}


	protected function getSignature($module)
	{
		return json_decode(file_get_contents(WORKER_SIGNATURES_DIR . '/' . $module . '.json'), true);
	}


	/**
	 * @param SplFileInfo $file
	 * @param array $signature
	 * @param string $base_path
	 */
	protected function integrityCheck($file, $signature, $base_path = null)
	{
		$key = trim(strtr($file, array('//' => '/', $base_path => '')), '/');
		$md5 = Arr::get($signature, explode('###', str_replace('/', '/###', $key)));

		// If no signature or different
		if (md5_file($file) != $md5) {

			// If signature exists report as malformed
			if ($md5) {
				$this->reportFile($file, 'malformed');
			}

			if (!$this->shouldScan($file)) {
				$this->log($file, $md5 ? 'signature invalid' : 'skipping');
			}
			else {
				$this->log($file, $md5 ? 'signature invalid' : 'signature not found');
				$this->scanFile($file);
			}

			return;
		}

		$this->log($file, 'ok');

		$this->incrementFilesChecked();
	}


	/**
	 * @param SplFileInfo $file
	 */
	protected function scanFile($file)
	{
		if (!$this->shouldScan($file)) {
			return;
		}

		$lines    = array();
		$longs    = array();
		$search   = $this->getRequest()->get('keywords', array());
		$withCode = $this->getRequest()->get('with_code');

		try {
			$contents = $file->getContents();
		}
		catch (\RuntimeException $e) {
			$this->reportFile($file, 'error', $e->getMessage());

			return;
		}

		$long = $this->getRequest()->get('long');

		foreach (preg_split("/\r\n|\n|\r/", $contents) as $i => $line) {
			$no = $i + 1;

			foreach ($search as $word) {
				if (stripos($line, $word) !== false) {
					$lines[ $no ] = $withCode ? $this->trimLine($line) : $word;
				}
			}

			// Continue if no long line parameter set or line is reported
			if (!$long || isset($lines[ $no ])) {
				continue;
			}

			$skipLongLineCheck = false;

			// Skip long lines in the some paths
			foreach (array(
				'*/wp-content/cache/db/*',
				'*/wp-content/cache/object/*',
			) as $pattern) {
				if (Str::is($pattern, $file)) {
					$skipLongLineCheck = true;
					break;
				}
			}

			if (!$skipLongLineCheck && strlen($line) >= $long && !isset($lines[ $no ])) {
				$longs[ $no ] = $withCode ? $this->trimLine($line) : strlen($line);
			}
		}

		if (!empty($lines)) {
			$this->reportFile($file, 'keywords', $lines);
		}

		if (!empty($longs)) {
			$this->reportFile($file, 'longs', $longs);
		}

		$this->incrementFilesChecked();
	}


	/**
	 * @param SplFileInfo $file
	 *
	 * @return bool
	 */
	protected function shouldScan($file)
	{
		return in_array($file->getExtension(), array('php', 'php3', 'php4', 'php5', 'phtml'));
	}


	protected function incrementFilesChecked()
	{
		if (!isset($this->result['files_checked']))
			$this->result['files_checked'] = 0;

		$this->result['files_checked']++;
	}


	/**
	 * @param SplFileInfo $file
	 * @param string $key
	 * @param mixed $data
	 */
	protected function reportFile($file, $key = null, $data = true)
	{
		$path = str_replace(WORKER_ROOT . '/', '', $file);
		$file_key = md5($path);

		if (!isset($this->result['report'][ $file_key ])) {
			$this->result['report'][ $file_key ]['file'] = $path;
			$this->result['report'][ $file_key ]['size'] = $file->getSize();
			$this->result['report'][ $file_key ]['ctime'] = $file->getCTime();
			$this->result['report'][ $file_key ]['mtime'] = $file->getMTime();
		}

		if (!is_null($key)) {
			$this->result['report'][ $file_key ][ $key ] = $data;
		}
	}


	protected function log($file, $message)
	{
		if (!$this->log) {
			return;
		}

		fwrite($this->log_file, str_replace(WORKER_ROOT . '/', '', $file) . ' -- ' . $message . PHP_EOL);
	}


	protected function trimLine($str, $l = 32)
	{
		$str = trim($str);
		if (strlen($str) > $l) {
			$str = substr($str, 0, $l);
		}

		return $str;
	}

}