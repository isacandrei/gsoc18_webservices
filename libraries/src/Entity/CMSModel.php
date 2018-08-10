<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Entity;

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Entity\AdminModelTrait;
use Joomla\CMS\MVC\Entity\BaseModelTrait;
use Joomla\CMS\MVC\Entity\FormModelTrait;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Entity\Model;


/**
 * Class CMSModel used to extend the Entity Model
 *
 * @package  Joomla\CMS\Entity
 *
 * @since    __DEPLOY_VERSION__
 */
class CMSModel extends Model
{
	use EntityTableTrait;
	use BaseModelTrait;
	use FormModelTrait;
	use AdminModelTrait;

	/**
	 * Internal memory based cache array of data.
	 *
	 * @var    array
	 * @since  1.6
	 */
	protected $cache = array();

	/**
	 * The model (base) name
	 *
	 * @var    string
	 * @since  3.0
	 */
	protected $name;

	/**
	 * The URL option for the component.
	 *
	 * @var    string
	 * @since  3.0
	 */
	protected $option = null;

	/**
	 * Component parameters
	 *
	 * @var    mixed
	 * @since  __DEPLOY_VERSION__
	 */
	protected $componentParams;

	/**
	 * The factory.
	 *
	 * @var    MVCFactoryInterface
	 * @since  4.0.0
	 */
	protected $factory;

	/**
	 * CMSModel constructor.
	 *
	 * @param   DatabaseDriver  $db  Database Driver instance
	 *
	 * @throws Exception
	 */
	public function __construct(DatabaseDriver $db)
	{
		// If we instantiate a simple Entity as a relation.
		if (strpos(get_class($this), '\Entity\\'))
		{
			$this->option = 'entity';
			$this->name = 'entity';
		}
		else
		{
			// Guess the option from the class name (Option)Model(View).
			if (empty($this->option))
			{
				$r = null;
				preg_match('/(.*)Model/i', get_class($this), $r);

				if (!preg_match('/(.*)Model/i', get_class($this), $r))
				{
					throw new \Exception(\JText::_('JLIB_APPLICATION_ERROR_MODEL_GET_NAME'), 500);
				}

				$this->option = ComponentHelper::getComponentName($this, $r[1]);
			}

			$this->name = $this->getName();

			$component = Factory::getApplication()->bootComponent($this->option);

			if ($component instanceof MVCFactoryServiceInterface)
			{
				$this->factory = $component->createMVCFactory(Factory::getApplication());
			}
		}

		parent::__construct($db);

		// Get the pk of the record from the request.
		$pk = \JFactory::getApplication()->input->getInt($this->getPrimaryKey());
		$this->setPrimaryKeyValue($pk);

		// Load the parameters.
		$value = \JComponentHelper::getParams($this->option);
		$this->componentParams = $value;
	}

	/**
	 * Method to get the model name
	 *
	 * @return  string  The name of the model
	 *
	 * @since   3.0
	 * @throws  \Exception
	 */
	public function getName()
	{
		if (empty($this->name))
		{
			$r = null;
			if (!preg_match('/Model(.*)/i', get_class($this), $r))
			{
				throw new \Exception(\JText::_('JLIB_APPLICATION_ERROR_MODEL_GET_NAME'), 500);
			}

			$this->name = str_replace(['\\', 'model'], '', strtolower($r[1]));
		}

		return $this->name;
	}

	/**
	 * Set function (Backwards Compatibility)
	 *
	 * @param   string  $key    attribute name
	 * @param   mixed   $value  attribute value
	 *
	 * @return boolean
	 *
	 * @deprecated
	 */
	public function set($key, $value)
	{
		if (property_exists($this, $key))
		{
			$this->$key = $value;

			return true;
		}

		$this->entity->$key = $value;

		return true;
	}

	/**
	 * Backwards compatibility in controllers
	 *
	 * @param   integer  $i         .
	 * @param   boolean  $toString  .
	 *
	 * @return  string   .
	 *
	 * @deprecated
	 */
	public function getError($i = null, $toString = true)
	{
		return '';
	}

	/**
	 * Backwards compatibility in controllers
	 *
	 * @return  array  .
	 *
	 * @deprecated
	 */
	public function getErrors()
	{
		return [];
	}

	/**
	 * Backwards compatibility in controllers
	 *
	 * @param   string  $property  .
	 * @param   mixed   $value     .
	 *
	 * @return  mixed  .
	 *
	 * @since   3.0
	 * @deprecated
	 */
	public function setState($property, $value = null)
	{
		return true;
	}

	/**
	 * Backwards compatibility in controllers
	 *
	 * @param   string  $property  .
	 * @param   mixed   $default   .
	 *
	 * @return  mixed  .
	 *
	 * @since   3.0
	 * @deprecated
	 */
	public function getState($property = null, $default = null)
	{
		return $default;
	}

	/**
	 * Method for backwards compatibility
	 *
	 * @return Model
	 *
	 * @deprecated
	 */
	public function getTable()
	{
		return $this;
	}

	/**
	 * Getter for componentParams
	 *
	 * @return mixed
	 */
	public function getComponentParams()
	{
		return $this->componentParams;
	}
}
