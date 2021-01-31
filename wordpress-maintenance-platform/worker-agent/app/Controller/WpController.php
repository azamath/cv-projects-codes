<?php
namespace App\Controller;


use App\Controller;
use App\Utils\Arr;
use App\Utils\Wp;
use Symfony\Component\HttpFoundation\File\UploadedFile;


class WpController extends Controller
{

	public function updates()
	{
		$options = array();

		if ($this->hasRequest('force')) {
			$options['force'] = (bool) $this->getRequest('force');
		}

		if ($this->hasRequest('parts')) {
			$options['parts'] = (array) $this->getRequest('parts');
		}

		return Wp::updates($options);
	}


	public function packageInstall()
	{
		$this->includeUpgrader();

		$destinations = array(
			'plugin' => WP_PLUGIN_DIR,
			'theme'  => get_theme_root(),
		);

		if (!array_key_exists($this->getRequest('type'), $destinations)) {
			return array(
				'error' => 'wrong_type'
			);
		}

		$package  = $this->getRequest('package');
		/** @var UploadedFile $file */
		if ($file = $this->getRequest()->files->get('package')) {
			$package = (string) $file->getPathname();
		}

		$upgrade = new \Plugin_Upgrader(new \Automatic_Upgrader_Skin());

		return (array) $upgrade->run(array(
			'package'                     => $package,
			'destination'                 => Arr::get($destinations, $this->getRequest('type')),
			'clear_destination'           => $this->getRequest('clear_destination', true),
			'abort_if_destination_exists' => $this->getRequest('abort_if_destination_exists', false),
			'clear_working'               => $this->getRequest('clear_working', true),
			'clear_update_cache'          => $this->getRequest('clear_update_cache', false),
		));
	}


	public function packageUpdate()
	{
		$this->includeUpgrader();

		if ($this->getRequest('plugin')) {
			$upgrade = new \Plugin_Upgrader(new \Automatic_Upgrader_Skin());
			return (array) $upgrade->bulk_upgrade((array) $this->getRequest('plugin', array()), array(
				'clear_update_cache' => $this->getRequest('clear_update_cache', false),
			));
		}

		if ($this->getRequest('theme')) {
			$upgrade = new \Theme_Upgrader(new \Automatic_Upgrader_Skin());
			return (array) $upgrade->bulk_upgrade((array) $this->getRequest('theme', array()), array(
				'clear_update_cache' => $this->getRequest('clear_update_cache', false),
			));
		}

		return $this->json(array('error' => 'No packages provided'), 400);
	}


