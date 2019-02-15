<?php
/**
 * 2007-2019 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2012-2019 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

class PayStoPaymentAgainModuleFrontController extends ModuleFrontControllerPPM
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $order = new Order((int)Tools::getValue('id_order'));
        if (Validate::isLoadedObject($order)
            && $order->secure_key == $this->context->customer->secure_key) {
            $returned = $this->module->getPaymentUrl($order->id);
            Tools::redirect($returned['url']);
        } else {
            Tools::redirect($this->context->link->getPageLink('index'));
        }
        die();
    }
}
