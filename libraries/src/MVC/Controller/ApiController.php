<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\MVC\Controller;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Factory\MvcFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * Base class for a Joomla API Controller
 *
 * Controller (controllers are where you put all the actual code) Provides basic
 * functionality, such as rendering views (aka displaying templates).
 *
 * @since  __DEPLOY_VERSION__
 */
class ApiController extends BaseController
{
	/**
	 * The content type of the item.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $contentType;

	/**
	 * The URL option for the component.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $option;

	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $text_prefix;

	/**
	 * The context for storing internal data, e.g. record.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $context;

	/**
	 * Constructor.
	 *
	 * @param   array                $config   An optional associative array of configuration settings.
	 * Recognized key values include 'name', 'default_task', 'model_path', and
	 * 'view_path' (this list is not meant to be comprehensive).
	 * @param   MvcFactoryInterface  $factory  The factory.
	 * @param   CmsApplication       $app      The JApplication for the dispatcher
	 * @param   \JInput              $input    Input
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  \Exception
	 */
	public function __construct($config = array(), MvcFactoryInterface $factory = null, $app = null, $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		// Guess the option as com_NameOfController
		if (empty($this->option))
		{
			$this->option = \JComponentHelper::getComponentName($this, $this->getName());
		}

		// Guess the \JText message prefix. Defaults to the option.
		if (empty($this->text_prefix))
		{
			$this->text_prefix = strtoupper($this->option);
		}

		// Guess the context as the suffix, eg: OptionControllerContent.
		if (empty($this->context))
		{
			$r = null;

			if (!preg_match('/(.*)Controller(.*)/i', get_class($this), $r))
			{
				throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_CONTROLLER_GET_NAME'), 500);
			}

			$this->context = str_replace('\\', '', strtolower($r[2]));
		}
	}

	/**
	 * Basic display of an item view
	 *
	 * @param  integer  $id  The primary key to display. Leave empty if you want to retrieve data from the request
	 *
	 * @return  static  A \JControllerLegacy object to support chaining.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function displayItem($id = null)
	{
		if ($id === null)
		{
			$id = $this->input->get('id', 0, 'int');
		}

		$document = \JFactory::getDocument();
		$viewType = $document->getType();
		$viewName = $this->input->get('view', 'ItemJsonView');
		$viewLayout = $this->input->get('layout', 'default', 'string');

		try
		{
			$view = $this->getView($viewName, $viewType, '', ['base_path' => $this->basePath, 'layout' => $viewLayout, 'contentType' => $this->contentType]);
		}
		catch (\Exception $e)
		{
			return $this;
		}

		// Create the model, ignoring request data so we can safely set the state in the request, without it being
		// reinitialised on the first getState call
		$model = $this->getModel('', '', ['ignore_request' => true]);

		if (!$model)
		{
			throw new \RuntimeException('Unable to create the model');
		}

		try
		{
			$modelName = $model->getName();
		}
		catch (\Exception $e)
		{
			return $this;
		}

		$model->setState($modelName . '.id', $id);

		// Push the model into the view (as default)
		$view->setModel($model, true);

		$view->document = $document;
		$view->display();

		return $this;
	}

	/**
	 * Basic display of a list view
	 *
	 * @return  static  A \JControllerLegacy object to support chaining.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function displayList()
	{
		// Assemble pagination information (using recommended JsonApi pagination notation for offset strategy)
		$paginationInfo = $this->input->get('page', [], 'array');
		$internalPaginationMapping = [];

		if (array_key_exists('offset', $paginationInfo))
		{
			$this->input->set('limitstart', $paginationInfo['offset']);
		}

		if (array_key_exists('limit', $paginationInfo))
		{
			$internalPaginationMapping['limit'] = $paginationInfo['limit'];
		}

		$this->input->set('list', $internalPaginationMapping);

		$document = \JFactory::getDocument();
		$viewType = $document->getType();
		$viewName = $this->input->get('view', 'ListJsonView');
		$viewLayout = $this->input->get('layout', 'default', 'string');

		try
		{
			$view = $this->getView($viewName, $viewType, '', ['base_path' => $this->basePath, 'layout' => $viewLayout, 'contentType' => $this->contentType]);
		}
		catch (\Exception $e)
		{
			return $this;
		}

		/** @var ListModel $model */
		$model = $this->getModel($this->contentType);

		if (!$model)
		{
			throw new \RuntimeException('Model failed to be created', 500);
		}

		// Push the model into the view (as default)
		$view->setModel($model, true);

		/**
		 * Sanity check we don't have too much data being requested as regularly we will automatically set it back to
		 * the last page of data
		 */
		if ($this->input->getInt('limitstart', 0) > $model->getTotal())
		{
			throw new Exception\ResourceNotFound;
		}

		$view->document = $document;

		$view->display();

