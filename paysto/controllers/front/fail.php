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

class PayStoFailModuleFrontController extends ModuleFrontControllerPPM
{
    public $ssl = true;
    public $display_column_left = false;
    
    public function initContent()
    {
        parent::initContent();
        if (Tools::getValue('x_login')
            && Tools::getValue('x_login') == ConfPPM::getConf('paysto_merchant_id')) {
            $order = new Order(Tools::getValue('x_invoice_num'));
            
            if (Validate::isLoadedObject($order)) {
                $link_payment_again = false;
                if (Validate::isLoadedObject($order)) {
                    if ($order->current_state != (int)Configuration::get('PS_OS_ERROR')) {
                        $order->setCurrentState((int)Configuration::get('PS_OS_ERROR'));
                    }
                    $link_payment_again = $this->context->link->getModuleLink(
                        $this->module->name,
                        'paymentagain',
                        [
                            'id_order' => $order->id
                        ]
                    );
                }
                $this->context->smarty->assign('link_payment_again', $link_payment_again);
                $this->context->smarty->assign('path', $this->module->l('fail', 'fail'));
                
                $this->setTemplate('fail.tpl');
            } else {
                Tools::redirect($this->context->link->getPageLink('index'));
            }
        } else {
            die();
        }
    }
}
