<?php
class ScriptBase
{
	protected $_fileName;
	protected $_log;
	protected $_registry;
	protected $db;
	protected $_baseDir;

	public function __construct()
	{
		$this->_baseDir = dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR;
		require_once $this->_baseDir . 'config.php';
		require_once $this->_baseDir . 'system/engine/registry.php';
		require_once $this->_baseDir . 'system/engine/loader.php';
		require_once $this->_baseDir . 'system/library/config.php';
		require_once $this->_baseDir . 'system/library/db.php';
		require_once $this->_baseDir . 'system/library/language.php';
		require_once $this->_baseDir . 'system/library/cache.php';
		require_once $this->_baseDir . 'system/library/length.php';
		require_once $this->_baseDir . 'system/library/customer.php';
		require_once $this->_baseDir . 'system/library/url.php';
		require_once $this->_baseDir . 'system/engine/model.php';
		require_once $this->_baseDir . 'system/library/log.php';
		require_once $this->_baseDir . 'system/helper/utf8.php';
		require_once(DIR_SYSTEM . 'engine/controller.php');
        	require_once(DIR_APPLICATION . 'controller/common/seo_url.php');
		
		$this->_registry = new Registry();
		$loader = new Loader($this->_registry);
		$this->_registry->set('load', $loader);
		$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
		$language = new Language('english');
		$language->load('english');
		$this->_registry->set('language', $language);
		$this->_registry->set('db', $db);
		$this->db = $db;
		$config = new Config();
		$config->set('config_store_id', 0);
		$query = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '0' OR store_id = '" . (int)$config->get('config_store_id') . "' ORDER BY store_id ASC");
		foreach ($query->rows as $setting)
		{
			if (!$setting['serialized'])
			{
				$config->set($setting['key'], $setting['value']);
			}
			else
			{
				$config->set($setting['key'], unserialize($setting['value']));
			}
		}
		$config->set('config_url', HTTP_SERVER);
		$config->set('config_ssl', HTTPS_SERVER);
		$config->set('config_language_id', 1);
		$this->_registry->set('config', $config);

		$url = new Url($config->get('config_url'), $config->get('config_secure') ? $config->get('config_ssl') : $config->get('config_url'));
		$this->_registry->set('url', $url);

		// Customer
		$this->_registry->set('customer', new Customer($this->_registry));

		$log = new Log($config->get('config_error_filename'));
		$this->_registry->set('log', $log);
		$cache = new Cache();
		$this->_registry->set('cache', $cache);
		$length = new Length($this->_registry);
		$this->_registry->set('length', $length);
		$this->_log = $this->_registry->get('log');
	}

	public function __get($name)
	{
		return $this->_registry->get($name);
	}
}
?>
