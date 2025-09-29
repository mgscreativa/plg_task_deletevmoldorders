<?php
/**
 * VirtueMart delete old orders task plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Task.Deletevmoldorders
 *
 * @author      MGS Creativa
 * @url https://www.mgscreativa.com
 * @copyright   Copyright (C) 2014 MGS Creativa - All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 *
 *  VirtueMart is free software. This version may have been modified pursuant
 *  to the GNU General Public License, and as distributed it includes or
 *  is derivative of works licensed under the GNU General Public License or
 *  other free or open source software licenses.
 *  See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 *  http://virtuemart.org
 */

namespace Joomla\Plugin\Task\Deletevmoldorders\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Event\SubscriberInterface;

/**
 * Task plugin with routines to check in a checked out item.
 *
 * @since  5.0.0
 */
class Deletevmoldorders extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;

	/**
	 * @var string[]
	 * @since 5.0.0
	 */
	protected const TASKS_MAP = [
		'plg_task_deletevmoldorders_task_get' => [
			'langConstPrefix' => 'PLG_TASK_DELETEVMOLDORDERS',
			'form'            => 'deletevmoldorders_params',
			'method'          => 'deleteOldOrders',
		],
	];

	/**
	 * @var boolean
	 * @since 5.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * @inheritDoc
	 *
	 * @return string[]
	 *
	 * @since 5.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onTaskOptionsList'    => 'advertiseRoutines',
			'onExecuteTask'        => 'standardRoutineHandler',
			'onContentPrepareForm' => 'enhanceTaskItemForm',
		];
	}

	/**
	 * Standard method for the old orders delete routine.
	 *
	 * @param   ExecuteTaskEvent  $event  The onExecuteTask event
	 *
	 * @return  integer  The exit code
	 *
	 * @since   5.0.0
	 */
	protected function deleteOldOrders(ExecuteTaskEvent $event): int
	{
		$this->logTask('VirtueMart delete old orders start');

		$db                    = $this->getDatabase();
		$olderThan             = (int) $event->getArgument('params')->older_than ?? 1;
		$filterOrderStatus     = $event->getArgument('params')->filter_order_status ?? '';
		$limitProcess          = (int) $event->getArgument('params')->limit_process ?? 0;
		$filterOrderStatusText = '';
		$failed                = false;

		$this->logTask('olderThan ' . $olderThan);
		$this->logTask('filterOrderStatus ' . $filterOrderStatus);
		$this->logTask('limitProcess ' . $limitProcess);

		$jnow   = Factory::getDate();
		$minusT = date('Y-m-d H:i:s', strtotime('-' . $olderThan . ' days', strtotime($jnow)));

		$q = 'SELECT virtuemart_order_id FROM `#__virtuemart_orders` WHERE ';
		$q .= '`created_on` < "' . $minusT . '" ';

		if (!empty($filterOrderStatus))
		{
			$filterOrderStatus = explode(',', $filterOrderStatus);

			if (is_array($filterOrderStatus))
			{
				$lenght = count($filterOrderStatus);

				foreach ($filterOrderStatus as $index => $status)
				{
					$filterOrderStatusText .= "'" . $status . "',";

					if ($index == $lenght - 1)
					{
						$filterOrderStatusText = rtrim($filterOrderStatusText, ',');
					}
				}

				$q .= ' AND `order_status` IN (' . $filterOrderStatusText . ') ';
			}
		}

		if (!empty($limitProcess))
		{
			$q .= ' LIMIT 50';
		}

		$this->logTask('Old orders search query ' . $q);

		$db->setQuery($q);

		try
		{
			$virtuemart_order_ids = $db->loadRowList();

			if (empty($virtuemart_order_ids))
			{
				$this->logTask('No orders older than ' . $minusT . ' found.');
				$this->logTask('VirtueMart delete old orders end');

				return TaskStatus::OK;
			}

			$order_ids = array();
			foreach ($virtuemart_order_ids as $oid)
			{
				$order_ids[] = $oid[0];
			}

			$this->logTask('Order IDs to remove ' . json_encode($order_ids));

			$this->logTask('Setting up VM');

			if (!class_exists( 'VmConfig' )) {
				require(JPATH_ROOT . '/administrator/components/com_virtuemart/helpers/config.php');
			}

			\VmConfig::loadConfig();
			\vmLanguage::loadJLang('com_virtuemart', true);

			$this->logTask('Deleting Vm orders older than ' . $minusT . ' with status ' . $filterOrderStatusText);

			$odersModel = \VmModel::getModel('orders');
			$odersModel->remove($order_ids, false);
		}
		catch (ExecutionFailureException)
		{
			$failed = true;
		}

		$this->logTask('VirtueMart delete old orders end');

		return $failed ? TaskStatus::INVALID_EXIT : TaskStatus::OK;
	}
}