	public function migrateUrl()
	{
		$old = rtrim($this->getRequest('from'), '/') . '/';
		$new = rtrim($this->getRequest('to'),  '/') . '/';

		$result = array();

		$wpdb = Wp::db();
		$tables = $wpdb->get_col('SHOW TABLES');

		$sites = 1;

		if (in_array($wpdb->blogs, $tables)) {

			$result[] = 'Sites: ' . ( $sites = intval( $wpdb->get_var( "SELECT count(*) FROM {$wpdb->blogs}" ) ) );

			$result[] = array(
				$query = $wpdb->prepare( "UPDATE {$wpdb->blogs} SET `domain` = REPLACE(`domain`, '%1\$s', '%2\$s')", $old, $new ),
				'Affected: ' . $wpdb->query( $query )
			);

			$result[] = array(
				$query = $wpdb->prepare( "UPDATE {$wpdb->site} SET `domain` = REPLACE(`domain`, '%1\$s', '%2\$s')", $old, $new ),
				'Affected: ' . $wpdb->query( $query ),
			);

			$result[] = array(
				$query = $wpdb->prepare( "UPDATE {$wpdb->sitemeta} SET meta_value = REPLACE(meta_value, '%s', '%s') WHERE LEFT(meta_value,2) <> 'a:'", $old, $new ),
				'Affected: ' . $wpdb->query( $query ),
			);

			$rows = $wpdb->get_results("SELECT * FROM {$wpdb->sitemeta} WHERE LEFT(meta_value, 2) = 'a:'");
			foreach ($rows as $row) {
				$value = maybe_unserialize($row->meta_value);
				if (is_array($value)) {
					array_walk_recursive($value, function (&$value) use ($old, $new) {
						$value = is_scalar($value) ? str_replace($old, $new, $value) : $value;
					});
					$value = serialize($value);
					if ($value !== $row->meta_value) {
						$result[] = array(
							"{$wpdb->sitemeta} (site {$row->site_id}, {$row->meta_key}): ",
							$wpdb->update($wpdb->sitemeta, array(
								'meta_value' => $value,
							), array(
								'meta_id' => $row->meta_id
							)),
						);
					}
				}
			}

		}

		$prefix = $wpdb->prefix;

		for ($blog_id = 1; $blog_id <= $sites; $blog_id ++) {
			if ( $blog_id > 1 ) {
				$wpdb->set_prefix($prefix . $blog_id . '_');
			}

			$result[] = array(
				$query = $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = REPLACE(option_value, '%s', '%s') WHERE LEFT(option_value,2) <> 'a:'", $old, $new ),
				'Affected: ' . $wpdb->query( $query ),
			);

			$result[] = array(
				$query = $wpdb->prepare( "UPDATE {$wpdb->posts} SET guid = REPLACE(guid, '%1\$s', '%2\$s'), post_content = REPLACE(post_content, '%1\$s', '%2\$s'), post_excerpt = REPLACE(post_excerpt, '%1\$s', '%2\$s')", $old, $new ),
				'Affected: ' . $wpdb->query( $query ),
			);

			$result[] = array(
				$query = $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, '%s', '%s') WHERE LEFT(meta_value,2) <> 'a:'", $old, $new ),
				'Affected: ' . $wpdb->query( $query ),
			);

			$result[] = array(
				$query = $wpdb->prepare( "UPDATE {$wpdb->comments} SET comment_content = REPLACE(comment_content, '%1\$s', '%2\$s'), comment_author_url = REPLACE(comment_author_url, '%1\$s', '%2\$s')", $old, $new ),
				'Affected: ' . $wpdb->query( $query ),
			);

			$rows = $wpdb->get_results("SELECT * FROM {$wpdb->postmeta} WHERE LEFT(meta_value, 2) = 'a:'");
			foreach ($rows as $row) {
				$value = maybe_unserialize($row->meta_value);
				if (is_array($value)) {
					array_walk_recursive($value, function (&$value) use ($old, $new) {
						$value = is_scalar($value) ? str_replace($old, $new, $value) : $value;
					});
					$value = serialize($value);
					if ($value !== $row->meta_value) {
						$result[] = array(
							"{$wpdb->postmeta} (post {$row->post_id}, {$row->meta_key}): ",
							$wpdb->update($wpdb->postmeta, array(
								'meta_value' => $value,
							), array(
								'meta_id' => $row->meta_id
							)),
						);
					}
				}
			}

			$rows = $wpdb->get_results("SELECT * FROM {$wpdb->options} WHERE autoload = 'yes' AND LEFT(option_value,2) = 'a:'");
			foreach ($rows as $row) {
				$value = maybe_unserialize($row->option_value);
				if (is_array($value)) {
					array_walk_recursive($value, function (&$value) use ($old, $new) {
						$value = is_scalar($value) ? str_replace($old, $new, $value) : $value;
					});
					$value = serialize($value);
					if ($value !== $row->option_value) {
						$result[] = array(
							"{$wpdb->options} ({$row->option_name}): ",
							$wpdb->update($wpdb->options, array(
								'option_value' => $value,
							), array(
								'option_name' => $row->option_name
							)),
						);
					}
				}
			}
		}

		if (in_array($wpdb->prefix . 'revslider_slides', $tables)) {
			$result[] = array(
				$query = $wpdb->prepare( "UPDATE {$wpdb->prefix}revslider_slides SET layers = REPLACE(layers, '%1\$s', '%2\$s')", addcslashes($old, '/'), addcslashes($new, '/')),
				'Affected: ' . $wpdb->query( $query ),
			);
		}


		if (in_array($wpdb->prefix . 'revslider_sliders', $tables)) {
			$result[] = array(
				$wpdb->prepare( "UPDATE {$wpdb->prefix}revslider_sliders SET params = REPLACE(params, '%1\$s', '%2\$s')", addcslashes($old, '/'), addcslashes($new, '/')),
				'Affected: ' . $wpdb->query( $query )
			);
		}

		return $result;
	}


	private function includeUpgrader()
	{
		require_once $this->getRequest('wp_root') . '/wp-admin/includes/admin.php';
		require_once $this->getRequest('wp_root') . '/wp-admin/includes/class-wp-upgrader.php';
	}

}