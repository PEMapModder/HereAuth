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

namespace HereAuth\User\Registration;

use pocketmine\event\TextContainer;

interface RegistrationStep{
	/**
	 * @return string|TextContainer
	 */
	public function getMessage();

	/**
	 * Triggered when a user answers this registration step.
	 * It is safe to call {@link Registration::rewind} from here.
	 *
	 * @param string $value
	 *
	 * @return bool whether the value is accepted.
	 */
	public function onSubmit($value);
}
