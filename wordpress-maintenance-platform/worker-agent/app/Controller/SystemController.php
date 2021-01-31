<?php
namespace App\Controller;


use App\Controller;
use App\Utils\Arr;
use App\Utils\Collection;
use App\Utils\Str;
use App\Utils\Wp;


class SystemController extends Controller
{

	public function phpinfo()
	{
		ob_start();
		phpinfo();

		return ob_get_clean();
	}


	public function info()
	{
		$result = array(
			'php_version' => phpversion(),
			'$server' => $this->getRequest()->server->all(),
		);

		return $result;
	}


	public function wpinfo()
	{
		Wp::constants();

		$result['php_version']  = phpversion();
		$result['wp_version']   = Wp::version(true);
		$result['wp_constants'] = Wp::constants();

		$wp_path = '';

		// Sometimes ABSPATH completely differs from DOCUMENT_ROOT
		// So we need to check if they have the same root
		if (strpos(ABSPATH, $doc_root = $this->getRequest()->server->get('DOCUMENT_ROOT')) !== false) {
			$wp_path = ltrim(strtr(ABSPATH, array($doc_root => '')), '/\\');
		}

		$result['wp_path'] = $wp_path;


		if ($this->getRequest('packages')) {
			$result['wp_plugins'] = Wp::plugins();
			$result['wp_themes']  = Wp::themes();

			if ($this->getRequest('active')) {
				$active = Wp::db()->get_results('SELECT option_name, option_value FROM ' . $GLOBALS['table_prefix'] . 'options WHERE option_name IN ("active_plugins", "stylesheet")');
				if (is_array($active)) {
					foreach ($active as $_row) {
						$option = $_row->option_name;
						$active = $_row->option_value;
						if ($option == 'active_plugins') {
							$active = unserialize($active);
							foreach ($active as $pluginPath) {
								$result['wp_plugins'][$pluginPath]['active'] = true;
							}
						}
						if ($option == 'stylesheet') {
							$result['wp_themes'][$active]['active'] = true;
						}
					}
				}
			}
		}

		return $result;
	}

}