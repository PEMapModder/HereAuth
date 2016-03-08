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

namespace HereAuth\Database;

use HereAuth\User\AccountInfo;

interface Database{
	const SUCCESS = 0;
	const UNKNOWN_ERROR = 1;
	const RENAME_SOURCE_ABSENT = 2;
	const RENAME_TARGET_PRESENT = 3;

	public function loadFor($name, $identifier);

	public function saveData(AccountInfo $info, $overwrite = true);

	public function renameAccount($oldName, $newName, callable $hook);

	public function unregisterAccount($name, callable $hook);

	public function passesLimit($ip, $limit, $time, $identifier);

	public function getAccountWriter(&$writerArgs) : string;

	public function close();
}
