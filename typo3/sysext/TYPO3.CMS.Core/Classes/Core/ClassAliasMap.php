<?php
namespace TYPO3\CMS\Core\Core;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Thomas Maroschik <tmaroschik@dfau.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\Flow\Annotations as Flow;
/**
 * @Flow\Scope(singleton)
 */
class ClassAliasMap {

	const CACHE_ENTRY_IDENTIFIER = 'ClassAliasMapping';

	/**
	 * Old class name to new class name mapping
	 *
	 * @var array
	 */
	protected $aliasToClassNameMapping = array();

	/**
	 * New class name to old class name mapping
	 *
	 * @var array
	 */
	protected $classNameToAliasMapping = array();

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\PhpFrontend
	 */
	protected $classAliasCache;

	/**
	 * An array of \TYPO3\Flow\Package\Package objects
	 * @var array
	 */
	protected $packages = array();


	/**
	 * Injector method for a \TYPO3\Flow\Cache\Frontend\PhpFrontend
	 *
	 * @param \TYPO3\Flow\Cache\Frontend\PhpFrontend
	 */
	public function injectClassAliasCache(\TYPO3\Flow\Cache\Frontend\PhpFrontend $classAliasCache) {
		$this->classAliasCache = $classAliasCache;
	}

	/**
	 * @param array $packages
	 */
	public function setPackages(array $packages) {
		$this->packages = $packages;
	}

	/**
	 *
	 */
	public function initialize() {
		$aliasToClassNameMapping = NULL;
		if ($this->classAliasCache->has(self::CACHE_ENTRY_IDENTIFIER)) {
			list($this->aliasToClassNameMapping, $this->classAliasCache) = $this->classAliasCache->get(self::CACHE_ENTRY_IDENTIFIER);
		}
		if (!is_array($aliasToClassNameMapping)) {
			$aliasToClassNameMapping = array();
			foreach ($this->packages as $package) {
				if ($package instanceof \TYPO3\CMS\Core\Package\Package) {
					$aliasToClassNameMapping = array_merge($aliasToClassNameMapping, $package->getClassAliases());
				}
			}
			foreach ($aliasToClassNameMapping as $aliasClassName => $className) {
				$this->aliasToClassNameMapping[strtolower($aliasClassName)] = $className;
			}
			foreach (array_flip($aliasToClassNameMapping) as $className => $aliasClassName) {
				$this->classNameToAliasMapping[strtolower($className)] = $aliasClassName;
			}
		}
		$this->setAliasesForEarlyInstances();
	}

	/**
	 * Create aliases for early loaded classes
	 */
	protected function setAliasesForEarlyInstances() {
		$classedLoadedPriorToClassLoader = array_intersect($this->aliasToClassNameMapping, get_declared_classes());
		if (!empty($classedLoadedPriorToClassLoader)) {
			foreach ($classedLoadedPriorToClassLoader as $oldClassName => $newClassName) {
				if (!class_exists($oldClassName, FALSE)) {
					class_alias($newClassName, $oldClassName);
				}
			}
		}
	}

	/**
	 * @param string $alias
	 * @return mixed
	 */
	public function getClassNameForAlias($alias) {
		$lookUpClassName = strtolower($alias);
		return isset($this->aliasToClassNameMapping[$lookUpClassName]) ? $this->aliasToClassNameMapping[$lookUpClassName] : $alias;
	}


	/**
	 * @param string $className
	 * @return mixed
	 */
	public function getAliasForClassName($className) {
		return isset($this->classNameToAliasMapping[$className]) ? $this->classNameToAliasMapping[$className] : $className;
	}

}