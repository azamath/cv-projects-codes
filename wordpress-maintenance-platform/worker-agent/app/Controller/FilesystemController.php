<?php
namespace App\Controller;


use App\Controller;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;


class FilesystemController extends Controller
{
	protected $fs;


	public function __construct()
	{
		$this->fs = new Filesystem();
	}


	public function get()
	{
		$path = \App\abs_path($this->getRequest('file'));

		if (!is_readable($path)) {
			throw new \Exception('File is not available');
		}

		readfile($path);
		exit;
	}


	public function put()
	{
		$path = $this->getRequest('path');

		if (empty($path) || !is_string($path)) {
			return $this->json(array('error' => 'bad_path'), 400);
		}

		if (($file = $this->getRequest()->files->get('file')) instanceof UploadedFile
			&& $file->getError() == UPLOAD_ERR_OK) {
			$this->fs->dumpFile($this->normalizePath($path), fopen($file->getPathname(), 'r'));
			return $this->json(array('file' => $path));
		}

		return $this->json(array('error' => 'no_file'), 400);
	}


	public function rename()
	{
		$source = \App\abs_path($this->getRequest('source'));
		$dest   = \App\abs_path($this->getRequest('destination'));
		$fs     = new Filesystem();

		$result = array(
			'moved' => array(),
			'errors' => array(),
		);

		$finder = new Finder();

		if (is_dir($source)) {
			$entities = $finder->in($source)->depth(0)->ignoreDotFiles(false);
		}
		elseif (file_exists($source)) {
			$entities = $finder->name($source)->ignoreDotFiles(false);
		}
		else {
			$result['errors'][] = 'Source does not exist';

			return $result;
		}

		/** @var SplFileInfo $file */
		foreach ($entities as $file) {
			try {
				$target = $dest . '/' . $file->getFilename();

				if ($fs->exists($target)) {
					$fs->remove($target);
				}

				$fs->rename($file, $target);
				$result['moved'][] = $file->getRelativePathname();
			}
			catch (\Exception $e) {
				$result['errors'][$file] = $e->getMessage();
				break;
			}
		}

		if (empty($result['errors']) && is_dir($source)) {
			$fs->remove($source);
		}

		return $result;
	}


	public function delete()
	{
		$files = (array) $this->getRequest('files');

		if ($in = $this->getRequest('in')) {
			$files = Finder::create()->in(\App\abs_path($in))->depth(0);
		}

		$deleted = array();
		foreach ($files as $file) {
			try {
				$_file = $this->normalizePath($file);
				if (!file_exists($_file) && !is_link($_file)) {
					continue;
				}
				$this->fs->remove($_file);
				$deleted[] = $file instanceof SplFileInfo ? $file->getRelativePathname() : $file;
			}
			catch (IOException $e) {
				continue;
			}
		}

		return $this->json(array('deleted' => $deleted));
	}


	public function download()
	{
		$url = $this->getRequest('url');
		$file = \App\abs_path($this->getRequest('file'));

		$this->fs->mkdir(dirname($file));

		if (!($h = fopen($file, 'w+'))) {
			return array('error' => 'Can write to: ' . $file);
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_FILE, $h);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_exec($ch);
		curl_close($ch);

		fclose($h);

		return array(
			'filesize' => filesize($file),
		);
	}


	protected function normalizePath($path)
	{
		if (!$this->fs->isAbsolutePath($path)) {
			$path = $this->getRequest('wp_root') . '/' . $path;
		}

		return $path;
	}
}