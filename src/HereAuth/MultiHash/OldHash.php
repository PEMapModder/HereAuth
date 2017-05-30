<?php

/*
 *
 * HereAuth
 *
 * Copyright (C) 2017 SOFe
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
*/

namespace HereAuth\MultiHash;

class OldHash implements ImportedHash{
	public function getName(){
		return "hereauth.old";
	}

	public function hash($password, $salt, $suffix){
		throw new \RuntimeException("Unsupported Operation");
	}

	public function verify($password, $salt, $suffix, $hash){
		return hash("sha512", $password . $salt, true) ^ hash("whirlpool", $salt . $password, true) === base64_decode($hash);
	}
}
