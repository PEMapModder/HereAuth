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

namespace HereAuth\Importer;

use HereAuth\HereAuth;
use HereAuth\Importer\Reader\AccountReader;
use HereAuth\Importer\Writer\AccountWriter;
use HereAuth\User\AccountOpts;
use pocketmine\Thread;

class ImporterThread extends Thread{
	/** @type string */
	private $readerClass;
	/** @type array */
	private $readerArgs;
	/** @type string */
	private $writerClass;
	/** @type array */
	private $writerArgs;
	/** @type AccountOpts */
	private $defaultOpts;

	public function __construct(HereAuth $main, $readerClass, $readerArgs, $writerClass, $writerArgs){
		$this->readerClass = $readerClass;
		$this->readerArgs = $readerArgs;
		$this->writerClass = $writerClass;
		$this->writerArgs = $writerArgs;
		$this->defaultOpts = AccountOpts::defaultInstance($main);
	}

	public function run(){
		$rc = $this->readerClass;
		$wc = $this->writerClass;
		/** @type AccountReader $reader */
		$reader = new $rc;
		/** @type AccountWriter $writer */
		$writer = new $wc(...$this->writerArgs);
		$reader->read($this->readerArgs, $writer);
	}
}
