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

class SaltlessArgumentedImportedHash implements ImportedHash{
	public function getName(){
		return "saltless";
	}

	public function hash($password, $salt, $suffix){
		if(!in_array($suffix, hash_algos())){
			throw new \InvalidArgumentException("Unknown hash algorithm $suffix");
		}
		return hash($suffix, $password);
	}
}
