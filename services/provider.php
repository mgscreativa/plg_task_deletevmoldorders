<?php
/**
 * VirtueMart delete old orders task plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Task.Deletevmoldorders
 *
 * @author MGS Creativa
 * @url https://www.mgscreativa.com
 * @copyright Copyright (C) 2014 MGS Creativa - All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 *
 *  VirtueMart is free software. This version may have been modified pursuant
 *  to the GNU General Public License, and as distributed it includes or
 *  is derivative of works licensed under the GNU General Public License or
 *  other free or open source software licenses.
 *  See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 *  https://virtuemart.org
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Task\Deletevmoldorders\Extension\Deletevmoldorders;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     * @since   5.0.0
     */
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $deletevmoldorders = new Deletevmoldorders(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('task', 'deletevmoldorders')
                );
	            $deletevmoldorders->setDatabase($container->get(DatabaseInterface::class));

                return $deletevmoldorders;
            }
        );
    }
};
