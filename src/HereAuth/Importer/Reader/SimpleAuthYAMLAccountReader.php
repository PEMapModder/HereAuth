<?php

/*
 * HereAuth
 *
 * Copyright (C) 2016 PEMapModder
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PEMapModder
 */

namespace HereAuth\Importer\Reader;

use HereAuth\Importer\Writer\AccountWriter;
use HereAuth\User\AccountInfo;
use HereAuth\User\AccountOpts;

class SimpleAuthYAMLAccountReader extends AccountReader{
	/** @type AccountOpts */
	private $defaultOpts;

	public function read($args, AccountWriter $writer){
		$dir = "plugins/SimpleAuth/players/"; // TODO customize
		foreach(new \RegexIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)), '@/[A-za-z0-9_]{3,16}\\.yml$@') as $file){
			if(!is_File($file)){
				continue;
			}
			$name = basename($file, ".yml");
			$data = yaml_parse_file($file);
			if(!is_array($data)){
				continue;
			}
			$info = AccountInfo::defaultInstance($name, $this->defaultOpts);
			$info->passwordHash = $data["hash"];
			$info->lastIp = $data["lastip"];
			$info->registerTime = $data["registerdate"];
			$info->lastLogin = $data["logindate"];
			$writer->write($info);
		}
		$this->setProgress(1.0);
	}
}
