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

namespace HereAuth\Importer\Reader;

use HereAuth\HereAuth;
use HereAuth\Importer\ImporterThread;
use HereAuth\Importer\Writer\AccountWriter;
use HereAuth\User\AccountOpts;

abstract class AccountReader{
	/** @type ImporterThread */
	private $thread;

	/** @type AccountOpts */
	protected $defaultOpts;

	/** @type string */
	private $status = "Initializing";
	/** @type double */
	private $progress;

	public function __construct(HereAuth $main, ImporterThread $thread){
		$this->defaultOpts = AccountOpts::defaultInstance($main);
		$this->thread = $thread;
	}

	public abstract function read($params, AccountWriter $writer);

	public function getProgress(){
		return $this->progress;
	}

	protected function setProgress($progress){
		$this->progress = $progress;
		$this->thread->progress = $progress;
	}

	/**
	 * @return string
	 */
	public function getStatus(){
		return $this->status;
	}

	/**
	 * @param string $status
	 */
	public function setStatus($status){
		$this->status = $status;
		$this->thread->status = $status;
	}
}
