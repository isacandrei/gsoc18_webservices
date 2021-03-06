<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fields.URL
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

$value = $field->value;

if ($value == '')
{
	return;
}

$attributes = '';

if (!Uri::isInternal($value))
{
	$attributes = ' rel="nofollow noopener noreferrer" target="_blank"';
}

echo sprintf('<a href="%s"%s>%s</a>',
	htmlspecialchars($value),
	$attributes,
	htmlspecialchars($value)
);
