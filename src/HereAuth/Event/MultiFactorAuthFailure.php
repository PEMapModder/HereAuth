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

namespace HereAuth\Event;

class MultiFactorAuthFailure{
	public $type;
	public $message;
	public $data;

	/**
	 * @param string $type    type of MFA, used in audit logs
	 * @param string $message user-friendly message used in player kick message
	 * @param string $data    printable, single-line string used in audit logs as data for this
	 *
	 * @throws \InvalidArgumentException if $data is not printable or single-line
	 */
	public function __construct($type, $message, $data = ""){
		if(!ctype_print($data)){
			throw new \InvalidArgumentException("\$data is not printable");
		}
		$this->type = $type;
		$this->message = $message;
		$this->data = $data;
	}
}
