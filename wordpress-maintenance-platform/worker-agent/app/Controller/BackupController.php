<?php
namespace App\Controller;


use App\Controller;
use App\Utils\Arr;
use App\Utils\MySQLDump;
use App\Utils\Packer;
use App\Utils\SqlReader;
use App\Utils\Unpacker;
use App\Utils\Wp;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;


class BackupController extends Controller
{

	public function make()
	{
		$result  = array();
		$request = $this->getRequest();

		$target  = \App\abs_path($request->get('target'));
		$archive = \App\abs_path($request->get('archive'));
		$exclude = $request->get('exclude', array());
		$slicing = $request->get('slicing', -1);
		$targets = array($target => array(
			'exclude' => $exclude,
		));


		$packer = new Packer($targets, $archive);
		$fs     = new Filesystem();

		$packer->size_limit = $slicing != -1 ? $slicing * 1024 * 1024 : $slicing;
		$packer->base_dir   = WORKER_ROOT;

		if ($packer->run()) {
			foreach ($packer->files as $archive_file) {
				$fs->chmod($archive_file['name'], 0777);
				$archive_file['name'] = \App\relative_path($archive_file['name']);
				$result['files'][] = $archive_file;
			}
		}
		else {
			trigger_error($packer->error, E_USER_ERROR);
		}

		return $result;
	}

	
	public function create()
	{
		Wp::constants();

		$result   = array();
		$request  = $this->getRequest();
		$parts    = $request->get('parts', array('database', 'uploads', 'themes', 'plugins', 'languages', 'other'));
		$date     = strtotime($request->get('backup_date', gmdate('c')));
		$date_str = date('Y-m-d--H-i-s', $date);
		$fs       = new Filesystem();

		$backup_dir = $request->get('backup_dir', WP_CONTENT_DIR . '/backups/' . $date_str);
		$fs->mkdir($backup_dir);

		$_parts = array();

		if (in_array('database', $parts)) {
			$backup           = new MySQLDump();
			$backup->server   = DB_HOST;
			$backup->username = DB_USER;
			$backup->password = DB_PASSWORD;
			$backup->database = DB_NAME;
			$backup->charset  = DB_CHARSET;
			$backup->backup_dir = $backup_dir;
			$db_filename      = 'database.sql';

			if (!$backup->run(MySQLDump::RETURN_SAVE, $db_filename)) {
				throw new \Exception($backup->error);
			}

			$fs->chmod($db_filename, 0777);

			$_parts += array(
				'database' => array(
					'archive' => $backup_dir . '/database.zip',
					'targets' => array(
						$db_filename => basename($db_filename),
					),
				),
			);
		}

		$_parts += array(
			'themes'  => array(
				'archive' => $backup_dir . '/themes.zip',
				'targets' => array(
					WP_CONTENT_DIR . '/themes' => array(
						'exclude' => array('*.svn', '*.cvs', '*.git', '*.log', '*.cache', '*.back', '*.swp'),
					),
				),
			),
			'plugins' => array(
				'archive' => $backup_dir . '/plugins.zip',
				'targets' => array(
					WP_CONTENT_DIR . '/plugins' => array(
						'exclude' => array('*.svn', '*.cvs', '*.git', '*.log', '*.cache', '*.back', '*.swp'),
					),
					WP_CONTENT_DIR . '/mu-plugins' => array(
						'exclude' => array('*.svn', '*.cvs', '*.git', '*.log', '*.cache', '*.back', '*.swp'),
					),
				),
			),
			'languages' => array(
				'archive' => $backup_dir . '/languages.zip',
				'targets' => array(
					WP_CONTENT_DIR . '/languages' => array(
						'exclude' => array('*.svn', '*.cvs', '*.git', '*.log', '*.cache', '*.back', '*.swp'),
					),
				),
			),
			'other'   => array(
				'archive' => $backup_dir . '/other.zip',
				'targets' => array(
					$request->get('wp_root') . '/.htaccess'     => '.htaccess',
					$request->get('wp_root') . '/wp-config.php' => 'wp-config.php',
					WP_CONTENT_DIR => array(
						'exclude' => array('*.svn', '*.cvs', '*.git', '*.log', '*.cache', '*.back', '*.swp'),
						'current' => array(
							'exclude' => array(
								'languages',
								'mu-plugins',
								'plugins',
								'themes',
								'uploads',
								'upgrade',
								'*cache*',
								'*backup*',
								'updraft',
								'*log*',
								'managewp',
								'index.php',
							),
						),
					),
				),
			),
			'uploads' => array(
				'archive' => $backup_dir . '/uploads.zip',
				'targets' => array(
					WP_CONTENT_DIR . '/uploads' => array(
						'exclude' => array(
							'*.svn', '*.cvs', '*.git', '*.log', '*.cache', '*.back', '*.swp',
							'backup', 'backwpup*', 'log*', 'snapshots'
						),
					),
				),
			),
		);

		$max_execution_time = (int) ini_get('max_execution_time');

		foreach ($_parts as $part => $part_data) {

			if (!in_array($part, $parts)) {
				continue;
			}

			$time_limit = -1;
			if ($max_execution_time > 0) {
				$time_limit = $max_execution_time - (microtime(true) - WORKER_STARTED) - 3;
				if ($time_limit <= 0) {
					$result['time_limit_reached'] = $time_limit;
					break;
				}
				$result['time_limit'] = $time_limit;
			}

			$packer = new Packer($part_data['targets'], $part_data['archive']);

			$packer->base_dir   = WORKER_ROOT;
			$packer->size_limit = $request->get('slicing', -1);
			$packer->time_limit = $time_limit;
			$packer->max_file_size = $request->get('max_file_size', 50 * 1024 * 1024);

			if ($part == 'database') {
				$packer->max_file_size = -1;
			}

			if ($packer->run()) {
				foreach ($packer->files as $archive_file) {
					$result['parts'][] = array(
						'part'  => $part,
						'local' => \App\relative_path($archive_file['name']),
						'size'  => $archive_file['size'],
						'unpacked' => $archive_file['unpacked'],
					);
					$fs->chmod($archive_file['name'], 0777);
				}

				if ($packer->stopped_file) {
					$result['parts'][count($result['parts']) - 1]['stopped_file'] = \App\relative_path($packer->stopped_file);
				}
			}
			else {
				trigger_error($packer->error, E_USER_ERROR);
			}

			if ($part == 'database' && isset($db_filename)) {
				$fs->remove($db_filename);
			}
		}


		$php_version  = phpversion();
		$wp_version   = Wp::version(true);
		$wp_plugins   = Wp::plugins(false);
		$wp_themes    = Wp::themes(false);
		$wp_constants = Wp::constants();

		$manifest = $backup_dir . '/manifest.json';
		file_put_contents($manifest, json_encode(compact('php_version', 'wp_version', 'wp_plugins', 'wp_themes', 'wp_constants')));
		$result['manifest_file'] = \App\relative_path($manifest);

		return $result;
	}


