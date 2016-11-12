<?php
	// Status tracker functions.
	// (C) 2016 CubicleSoft.  All Rights Reserved.

	function ST_LoadConfig()
	{
		global $rootpath;

		if (file_exists($rootpath . "/config.dat"))  $result = json_decode(file_get_contents($rootpath . "/config.dat"), true);
		else  $result = array();
		if (!is_array($result))  $result = array();

		return $result;
	}

	function ST_SaveConfig($config)
	{
		global $rootpath;

		file_put_contents($rootpath . "/config.dat", json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		@chmod($rootpath . "/config.dat", 0660);
	}

	function ST_LoadPlugins()
	{
		global $rootpath;

		$serverexts = array();
		$dir = opendir($rootpath . "/plugins");
		if ($dir !== false)
		{
			while (($file = readdir($dir)) !== false)
			{
				if (substr($file, -4) === ".php")
				{
					require_once $rootpath . "/plugins/" . $file;

					$key = substr($file, 0, -4);
					$classname = "ST_Plugin_" . $key;
					$serverexts[$key] = new $classname;
				}
			}

			closedir($dir);
		}

		return $serverexts;
	}
?>