<?php
	// Status tracker.
	// (C) 2016 CubicleSoft.  All Rights Reserved.

	if (!isset($_SERVER["argc"]) || !$_SERVER["argc"])
	{
		echo "This file is intended to be run from the command-line.";

		exit();
	}

	// Make sure PHP doesn't introduce weird limitations.
	ini_set("memory_limit", "-1");
	set_time_limit(0);

	// Temporary root.
	$rootpath = str_replace("\\", "/", dirname(__FILE__));

	require_once $rootpath . "/support/cli.php";
	require_once $rootpath . "/support/st_functions.php";

	$config = ST_LoadConfig();

	$plugins = ST_LoadPlugins();
	foreach ($plugins as $plugin)
	{
		$plugin->InitRun();
	}

	// Retrieve new input files.
	$files = array();
	$dir = @opendir($rootpath . "/input");
	if ($dir)
	{
		while (($file = readdir($dir)) !== false)
		{
			if ($file !== "." && $file !== "..")
			{
				$filename = $rootpath . "/input/" . $file;

				if (substr($file, -5) === ".json" && strpos($file, "-") !== false)
				{
					$ts = filemtime($filename);
					if ($ts + 1 < time())
					{
						$data = @file_get_contents($filename);
						$data = @json_decode($data, true);
						if (is_array($data))
						{
							// Set the timestamp of the starting point for status duration calculations.
							$data["start"] = $ts;
							$data["start_utc"] = date("Y-m-d H:i:s", $data["start"]);

							$files[$file] = $data;
						}
					}
				}

				@unlink($filename);
			}
		}

		closedir($dir);
	}

	uksort($files, "strnatcmp");

	// Retrieve existing names.
	$names = array();
	$dir = @opendir($rootpath . "/logs");
	if ($dir)
	{
		while (($file = readdir($dir)) !== false)
		{
			if (substr($file, -5) === ".json")
			{
				$filename = $rootpath . "/logs/" . $file;
				$data = @file_get_contents($filename);
				$data = @json_decode($data, true);
				if (is_array($data))  $names[substr($file, 0, -5)] = $data;
			}
		}

		closedir($dir);
	}

	function NotifyUsers($name, $data, $prevstatus, $ts)
	{
		global $plugins;

		foreach ($plugins as $plugin)
		{
			$plugin->NotifyUsers($name, $data, $prevstatus, $ts);
		}
	}

	function AppendLog($name, $data, $endts)
	{
		global $rootpath;

		$data["end"] = $endts;
		$data["end_utc"] = date("Y-m-d H:i:s", $endts);

		$fp = fopen($rootpath . "/logs/" . $name . ".log", "ab");
		if ($fp !== false)
		{
			fwrite($fp, json_encode($data, JSON_UNESCAPED_SLASHES) . "\n");

			fclose($fp);
		}
	}

	// Process incoming data.
	foreach ($files as $filename => $data)
	{
		if (isset($data["status"]) && is_string($data["status"]) && isset($data["notify"]) && (is_string($data["notify"]) || is_array($data["notify"])))
		{
			$pos = strpos($filename, "-");
			$name = substr($filename, 0, $pos);

			// Calculate timeout.
			if (isset($data["timeout"]))  $data["timeout"] = time() + ((int)$data["timeout"] > 0 ? (int)$data["timeout"] : 1);

			// Revert 'start' if it already existed and the status has not changed.
			if (isset($names[$name]) && $data["status"] === $names[$name]["status"])
			{
				$data["start"] = $names[$name]["start"];
				$data["start_utc"] = $names[$name]["start_utc"];
			}

			if (is_string($data["notify"]))  $data["notify"] = array($data["notify"]);

			file_put_contents($rootpath . "/logs/" . $name . ".json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

			// Only notify users if the status has changed.
			if (!isset($names[$name]))  NotifyUsers($name, $data, false, $data["start"]);
			else if ($data["status"] !== $names[$name]["status"])
			{
				AppendLog($name, $names[$name], $data["start"]);

				NotifyUsers($name, $data, $names[$name]["status"], $data["start"]);
			}

			$names[$name] = $data;
		}
	}

	// Evaluate remaining names for timeout expiration.
	foreach ($names as $name => $data)
	{
		if (isset($data["timeout"]) && $data["timeout"] <= time())
		{
			// Copy the data, set the status, and clear the timeout.
			$data2 = $data;
			$data2["status"] = "status-tracker-timeout";
			unset($data2["timeout"]);

			// Set the timestamp of the starting point for status duration calculation.
			$data2["start"] = time();
			$data2["start_utc"] = date("Y-m-d H:i:s", $data2["start"]);

			file_put_contents($rootpath . "/logs/" . $name . ".json", json_encode($data2, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

			AppendLog($name, $data, $data2["start"]);

			NotifyUsers($name, $data2, $data["status"], $data2["start"]);
		}
	}
?>