	public function unpack()
	{
		$files  = array_filter((array) ($this->getRequest('files')));
		$dest   = \App\abs_path($this->getRequest('destination'));
		$result = array();

		foreach ($files as $file) {
			$archive = \App\abs_path($file);

			if (!is_file($archive) || is_dir($archive)) {
				continue;
			}

			$unpacker = new Unpacker($archive);

			$result[ $file ] = $unpacker->to($dest);
		}

		return $result;
	}


	public function maintenance()
	{
		$_maintenance = \App\abs_path('.maintenance');
		$fs = new Filesystem();

		if ($this->getRequest('release')) {
			$fs->remove($_maintenance);
		}
		else {
			$fs->dumpFile($_maintenance, '<?php $upgrading = ' . time() . ';');
		}

		return array('result' => true);
	}


	public function moveUnpacked()
	{
		$source = \App\abs_path($this->getRequest('source'));
		$dest   = \App\abs_path($this->getRequest('destination'));
		$fs     = new Filesystem();

		$result = array(
			'files' => array(),
			'errors' => 0,
		);

		if (!is_dir($source)) {
			return $result;
		}

		$finder   = new Finder();
		$entities = $finder->in($source)->depth(0)->ignoreDotFiles(false);

		/** @var SplFileInfo $file */
		foreach ($entities as $file) {
			$_file = $file->getRelativePathname();
			try {
				$target = $dest . '/' . $file->getFilename();

				if ($fs->exists($target)) {
					$fs->remove($target);
				}

				$fs->rename($file, $target);
				$result['files'][$_file] = true;
			}
			catch (\Exception $e) {
				$result['files'][$_file] = $e->getMessage();
				$result['errors'] ++;
				break;
			}
		}

		if (empty($result['errors'])) {
			$fs->remove($source);
		}

		return $result;
	}


	public function upload()
	{
		$result  = array();
		$request = $this->getRequest();
		$fs = new Filesystem();

		foreach (array('s3', 'file', 'target') as $key) {
			$_value = $request->get($key);
			if (is_array($_value) && empty($_value) || (!is_array($_value) && trim($_value) == '')) {
				throw new \Exception('Bad request. Missed required param: ' . $key);
			}
		}

		// Check for CURL
		if (!extension_loaded('curl')) {
			throw new \Exception("CURL extension not loaded");
		}

		$file = \App\abs_path($request->get('file'));

		if (!file_exists($file) || !is_file($file)) {
			throw new \Exception('File does not exists: ' . $file);
		}

		list($accessKey, $secretKey, $bucketName) = json_decode(base64_decode($request->get('s3')), true);
		$s3_path = $request->get('target');

		require_once WORKER_DIR . '/inc/s3.php';
		$s3 = new \S3($accessKey, $secretKey);
		\S3::setExceptions(true);

		if ($s3::putObjectFile($file, $bucketName, $s3_path, \S3::ACL_PRIVATE)) {
			$result['s3'] = $s3_path;
			if ($request->get('delete_local')) {
				$fs->remove($file);
				$dirname = dirname($file);
				if (!glob($dirname . '/*')) {
					rmdir($dirname);
				}
			}
		}

		return $result;
	}


