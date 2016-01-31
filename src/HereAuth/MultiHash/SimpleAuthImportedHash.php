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

namespace HereAuth\MultiHash;

use HereAuth\HereAuth;

class SimpleAuthImportedHash implements ImportedHash{
	public function getName(){
		return "simpleauth";
	}

	public function hash($password, $salt, $suffix){
		return bin2hex(HereAuth::hash($password, $salt));
	}
}
