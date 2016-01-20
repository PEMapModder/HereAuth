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

echo "(Ignore the line above; it is just a harmless annoying Shebang line)", "\n";

//if(version_compare(PHP_VERSION, "7.0.0", "<")){
//	echo "Fatal: This entry script requires PHP >=7.0.0!\n";
//	exit;
//}

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

echo "Press enter to exit\n";
fgets(STDIN);
exit(0);
