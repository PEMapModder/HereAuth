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
	/** @type bool */
	private $overwrite;

	/** @type AccountReader */
	private $reader;

	/** @type string */
	public $status;
	/** @type double */
	public $progress;
	/** @type bool */
	public $completed = false;

	public function __construct(HereAuth $main, bool $overwrite, string $readerClass, array $readerArgs, string $writerClass, array $writerArgs){
		$this->readerClass = $readerClass;
		$this->readerArgs = serialize($readerArgs);
		$this->writerClass = $writerClass;
		$this->writerArgs = serialize($writerArgs);
		$this->defaultOpts = serialize(AccountOpts::defaultInstance($main));
		$this->overwrite = $overwrite;
		$rc = $this->readerClass;
		/** @type AccountReader $reader */
		$this->reader = new $rc;
	}

	public function run(){
		$reader = $this->reader;
		$wc = $this->writerClass;
		/** @type AccountWriter $writer */
		$writer = new $wc($this->overwrite, ...unserialize($this->writerArgs));
		$reader->read(unserialize($this->readerArgs), $writer);
		$this->completed = true;
	}

	public function hasCompleted() : bool{
		return $this->completed;
	}
}
