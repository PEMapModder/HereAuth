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
	public function loadFor($name, $identifier);

	public function saveData($name, AccountInfo $info);

	public function renameAccount($oldName, $newName);

	public function unregisterAccount($name);

	public function close();
}
