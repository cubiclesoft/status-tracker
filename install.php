<?php
	// Status tracker installation tool.
	// (C) 2016 CubicleSoft.  All Rights Reserved.

	if (!isset($_SERVER["argc"]) || !$_SERVER["argc"])
	{
		echo "This file is intended to be run from the command-line.";

		exit();
	}

	// Temporary root.
	$rootpath = str_replace("\\", "/", dirname(__FILE__));

	require_once $rootpath . "/support/cli.php";
	require_once $rootpath . "/support/st_functions.php";

	$config = ST_LoadConfig();

	// Process the command-line options.
	$options = array(
		"shortmap" => array(
			"?" => "help"
		),
		"rules" => array(
			"help" => array("arg" => false)
		)
	);
	$args = CLI::ParseCommandLine($options);

	if (isset($args["opts"]["help"]))
	{
		echo "Status tracker installer\n";
		echo "Purpose:  Installs status tracker.\n";
		echo "\n";
		echo "This tool is question/answer enabled.  Just running it will provide a guided interface.  It can also be run entirely from the command-line if you know all the answers.\n";
		echo "\n";
		echo "Syntax:  " . $args["file"] . " [options]\n";
		echo "\n";
		echo "Examples:\n";
		echo "\tphp " . $args["file"] . "\n";

		exit();
	}

	$plugins = ST_LoadPlugins();
	foreach ($plugins as $plugin)
	{
		$plugin->Install();
	}

	ST_SaveConfig($config);

	if (!is_dir($rootpath . "/input"))  @mkdir($rootpath . "/input", 0775);
	if (!is_dir($rootpath . "/logs"))  @mkdir($rootpath . "/logs", 0775);

	echo "\n";
	echo "**********\n";
	echo "Configuration file is located at '" . $rootpath . "/config.dat'.  It can be manually edited.\n\n";
	echo "Now you can set up cron to run 'run.php' every minute.\n";
	echo "Read the documentation on what to do next.\n";
	echo "**********\n\n";

	echo "Done.\n";
?>