<?php

if (!defined('_PS_VERSION_'))
	exit;

/**
 * @param Acumulus $object
 *
 * @return bool
 */
function upgrade_module_3_3_3($object) {
	return $object->createTables();
}
