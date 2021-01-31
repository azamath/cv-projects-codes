<?php
namespace App\Controller;


use App\Controller;


class UpgradeController extends Controller
{

	public function core()
	{
		$current = file_get_contents(WORKER_UPGRADE_URL . 'version');

		if (!$this->getRequest('force') && ($current && version_compare($current, WORKER_VERSION, '<='))) {
			return array('uptodate' => true);
		}

		require_once $this->getRequest('wp_root') . '/wp-admin/includes/admin.php';
		require_once $this->getRequest('wp_root') . '/wp-admin/includes/class-wp-upgrader.php';

		$upgrade = new \Plugin_Upgrader(new \Automatic_Upgrader_Skin());
		$result  = (array) $upgrade->run(array(
			'package'                     => WORKER_UPGRADE_URL . 'worker-' . $current . '.zip',
			'destination'                 => WORKER_DIR,
			'clear_destination'           => false,
			'abort_if_destination_exists' => false,
			'clear_working'               => true,
			'clear_update_cache'          => false,
		));

		return compact('result');
	}
}