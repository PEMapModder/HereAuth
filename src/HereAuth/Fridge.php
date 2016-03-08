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

namespace HereAuth;

class Fridge{
	/** @type HereAuth */
	private $main;
	private $warningSize = 10;
	/** @type object[]|callable[] */
	private $objects = [];
	/** @type int */
	private $nextObjectId = 0;

	public function __construct(HereAuth $main){
		$this->main = $main;
	}

	/**
	 * @param object|callable $object
	 *
	 * @return int
	 */
	public function store($object){
		$this->objects[$id = $this->nextId()] = $object;
		if(count($this->objects) >= $this->warningSize){
			$this->main->getLogger()->warning("Fridge size reached " . count($this->objects) . "! Object summary:");
			$summary = [];
			foreach($this->objects as $obj){
				$class = get_class($obj);
				if(isset($summary[$class])){
					$summary[$class]++;
				}else{
					$summary[$class] = 1;
				}
			}
			foreach($summary as $class => $cnt){
				$this->main->getLogger()->warning($class . ": $cnt entries");
			}
			$this->main->getLogger()->warning("The above is most likely caused by a mistake in code that results in a memory leak.");
		}
		return $id;
	}

	/**
	 * @param $id
	 *
	 * @return callable|object|null
	 */
	public function get($id){
		if(isset($this->objects[$id])){
			$object = $this->objects[$id];
			unset($this->objects[$id]);
			return $object;
		}
		return null;
	}

	/**
	 * Warning: avoid using this method to prevent memory leak
	 *
	 * @deprecated The use of this method is discouraged.
	 *
	 * @param int $id
	 *
	 * @return object|callable|null
	 */
	public function getWithoutClean($id){
		return isset($this->objects[$id]) ? $this->objects[$id] : null;
	}

	/**
	 * @return int
	 */
	private function nextId(){
		return $this->nextObjectId++;
	}
}
