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

interface ImportedHash{
	public function getName();

	/**
	 * Returns a UTF-8 non-binary string so that it can be json_encode()'ed.
	 * Implementors can use {@link base64_encode()} or {@link bin2hex()} to encode binary data.
	 *
	 * @param string $password
	 * @param string $salt
	 * @param string $suffix
	 *
	 * @return string
	 */
	public function hash($password, $salt, $suffix);
}
