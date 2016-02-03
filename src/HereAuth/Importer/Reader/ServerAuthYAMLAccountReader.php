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
use HereAuth\Utils\FormattedArgumentMap;

class ServerAuthYAMLAccountReader extends AccountReader{
	public function read($params, AccountWriter $writer){
		$args = new FormattedArgumentMap($params);
		$folder = $args->opt("i", "plugins/ServerAuth");
		if(!is_dir($folder)){
			throw new \InvalidArgumentException("Input database $folder not found or is not a directory");
		}
		$folder = rtrim($folder, "/\\") . DIRECTORY_SEPARATOR;
		$configFile = $folder . "config.yml";
		if(($hashMethod = $args->opt("hash", null)) === null){
			if(is_file($configFile)){
				try{
					$hashMethod = yaml_parse($configFile)["passwordHash"];
				}catch(\Exception $e){
					$hashMethod = "md5";
				}
			}else{
				$hashMethod = "md5";
			}
		}
		$users = $folder . "users/";
		$this->setStatus("Collecting files");
		$names = scandir($users);
		$namesCnt = count($names);
		$this->setStatus("Transferring data");
		foreach($names as $i => $name){
			$this->setStatus($i / $namesCnt);
			$file = $users . $name;
			if(!is_file($file) or !preg_match('@^[A-Za-z0-9_]{3,16}\.yml$@', $name)){
				continue;
			}
			$name = substr($file, 0, -4);
			$data = yaml_parse_file($file);
			$ai = AccountInfo::defaultInstance($name, $this->defaultOpts);
			$ai->passwordHash = "{IMPORTED}";
			$ai->lastIp = $data["ip"];
			$ai->registerTime = $data["firstlogin"];
			$ai->lastLogin = $data["lastlogin"];
			$ai->multiHash = ["saltless;" . $hashMethod => $data["password"], "nonhash:salt" => strtolower($name)];
			$writer->write($ai);
		}
	}
}
