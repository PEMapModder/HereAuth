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

class SimpleAuthYAMLAccountReader extends AccountReader{
	public function read($params, AccountWriter $writer){
		$args = new FormattedArgumentMap($params);
		$folder = $args->opt("i", "plugins/SimpleAuth");
		if(!is_dir($folder)){
			throw new \InvalidArgumentException("Input database $folder not found or is not a directory");
		}
		$folder = rtrim($folder, DIRECTORY_SEPARATOR) . "/";
		$dir = $folder . "players/";
		$this->setStatus("Indexing accounts");
		$alphas = array_filter(scandir($dir), function ($alpha) use ($dir){
			return $alpha !== "." and strlen(rtrim($alpha, DIRECTORY_SEPARATOR)) === 1 and is_dir($dir . $alpha);
		});
		$alphaCnt = count($alphas);
		$this->setStatus("Transferring data");
		foreach($alphas as $i => $alpha){
			$base = $i / $alphaCnt;
			$subdir = $dir . rtrim($alpha, DIRECTORY_SEPARATOR) . "/";
			$names = scandir($subdir);
			$namesCnt = count($names);
			foreach($names as $cnt => $name){
				$this->setProgress($base + $cnt / $namesCnt / $alphaCnt);
				if(!preg_match('@^[A-za-z0-9_]{3,16}\\.yml$@', $name)){
					continue;
				}
				$data = yaml_parse_file($subdir . $name);
				$name = substr($name, 0, -4);
				if(!is_array($data)){
					continue;
				}
				$info = AccountInfo::defaultInstance($name, $this->defaultOpts);
				$info->passwordHash = hex2bin($data["hash"]);
				$info->lastIp = $data["lastip"];
				$info->registerTime = $data["registerdate"];
				$info->lastLogin = $data["logindate"];
				$writer->write($info);
			}
		}
		$this->setProgress(1.0);
	}
}
