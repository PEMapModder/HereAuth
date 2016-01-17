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

namespace HereAuth\Logger;

interface AuditLogger{
	/**
	 * Log a registration
	 *
	 * @param string $name
	 * @param string $ip
	 */
	public function logRegister($name, $ip);

	/**
	 * Log a login
	 *
	 * @param string $name
	 * @param string $ip
	 * @param string $method one of "secret", "uuid", "ip" or "password"
	 */
	public function logLogin($name, $ip, $method);

	/**
	 * Log a "push", where a new player with the same name "pushed" away (kicked) the old player
	 *
	 * @param string $name
	 * @param string $oldIp
	 * @param string $newIp
	 */
	public function logPush($name, $oldIp, $newIp);

	/**
	 * Log a "bump", where a new player "bumped" into an online player with the same name and could not join.
	 *
	 * @param string $name
	 * @param string $oldIp
	 * @param string $newIp
	 * @param string $oldUuid
	 * @param string $newUuid
	 * @param string $oldState one of "loading", "register", "login", "auth" or "noreg"
	 */
	public function logBump($name, $oldIp, $newIp, $oldUuid, $newUuid, $oldState);

	/**
	 * Log a failure attempt to login
	 *
	 * @param string $name
	 * @param string $ip
	 */
	public function logInvalid($name, $ip);

	/**
	 * Log a kick caused by failure to login within timeout
	 *
	 * @param string $name
	 * @param string $ip
	 */
	public function logTimeout($name, $ip);

	/**
	 * Log a login rejected by multi-factor authentication.
	 *
	 * @param string $name
	 * @param string $wrongType
	 * @param string $wrongData
	 */
	public function logFactor($name, $wrongType, $wrongData);

	/**
	 * Free all resources and finalize the logger
	 */
	public function close();
}