	public function download()
	{
		$result = array();

		foreach (array('s3', 'destination', 'source') as $key) {
			$_value = $this->getRequest($key);
			if (is_array($_value) && empty($_value) || (!is_array($_value) && trim($_value) == '')) {
				throw new \Exception('Bad request. Missed required param: ' . $key);
			}
		}

		// Check for CURL
		if (!extension_loaded('curl')) {
			throw new \Exception("CURL extension not loaded");
		}

		list($accessKey, $secretKey, $bucketName) = json_decode(base64_decode($this->getRequest('s3')), true);
		$s3_path     = $this->getRequest('source');
		$filename    = $this->getRequest('destination');
		$destination = \App\abs_path($filename);

		if (!is_dir(dirname($destination))) {
			$fs = new Filesystem();
			$fs->mkdir(dirname($destination));
		}

		require_once WORKER_DIR . '/inc/s3.php';
		$s3 = new \S3($accessKey, $secretKey);
		\S3::setExceptions(true);

		if ($s3::getObject($bucketName, $s3_path, $destination)) {
			$result['destination'] = $filename;
		}

		return $result;
	}


	public function importSql()
	{
		$file = \App\abs_path($this->getRequest('file'));

		$statements = new SqlReader($file);

		if (is_array($db_config = $this->getRequest('db_config'))) {
			$db_host = Arr::get($db_config, 'host');
			$db_user = Arr::get($db_config, 'user');
			$db_pass = Arr::get($db_config, 'pass');
			$db_name = Arr::get($db_config, 'name');
			$db_char = Arr::get($db_config, 'charset');
		}
		else {
			WP::config($this->getRequest('wp_config') ? $this->getRequest('wp_config') : null);

			$db_host = DB_HOST;
			$db_user = DB_USER;
			$db_pass = DB_PASSWORD;
			$db_name = DB_NAME;
			$db_char = DB_CHARSET;
		}

		$db = \App\db();
		$db->hide_errors();
		$db->connect($db_user, $db_pass, $db_host);
		$db->select($db_name, $db_char);
		$db->query("set session sql_mode='NO_ENGINE_SUBSTITUTION'");
		$db->query("SET FOREIGN_KEY_CHECKS=0");

		foreach ($statements as $sql) {
			if ($db->query($sql) === false) {
				throw new \Exception('SQL error: ' . $db->last_error . ' in ' . $sql);
			}
		}

		return array('result' => true);
	}


	public function package()
	{
		Wp::constants();

		$path = ABSPATH;
		$package = trim($this->getRequest('package'), '/');
		$version = null;

		if ($this->getRequest('type') == 'plugin') {
			$path = WP_PLUGIN_DIR;
			$version = Arr::get(Arr::first(Wp::plugins(), function ($void, $p) use ($package) {
				return Arr::get($p, 'slug') == $package;
			}), 'version');
		}

		if ($this->getRequest('type') == 'theme') {
			$path = WP_CONTENT_DIR . '/themes';
			if (file_exists($path . '/' . $package . '/style.css')) {
				$meta = Wp::extractFileMeta($path . '/' . $package . '/style.css', array('version' => 'Version'));
				$version = Arr::get($meta, 'version');
			}
		}

		$temp = WP_CONTENT_DIR . '/upgrade/' . basename($package) . ($version ? '.' . $version : '') . '.zip';
		$packer = new Packer(array($path . '/' . $package => array()), $temp);
		$packer->base_dir = $path;

		if ($packer->run()) {
			$response = new BinaryFileResponse($temp, 200, array(), true, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
			$response->deleteFileAfterSend(true);
			$response->prepare($this->getRequest());

			return $response;
		}

		return array(
			'error' => $packer->error,
		);
	}


	public function cleanup()
	{
		Wp::constants();

		$fs  = new Filesystem();
		$req = $this->getRequest();

		if (!$req->get('backup_date')) {
			return array('error' => 'no_date');
		}

		$dir = WP_CONTENT_DIR . '/backups/' . date('Y-m-d--H-i-s', strtotime($req->get('backup_date')));
		$fs->remove($dir);

		return array('result' => !file_exists($dir));
	}


}