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

class PayStoResultModuleFrontController extends ModuleFrontControllerPPM
{
    public function initContent()
    {
        $server = &${'_SERVER'};
        if ($server['REQUEST_METHOD'] == 'POST') {
            $id_order = Tools::getValue('x_invoice_num');
            $x_amount = Tools::getValue('x_amount');
            $x_login = ConfPPM::getConf('paysto_secret');
            $x_trans_id = Tools::getValue('x_trans_id');
            $generated_x_MD5_Hash = $this->module->get_x_MD5_Hash($x_login, $x_trans_id, $x_amount);
            $x_response_code = Tools::getValue('x_response_code');
            $ip_only_from_server_list = ConfPPM::getConf('ip_only_from_server_list');
            $order = new Order($id_order);
            $success_url =  Tools::getHttpHost(false).__PS_BASE_URI__.'/module/paysto/success';
            $fail_url =  Tools::getHttpHost(false).__PS_BASE_URI__.'/module/paysto/fail';
            
            if ($ip_only_from_server_list && !$this->module->checkInServerList()
                && ($order->getCurrentState() != Configuration::get('PS_OS_PAYMENT'))) {
                    $this->module->redirect($fail_url);
            } else {
                if ($x_response_code == 1 && $order->getCurrentState() != Configuration::get('PS_OS_PAYMENT')) {
                    if (Validate::isLoadedObject($order)) {
                        $order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
                        PaymentTransaction::createTransaction($order->id, Tools::getValue('LMI_SYS_PAYMENT_ID'));
                    } else {
                        die();
                    }
                } else {
                    $this->module->redirect($success_url);
                }
            }
        }
        $this->module->redirect($fail_url);
    }
}
