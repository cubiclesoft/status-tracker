<?php
	// Status tracker e-mail notification plugin.
	// (C) 2016 CubicleSoft.  All Rights Reserved.

	class ST_Plugin_email_notify
	{
		public function Install()
		{
			global $config, $args;

			if (!isset($config["emailinfo"]))  $config["emailinfo"] = array();

			$config["emailinfo"]["from"] = CLI::GetUserInputWithArgs($args, "from", "FROM e-mail address", (isset($config["emailinfo"]["from"]) ? $config["emailinfo"]["from"] : false), "Specify the source e-mail address for all notifications.");
			$config["emailinfo"]["prefix"] = CLI::GetUserInputWithArgs($args, "prefix", "Subject line prefix", (isset($config["emailinfo"]["prefix"]) ? $config["emailinfo"]["prefix"] : "[STATUS" . (gethostname() !== false ? " - " . gethostname() : "") . "]"), "Specify the prefix for the subject line of each e-mail sent by this system.");
			$config["emailinfo"]["usemail"] = CLI::GetYesNoUserInputWithArgs($args, "usemail", "PHP mail() command", (isset($config["emailinfo"]["usemail"]) ? ($config["emailinfo"]["usemail"] ? "Y" : "N") : "Y"), "The next question declares whether to use the built-in PHP mail() function to send e-mail or SMTP.  For most environments, the default works to send e-mail from the local host (e.g. a local Postfix server).");
			if ($config["emailinfo"]["usemail"])
			{
				$config["emailinfo"]["server"] = "";
				$config["emailinfo"]["port"] = 25;
				$config["emailinfo"]["secure"] = false;
				$config["emailinfo"]["username"] = "";
				$config["emailinfo"]["password"] = "";
			}
			else
			{
				$config["emailinfo"]["server"] = CLI::GetUserInputWithArgs($args, "server", "SMTP server (without port)", (isset($config["emailinfo"]["server"]) ? $config["emailinfo"]["server"] : "localhost"), "Specify the SMTP server to connect to without the port number.  You'll be asked for the port number in the next question.");
				$config["emailinfo"]["port"] = (int)CLI::GetUserInputWithArgs($args, "port", "SMTP port", (isset($config["emailinfo"]["port"]) ? $config["emailinfo"]["port"] : 25), "Specify the SMTP port to connect to.  This is usually port 25, 465, or 587.");
				$config["emailinfo"]["secure"] = CLI::GetYesNoUserInputWithArgs($args, "secure", "SMTP over SSL/TLS", (isset($config["emailinfo"]["secure"]) ? ($config["emailinfo"]["secure"] ? "Y" : "N") : ($config["emailinfo"]["port"] != 25 ? "Y" : "N")), "The next question declares whether to use SSL/TLS for communicating with the SMTP server.");
				$config["emailinfo"]["username"] = CLI::GetUserInputWithArgs($args, "server", "SMTP username", (isset($config["emailinfo"]["username"]) ? $config["emailinfo"]["username"] : ""), "Specify the optional SMTP username to connect as.");
				$config["emailinfo"]["password"] = CLI::GetUserInputWithArgs($args, "server", "SMTP password", (isset($config["emailinfo"]["password"]) ? $config["emailinfo"]["password"] : ""), "Specify the optional SMTP password to connect as.");
			}
		}

		public function InitRun()
		{
			global $config;

			if (!isset($config["emailinfo"]))  CLI::DisplayError("E-mail notification configuration is incomplete or missing.  Run 'install.php' first.");
		}

		public function NotifyUsers($name, $data, $prevstatus, $ts)
		{
			global $rootpath, $config;

			require_once $rootpath . "/support/smtp.php";

			$subject = $config["emailinfo"]["prefix"] . " " . $name . " - " . $data["status"];

			$message = "<html><body>";

			$message .= "<p>The status for " . $name . ($prevstatus !== false ? " has been changed from '" . $prevstatus . "' to '" . $data["status"] . "'." : " is now '" . $data["status"] . "' and was previously unregistered.") . "</p>\n\n";

			$message .= "<p>";
			$message .= "Host:  " . gethostname() . "<br>\n";
			$message .= "Time (local):  " . date("D, F j, Y g:i:s A", $ts) . "<br>\n";
			$message .= "Time (UTC):  " . gmdate("D, F j, Y g:i:s A", $ts);
			$message .= "</p>\n\n";

			$message .= "</body></html>";

			$smtpoptions = array(
				"headers" => SMTP::GetUserAgent("Thunderbird"),
				"htmlmessage" => $message,
				"textmessage" => SMTP::ConvertHTMLToText($message),
				"usemail" => $config["emailinfo"]["usemail"],
				"server" => $config["emailinfo"]["server"],
				"port" => $config["emailinfo"]["port"],
				"secure" => $config["emailinfo"]["secure"],
				"username" => $config["emailinfo"]["username"],
				"password" => $config["emailinfo"]["password"]
			);

			$fromaddr = $config["emailinfo"]["from"];

			foreach ($data["notify"] as $toaddr)
			{
				if (substr($toaddr, 0, 7) === "mailto:" || substr($toaddr, 0, 7) === "email:")
				{
					$toaddr = str_replace(array("mailto:", "email:"), "", $toaddr);

					// SMTP only.  No POP before SMTP support.
					$result = SMTP::SendEmail($fromaddr, $toaddr, $subject, $smtpoptions);
					if (!$result["success"])  CLI::DisplayError("Unable to send notification e-mail from '" . $fromaddr . "' to '" . $toaddr . "'.", $result, false);
				}
			}
		}
	}
?>