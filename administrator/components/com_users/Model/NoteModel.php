<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Joomla\Component\Users\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Users\Administrator\Table\NoteTable;

/**
 * User note model.
 *
 * @since  2.5
 */
class NoteModel extends AdminModel
{
	/**
	 * The type alias for this content type.
	 *
	 * @var      string
	 * @since    3.2
	 */
	public $typeAlias = 'com_users.note';

	protected $entity;

	public function __construct(array $config = array(), MVCFactoryInterface $factory = null, FormFactoryInterface $formFactory = null)
	{
		parent::__construct($config, $factory, $formFactory);
		$this->entity = new NoteTable($this->getDbo());
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A \JForm object on success, false on failure
	 *
	 * @since   2.5
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_users.note', 'note', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since   2.5
	 */
	public function getItem($pk = null)
	{
		if (!$pk)
		{
			$result = $this->entity;
		}
		else
		{
			$result = $this->entity->find($pk);
		}

		// Get the dispatcher and load the content plugins.
		PluginHelper::importPlugin('content');

		// Load the user plugins for backward compatibility (v3.3.3 and earlier).
		PluginHelper::importPlugin('user');

		// Trigger the data preparation event.
		\JFactory::getApplication()->triggerEvent('onContentPrepareData', array('com_users.note', $result));

		return $result;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   1.6
	 */
	protected function loadFormData()
	{
		// Get the application
		$app = \JFactory::getApplication();

		// Check the session for previously entered form data.
		$data = $app->getUserState('com_users.edit.note.data', array());

		if (empty($data))
		{
			$data = $this->getItem($this->getState($this->getName() . '.id'));
			// Prime some default values.
			if ($this->getState('note.id') == 0)
			{
				$data->set('catid', $app->input->get('catid', $app->getUserState('com_users.notes.filter.category_id'), 'int'));
			}

			$userId = $app->input->get('u_id', 0, 'int');

			if ($userId != 0)
			{
				$data->user_id = $userId;
			}
		}

		$this->preprocessData('com_users.note', $data);

		return $data;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	protected function populateState()
	{
		parent::populateState();

		$userId = \JFactory::getApplication()->input->get('u_id', 0, 'int');
		$this->setState('note.user_id', $userId);
	}
}
