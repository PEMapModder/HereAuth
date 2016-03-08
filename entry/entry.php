#!/usr/bin/env php
<?php

/*
 * NOWHERE Plugin Workspace Framework
 *
 * Copyright (C) 2015-2016 PEMapModder
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PEMapModder
 */

if(version_compare(PHP_VERSION, "7.0.0", "<")){
	echo "Fatal: This entry script requires PHP >=7.0.0!\n";
	exit;
}

if(!defined("STDIN")){
	define("STDIN", fopen("php://stdin", "r"));
}

spl_autoload_register(function ($class){
	$holder = Phar::running() . "/entry/";
	$file = $holder . str_replace("\\", "/", $class) . ".php";
	if(is_file($file)){
		require_once $file;
	}else{
		throw new RuntimeException("Class $class not found!");
	}
});

if(!defined("STDIN")){
	$define = "define";
	$define("STDIN", fopen("php://stdin", "r"));
}

$action = getopt("", ["action:"])["action"] ?? "?";

switch(strtolower($action)){
	case "import":
		$opts = getopt("", ["input-type:", "input:", "output-type:", "output:"]);
		if(!isset($opts["input-type"], $opts["input"], $opts["output-type"], $opts["output"])){
		}
}

echo "Please type a command to continue.\n";
query_cmd:
echo "Supported commands: init\n";

$line = strtolower(trim(fgets(STDIN)));
if($line === "init"){
	if(!is_dir("HereAuth")){
		mkdir("HereAuth");
	}
	$file = "config.yml";
	$i = 0;
	while(is_file($file)){
		$file = "config.yml." . ($i++);
	}
	$contents = file_get_contents(Phar::running() . "/resources/config.yml");
	$uname = php_uname("s");
	if(stripos($uname, "Win") !== false or $uname === "Msys"){
		$contents = str_replace(["/dev/null", '${IS_WINDOWS}'], ["/NUL", "Windows"], $contents);
	}else{
		$contents = str_replace('${IS_WINDOWS}', "non-Windows", $contents);
	}
	file_put_contents("HereAuth/$file", $contents);
	echo "Created config file at " . realpath("HereAuth/$file") . "\n";
}else{
	echo "Unknown command!\n";
	goto query_cmd;
}

echo "Press enter to exit\n";
fgets(STDIN);
exit(0);