		return $this;
	}

	/**
	 * Removes an item.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function delete()
	{
		if (!\JFactory::getUser()->authorise('core.delete', $this->option))
		{
			throw new NotAllowed('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED', 403);
		}

		$id = $this->input->get('id', 0, 'int');

		/** @var \Joomla\CMS\MVC\Model\AdminModel $model */
		$model = $this->getModel();

		// Remove the item.
		if (!$model->delete($id))
		{
			throw new \RuntimeException($model->getError(), 500);
		}

		$this->app->setHeader('status', 204);
	}

	/**
	 * Method to add a new record.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  NotAllowed
	 * @throws  \RuntimeException
	 */
	public function add()
	{
		// Access check.
		if (!$this->allowAdd())
		{
			throw new NotAllowed('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED', 403);
		}
		else
		{
			$success = $this->save();

			if (!$success)
			{
				throw new \RuntimeException($this->message);
			}

			$this->displayItem($success);
		}
	}

	/**
	 * Method to edit an existing record.
	 *
	 * @return  boolean  True if save succeeded after access level check and checkout passes, false otherwise.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function edit()
	{
		/** @var \Joomla\CMS\MVC\Model\AdminModel $model */
		$model = $this->getModel();

		try
		{
			$table = $model->getTable();
		}
		catch (\Exception $e)
		{
			$this->setMessage($e->getMessage());

			return false;
		}

		$recordId = $this->input->getInt('id');

		if (!$recordId)
		{
			// TODO: Nice exception with lang string
			throw new \RuntimeException('Record does not exist', 404);
		}

		$key      = $table->getKeyName();
		$checkin  = property_exists($table, $table->getColumnAlias('checked_out'));

		// Access check.
		if (!$this->allowEdit(array($key => $recordId), $key))
		{
			throw new NotAllowed('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED', 403);
		}

		// Attempt to check-out the new record for editing and redirect.
		if ($checkin && !$model->checkout($recordId))
		{
			// Check-out failed, display a notice but allow the user to see the record.
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKOUT_FAILED', $model->getError()), 'error');

			return false;
		}

		if (!$this->save($recordId))
		{
			throw new \RuntimeException($this->message);
		}

		return true;
	}

	/**
	 * Method to save a record.
	 *
	 * @param   int  $recordKey  The primary key of the item (if exists)
	 *
	 * @return  int|boolean  The record ID on success, false on failure
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function save($recordKey = null)
	{
		/** @var \Joomla\CMS\MVC\Model\AdminModel $model */
		$model = $this->getModel();

		try
		{
			$table = $model->getTable();
		}
		catch (\Exception $e)
		{
			$this->setMessage($e->getMessage());

			return false;
		}

		$key        = $table->getKeyName();
		$data       = json_decode($this->input->json->getRaw(), true);
		$checkin    = property_exists($table, $table->getColumnAlias('checked_out'));
		$data[$key] = $recordKey;

		// TODO: Not the cleanest thing ever but it works...
		\JForm::addFormPath(JPATH_COMPONENT_ADMINISTRATOR . '/forms');

		// Validate the posted data.
		$form = $model->getForm($data, false);

		if (!$form)
		{
			$this->setMessage($model->getError(), 'error');

			return false;
		}

		// Test whether the data is valid.
		$validData = $model->validate($form, $data);

		// Check for validation errors.
		if ($validData === false)
		{
			// Get the validation messages.
			$errors = $model->getErrors();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof \Exception)
				{
					$this->setMessage($errors[$i]->getMessage(), 'warning');
				}
				else
				{
					$this->setMessage($errors[$i], 'warning');
				}
			}

			return false;
		}

		if (!isset($validData['tags']))
		{
			$validData['tags'] = array();
		}

		// Attempt to save the data.
		if (!$model->save($validData))
		{
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $model->getError()), 'error');

			return false;
		}

		try
		{
			$modelName = $model->getName();
		}
		catch (\Exception $e)
		{
			$this->setMessage($e->getMessage());

			return false;
		}

		// Ensure we have the record ID in case we created a new article
		$recordId = $model->getState($modelName . '.id');

		if ($recordId === null)
		{
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()), 'error');

			return false;
		}

		// Save succeeded, so check-in the record.
		if ($checkin && $model->checkin($recordId) === false)
		{
			// Check-in failed, so go back to the record and display a notice.
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()), 'error');

			return false;
		}

		return $recordId;
	}

	/**
	 * Method to check if you can edit an existing record.
	 *
	 * Extended classes can override this if necessary.
	 *
	 * @param   array   $data  An array of input data.
	 * @param   string  $key   The name of the key for the primary key; default is id.
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		return \JFactory::getUser()->authorise('core.edit', $this->option);
	}

	/**
	 * Method to check if you can add a new record.
	 *
	 * Extended classes can override this if necessary.
	 *
	 * @param   array  $data  An array of input data.
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function allowAdd($data = array())
	{
		$user = \JFactory::getUser();

		return $user->authorise('core.create', $this->option) || count($user->getAuthorisedCategories($this->option, 'core.create'));
	}
}