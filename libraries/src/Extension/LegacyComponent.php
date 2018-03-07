<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Extension;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Dispatcher\DispatcherInterface;

/**
 * Access to component specific services.
 *
 * @since  __DEPLOY_VERSION__
 */
class LegacyComponent implements ComponentInterface
{
	/**
	 * @var string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	private $component;

	/**
	 * LegacyComponentContainer constructor.
	 *
	 * @param   string  $component  The component
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function __construct(string $component)
	{
		$this->component = str_replace('com_', '', $component);
	}

	/**
	 * Returns the dispatcher for the given application, null if none exists.
	 *
	 * @param   CMSApplicationInterface  $application  The application
	 *
	 * @return  DispatcherInterface|null
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getDispatcher(CMSApplicationInterface $application)
	{
		return null;
	}

	/**
	 * Returns the category service. If the service is not available
	 * null is returned.
	 *
	 * @param   array   $options  The options
	 * @param   string  $section  The section
	 *
	 * @return  Categories|null
	 *
	 * @see Categories::setOptions()
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getCategories(array $options = [], $section = '')
	{
		$classname = ucfirst($this->component) . ucfirst($section) . 'Categories';

		if (!class_exists($classname))
		{
			$path = JPATH_SITE . '/components/com_' . $this->component . '/helpers/category.php';

			if (!is_file($path))
			{
				return null;
			}

			include_once $path;
		}

		if (!class_exists($classname))
		{
			return null;
		}

		return new $classname($options);
	}
}
