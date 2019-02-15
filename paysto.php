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
 * @author    Goryachev Dmitry    <dariusakafest@gmail.com>
 * @copyright 2007-2019 Goryachev Dmitry
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

require_once(dirname(__FILE__) . '/classes/tools/config.php');

class PaySto extends ModulePPM
{
    
    /**
     * @var int
     */
    private $timeout = 80;
    /**
     * @var int
     */
    private $connectionTimeout = 30;
    /**
     * @var bool
     */
    private $keepAlive = true;
    /**
     * @var resource
     */
    private $curl;
    /**
     * @var LoggerInterface|null
     */
    private $logger;
    
    /**
     * Payment action url
     * @var string
     */
    public $url = 'https://paysto.com/ru/pay/AuthorizeNet';
    
    
    /**
     * PaySto constructor.
     */
    public function __construct()
    {
        $this->name = 'paysto';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        //$this->currencies = true;
        //$this->currencies_mode = 'radio';
        $this->bootstrap = true;
        $this->author = 'PrestaInfo';
        $this->need_instance = 0;
        
        parent::__construct();
        $this->documentation = false;
        
        $this->displayName = $this->l('PaySto');
        $this->description = $this->l('Allows you to accept payments through PaySto');
        $this->confirmUninstall = $this->l('Are you sure you want to delete?');
        
        $this->config = [
            'paysto_merchant_id' => '',
            'paysto_secret' => '',
            'status_paysto' => '',
            'server_list' => '95.213.209.218
95.213.209.219
95.213.209.220
95.213.209.221
95.213.209.222',
            'ip_only_from_server_list' => true,
            'disable_tax_shop' => false,
            'tax_delivery' => 'N'
        ];
        
        $this->hooks = [
            'displayPayment',
            'paymentOptions',
            'displayHeader',
            'displayOrderDetail',
            'displayPaymentReturn',
            'displayAdminOrder',
            'displayAdminProductsExtra',
            'actionProductDelete',
            'displayBackOfficeHeader'
        ];
        
        $this->classes = [
            'PaymentTransaction'
        ];
    }
    
    /**
     * Install function
     * @return bool
     */
    public function install()
    {
        return parent::install() && $this->createStatus() && $this->installTables();
    }
    
    /**
     * Uninstall
     * @return bool
     */
    public function uninstall()
    {
        $this->deleteStatus();
        $this->uninstallTables();
        return parent::uninstall();
    }
    
    /**
     * Create nessessary tables
     * @return bool
     */
    public function installTables()
    {
        HelperDbPPM::loadClass('Product')->installManyToOne(
            'tax',
            [
                'tax' => ['type' => ObjectModelPPM::TYPE_STRING, 'validate' => ValidateTypePPM::IS_STRING]
            ]
        );
        return true;
    }
    
    /**
     * Drop nesssessary tables
     */
    public function uninstallTables()
    {
        HelperDbPPM::loadClass('Product')->deleteManyToOne('tax');
    }
    
    /**
     * Create nessesary order status
     * @return mixed
     */
    public function createStatus()
    {
        $name = [
            'en' => 'Pending payment PaySto',
            'ru' => 'В ожидании оплаты PaySto'
        ];
        
        $order_state = new OrderState();
        foreach (ToolsModulePPM::getLanguages(false) as $l) {
            $order_state->name[$l['id_lang']] = (
            isset($name[$l['iso_code']])
                ? $name[$l['iso_code']]
                : $name['en']
            );
        }
        
        $order_state->template = '';
        $order_state->send_email = 0;
        $order_state->module_name = $this->name;
        $order_state->invoice = 0;
        $order_state->color = '#4169E1';
        $order_state->unremovable = 0;
        $order_state->logable = 0;
        $order_state->delivery = 0;
        $order_state->hidden = 0;
        $order_state->shipped = 0;
        $order_state->paid = 0;
        $order_state->pdf_invoice = 0;
        $order_state->pdf_delivery = 0;
        $order_state->deleted = 0;
        $result = $order_state->save();
        ConfPPM::setConf('status_paysto', $order_state->id);
        return $result;
    }
    
    /**
     * Delete order status created in hook createStatus
     */
    public function deleteStatus()
    {
        $order_status = new OrderState((int)ConfPPM::getConf('status_paysto'));
        if (Validate::isLoadedObject($order_status)) {
            $order_status->delete();
        }
    }
    
    /**
     * Save admin plugin form with validation
     */
    public function postProcess()
    {
        if (Tools::isSubmit('saveSettings')) {
            ConfPPM::setConf(
                'paysto_merchant_id',
                Tools::getValue(ConfPPM::formatConfName('paysto_merchant_id'))
            );
            ConfPPM::setConf(
                'paysto_secret',
                Tools::getValue(ConfPPM::formatConfName('paysto_secret'))
            );
            ConfPPM::setConf('server_list', Tools::getValue(ConfPPM::formatConfName('server_list')));
            ConfPPM::setConf('ip_only_from_server_list',
                Tools::getValue(ConfPPM::formatConfName('ip_only_from_server_list')));
            ConfPPM::setConf('disable_tax_shop',
                Tools::getValue(ConfPPM::formatConfName('disable_tax_shop')));
            ConfPPM::setConf('tax_delivery', Tools::getValue(ConfPPM::formatConfName('tax_delivery')));
            Tools::redirectAdmin(ToolsModulePPM::getModuleTabAdminLink() . '&conf=6');
        }
    }
    
    /**
     * Save
     * @return string|void
     */
    public function getContent()
    {
        $this->postProcess();
        ToolsModulePPM::registerSmartyFunctions();
        $this->context->controller->addCSS($this->getPathUri() . 'views/css/admin.css');
        $helper_form = new HelperForm();
        $helper_form->bootstrap = true;
        $helper_form->fields_value = [
            ConfPPM::formatConfName('paysto_merchant_id') => ConfPPM::getConf('paysto_merchant_id'),
            ConfPPM::formatConfName('paysto_secret') => ConfPPM::getConf('paysto_secret'),
            ConfPPM::formatConfName('server_list') => ConfPPM::getConf('server_list'),
            ConfPPM::formatConfName('ip_only_from_server_list') => ConfPPM::getConf('ip_only_from_server_list'),
            ConfPPM::formatConfName('disable_tax_shop') => ConfPPM::getConf('disable_tax_shop'),
            ConfPPM::formatConfName('tax_delivery') => ConfPPM::getConf('tax_delivery')
        ];
        $helper_form->submit_action = 'saveSettings';
        $helper_form->module = $this;
        $helper_form->show_toolbar = true;
        $helper_form->toolbar_btn = [
            'save' => [
                'title' => $this->l('Save')
            ]
        ];
        $helper_form->token = Tools::getAdminTokenLite('AdminModules');
        $helper_form->currentIndex = ToolsModulePPM::getModuleTabAdminLink();
        $form = new FormBuilderPPM($this->displayName);
        $form->addField(
            $this->l('Merchant ID'),
            ConfPPM::formatConfName('paysto_merchant_id'),
            'text'
        );
        $form->addField(
            $this->l('Secret'),
            ConfPPM::formatConfName('paysto_secret'),
            'text'
        );
        $form->addField(
            $this->l('Receive callback only from servers list'),
            ConfPPM::formatConfName('ip_only_from_server_list'),
            'switch'
        );
        $form->addField(
            $this->l('Server list'),
            ConfPPM::formatConfName('server_list'),
            'textarea'
        );
        $form->addField(
            $this->l('Use tax mode from module(tax mode shop will be disabled)'),
            ConfPPM::formatConfName('disable_tax_shop'),
            'switch'
        );
        $form->addField(
            $this->l('Delivery tax(When enabled tax mode from module)'),
            ConfPPM::formatConfName('tax_delivery'),
            'select',
            null,
            null,
            null,
            null,
            null,
            [
                'query' => $this->getTaxes(),
                'id' => 'id',
                'name' => 'name'
            ]
        );
        
        $form->addSubmit($this->l('Save'));
        return $helper_form->generateForm([
            [
                'form' => $form->getForm()
            ]
        ]);
    }
    
    /**
     * Get payment from in checkout
     * @param  [type] $id_order [description]
     * @return [type]           [description]
     */
    public function getPaymentUrl($id_order)
    {
    
        $x_fp_timestamp = time();
        $order = new Order($id_order);
        $cart = new Cart($order->id_cart);
        
        $currency = new Currency($order->id_currency);
        $order_id = $order->id;
        $description = $this->l('Payment order ') . ' №' . $order_id;
        $paysto_merchant_id = ConfPPM::getConf('paysto_merchant_id');
        $x_relay_url = $_SERVER['HTTP_ORIGIN'].__PS_BASE_URI__.'module/paysto/result';
        
        $order_amount = number_format(($order->total_products_wt + $order->total_shipping_tax_incl), 2, '.', '');
        
        // Not right RUR for rubles iso_code = RUB
        $iso_code = $currency->iso_code;
        if ($iso_code == 'RUR') {
            $iso_code = 'RUB';
        }
        
        $address = new Address($cart->id_address_delivery);
        $customer = new Customer($order->id_customer);
        
        $products = $cart->getProducts(true);
        
        foreach ($products as &$product) {
            $price_item_with_tax = Product::getPriceStatic(
                $product['id_product'],
                true,
                $product['id_product_attribute']
            );
            $price_item_with_tax = number_format(
                $price_item_with_tax,
                2,
                '.',
                ''
            );
            
            $product['price_item_with_tax'] = $price_item_with_tax;
            
            
            if (!ConfPPM::getConf('disable_tax_shop')) {
                if (Configuration::get('PS_TAX')) {
                    $rate = $product['rate'];
                    switch ($rate) {
                        case 10:
                            $product['tax_value'] = 'Y';
                            break;
                        case 18:
                            $product['tax_value'] = 'Y';
                            break;
                        case 20:
                            $product['tax_value'] = 'Y';
                            break;
                        default:
                            $product['tax_value'] = 'N';
                    }
                } else {
                    $product['tax_value'] = 'N';
                }
            } else {
                $product['tax_value'] = $this->getProductTax($product['id_product']);
            }
        }
        
        $params = [
            'x_description' => $description,
            'x_login' => $paysto_merchant_id,
            'x_amount' => $order_amount,
            'x_email' => $customer->email,
            'x_currency_code' => $iso_code,
            'x_fp_sequence' => $order_id,
            'x_fp_timestamp' => $x_fp_timestamp,
            'x_fp_hash' => $this->get_x_fp_hash($paysto_merchant_id, $order_id, $x_fp_timestamp,
                $order_amount, $iso_code),
            'x_invoice_num' => $order_id,
            'x_relay_response' => "TRUE",
            'x_relay_url' => $x_relay_url
        ];
        
        $params['x_line_item'] = '';
        
        if (is_array($products) && count($products)) {
            $tax_value_shipping = ConfPPM::getConf('tax_delivery');
            $products = $cart->getProducts(true);
            foreach ($products as $pos => $product) {
                if (!ConfPPM::getConf('disable_tax_shop')) {
                    $carrier = new Carrier((int)$cart->id_carrier);
                    
                    $tax_value = 'no_vat';
                    if (Configuration::get('PS_TAX')) {
                        $rate = $carrier->getTaxesRate($address);
                        switch ($rate) {
                            case 10:
                                $tax_value = 'Y';
                                break;
                            case 18:
                                $tax_value = 'Y';
                                break;
                            case 20:
                                $tax_value = 'Y';
                                break;
                            default:
                                $tax_value = 'N';
                        }
                    }
                } else {
                    $tax_value = ConfPPM::getConf('tax_delivery');
                }
    
                $lineArr = array();
                $lineArr[] = '№' . $pos . "  ";
                $lineArr[] = substr($product['id_product'], 0, 30);
                $lineArr[] = substr($product['name'], 0, 254);
                $lineArr[] = substr($product['cart_quantity'], 0, 254);
                $lineArr[] = number_format($product['price_wt'], 2, '.', '');
                $lineArr[] = $tax_value;
                $params['x_line_item'] .= implode('<|>', $lineArr) . "0<|>\n";
            }
            
            if ($order->total_shipping_tax_incl) {
                $pos++;
                $lineArr = array();
                $lineArr[] = '№' . $pos . "  ";
                $lineArr[] =$this->l('Shipping');
                $lineArr[] = $this->l('Shipping') .' '. $order_id;
                $lineArr[] = 1;
                $lineArr[] =number_format($order->total_shipping_tax_incl, 2,
                    '.', '');
                $lineArr[] = $tax_value_shipping;
                $params['x_line_item'] .= implode('<|>', $lineArr) . "0<|>\n";
            }
        }
        
        return ['url' => $this->url, 'params' => $params];
        
    }
    
    
    /**
     * Submit payment form
     * @param $url
     * @param array $data
     */
    public function makeSubmitForm($url, array $params)
    {
        ?>
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <script type="text/javascript">
                function closethisasap() {
                    document.forms["redirectpost"].submit();
                }
            </script>
        </head>
        <body onload="closethisasap();">
        <form name="redirectpost" method="post" action="<?php echo $url; ?>">
            <?php
            if (!is_null($params)) {
                foreach ($params as $k => $v) {
                    echo '<input type="hidden" name="' . $k . '" value="' . $v . '"> ';
                }
            }
            ?>
        </form>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Return hash md5 HMAC
     * @param $x_login
     * @param $x_fp_sequence
     * @param $x_fp_timestamp
     * @param $x_amount
     * @param $x_currency_code
     * @return false|string
     */
    public function get_x_fp_hash($x_login, $x_fp_sequence, $x_fp_timestamp, $x_amount, $x_currency_code)
    {
        $arr = array($x_login, $x_fp_sequence, $x_fp_timestamp, $x_amount, $x_currency_code);
        $str = implode('^', $arr);
        return hash_hmac('md5', $str, ConfPPM::getConf('paysto_secret'));
    }
    
    /**
     * Return sign with MD5 algoritm
     * @param $x_login
     * @param $x_trans_id
     * @param $x_amount
     * @return string
     */
    public function get_x_MD5_Hash($x_login, $x_trans_id, $x_amount)
    {
        return md5(ConfPPM::getConf('paysto_secret') . $x_login . $x_trans_id . $x_amount);
    }
    
    /**
     * Check if IP in acceptable IPs list
     * @return bool
     */
    public function checkInServerList()
    {
        $serverList = preg_split('/\r\n|[\r\n]/', ConfPPM::getConf('server_list'));
        $myIP = array();
        $myIP[] = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '127.0.0.1';
        $myIP[] = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : '127.0.0.1';
        $myIP[] = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : '127.0.0.1';
        $myIP[] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        $myIP[] = isset($_SERVER['GEOIP_ADDR']) ? $_SERVER['GEOIP_ADDR'] : '127.0.0.1';
        if (empty(array_intersect($serverList, $myIP))) {
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * Logger function for debug
     * @param  [type] $var  [description]
     * @param  string $text [description]
     * @return [type]       [description]
     */
    public function logger($var, $text = '')
    {
        // file name
        $loggerFile = __DIR__ . '/logger.log';
        if (is_object($var) || is_array($var)) {
            $var = (string)print_r($var, true);
        } else {
            $var = (string)$var;
        }
        $string = date("Y-m-d H:i:s") . " - " . $text . ' - ' . $var . "\n";
        file_put_contents($loggerFile, $string, FILE_APPEND);
    }
    
    /**
     * Get total amount of order
     * hier I found the bug
     * @param  [type] $cart [description]
     * @return [type]       [description]
     */
    public function getTotalCart($cart)
    {
        $products = $cart->getProducts(true);
        $total_products = 0;
        foreach ($products as &$product) {
            $price_item_with_tax = Product::getPriceStatic(
                $product['id_product'],
                true,
                $product['id_product_attribute']
            );
            $price_item_with_tax = (float)number_format(
                $price_item_with_tax,
                2,
                '.',
                ''
            );
            $total_products += ($price_item_with_tax * $product['cart_quantity']);
        }
        
        $total_shipping = $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        $total_wrapping = $cart->getOrderTotal(true, Cart::ONLY_WRAPPING);
        
        $amount = number_format(
            $total_products + $total_wrapping + $total_shipping,
            2,
            '.',
            ''
        );
        return $amount;
    }
    
    /**
     * Redirect methods
     * @param $url
     * @param bool $permanent
     */
    public function redirect($url, $permanent = false)
    {
        header('Location: ' . $url, true, $permanent ? 301 : 302);
        exit();
    }
    
    /**
     * Display header hook
     */
    public function hookDisplayHeader()
    {
        if (Tools::getValue('LMI_MERCHANT_ID')
            && Tools::getValue('LMI_MERCHANT_ID') == ConfPPM::getConf('paysto_merchant_id')
            && $this->context->controller instanceof IndexController) {
            if (Tools::getValue('LMI_MERCHANT_ID')) {
                Tools::redirect(
                    $this->context->link->getModuleLink(
                        $this->name,
                        'success',
                        [
                            'LMI_MERCHANT_ID' => Tools::getValue('LMI_MERCHANT_ID'),
                            'LMI_PAYMENT_NO' => Tools::getValue('LMI_PAYMENT_NO'),
                        ],
                        true
                    )
                );
            } else {
                Tools::redirect(
                    $this->context->link->getModuleLink(
                        $this->name,
                        'fail',
                        [
                            'LMI_MERCHANT_ID' => Tools::getValue('LMI_MERCHANT_ID'),
                            'LMI_PAYMENT_NO' => Tools::getValue('LMI_PAYMENT_NO')
                        ],
                        true
                    )
                );
            }
        }
        
        $this->context->controller->addCSS($this->getPathUri() . 'views/css/front.css');
    }
    
    /**
     * Display payment hooks
     * @param $params
     * @return string
     */
    public function hookDisplayPayment($params)
    {
        $this->context->smarty->assign([
            'paysto' => [
                'img_dir' => _MODULE_DIR_ . $this->name . '/views/img/',
                'validation' => $this->context->link->getModuleLink(
                    $this->name,
                    'validation'
                )
            ],
        ]);
        return ToolsModulePPM::fetchTemplate('hook/payment.tpl');
    }
    
    /**
     * Payment option hook
     * @return array
     */
    public function hookPaymentOptions()
    {
        $new_option = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $new_option->setCallToActionText($this->displayName)->setAction(
            $this->context->link->getModuleLink(
                $this->name,
                'validation'
            )
        )->setAdditionalInformation(
            $this->l('Pay with PaySto')
        );
        
        return [$new_option];
    }
    
    /**
     * Display order detail hook
     * @param $params
     * @return mixed
     *
     */
    public function hookDisplayOrderDetail($params)
    {
        /**
         * @var Order $order
         */
        $order = $params['order'];
        if ($order->module == $this->name
            && ($order->current_state == (int)Configuration::get('PS_OS_ERROR')
                || $order->current_state == (int)ConfPPM::getConf('status_paysto'))) {
            $link_payment_again = $this->context->link->getModuleLink($this->name, 'paymentagain', [
                'id_order' => $order->id
            ]);
            $this->context->smarty->assign('link_payment_again', $link_payment_again);
            return $this->display(__FILE__, 'order_detail.tpl');
        } else {
            $payment_transaction = PaymentTransaction::getInstanceByOrder(
                $order->id
            );
            if ($payment_transaction) {
                $this->context->smarty->assign(
                    'payment_transaction',
                    $payment_transaction
                );
                return $this->display(__FILE__, 'order_detail_payment_transaction.tpl');
            }
        }
    }
    
    /**
     * Display admin order hook
     * @param $params
     * @return string
     */
    public function hookDisplayAdminOrder($params)
    {
        $id_order = (int)$params['id_order'];
        $order = new Order($id_order);
        if (Validate::isLoadedObject($order)) {
            $payment_transaction = PaymentTransaction::getInstanceByOrder(
                $order->id
            );
            if ($payment_transaction) {
                $this->context->smarty->assign(
                    'payment_transaction',
                    $payment_transaction
                );
                return $this->display(__FILE__, 'order_detail_payment_transaction.tpl');
            }
        }
        return '';
    }
    
    /**
     * Payment return hook
     * @param $params
     * @return string
     */
    public function hookDisplayPaymentReturn($params)
    {
        if (!$this->active) {
            return '';
        }
        ToolsModulePPM::registerSmartyFunctions();
        
        /**
         * @var Order $order
         */
        if (version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
            $order = $params['objOrder'];
        } else {
            $order = $params['order'];
        }
        
        $id_order_state = (int)$order->getCurrentState();
        $order_status = new OrderState((int)$id_order_state, (int)$order->id_lang);
        $products = $order->getProducts();
        $customized_datas = Product::getAllCustomizedDatas((int)$order->id_cart);
        Product::addCustomizationPrice($products, $customized_datas);
        
        $this->context->smarty->assign([
            'status' => 'ok',
            'id_order' => $order->id,
            'total_to_pay' => $order->getTotalPaid(),
            'logable' => (bool)$order_status->logable,
            'products' => $products,
            'is_guest' => false
        ]);
        
        return $this->display(__FILE__, 'payment_return.tpl');
    }
    
    /**
     * Display product
     * @param $params
     * @return string
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        ToolsModulePPM::registerSmartyFunctions();
        $id_product = (int)Tools::getValue('id_product');
        if (isset($params['id_product'])) {
            $id_product = $params['id_product'];
        }
        
        return ToolsModulePPM::fetchTemplate('hook/admin_products_extra.tpl', [
            'taxes' => $this->getTaxes(),
            'product_tax' => $this->getProductTax($id_product),
            'id_product' => $id_product
        ]);
    }
    
    /**
     * Set header
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('ppm_ajax')) {
            ToolsModulePPM::createAjaxApiCall($this);
        }
    }
    
    /**
     * Tax ajax save
     * @return array
     */
    public function ajaxProcessSaveProductTax()
    {
        $id_product = (int)Tools::getValue('id_product');
        $tax = Tools::getValue('tax');
        $this->setProductTax($id_product, $tax);
        return [
            'message' => $this->l('Save successfully!')
        ];
    }
    
    /**
     * Delete tax when product was deleted
     * @param $params
     */
    public function hookActionProductDelete($params)
    {
        $id_product = isset($params['product'])
        && $params['product'] instanceof Product ? $params['product']->id : null;
        
        if ($id_product) {
            $this->deleteProductTax($id_product);
        }
    }
    
    /**
     * Get product tax
     * @param $id_product
     * @return mixed|string
     */
    public function getProductTax($id_product)
    {
        $tax = Db::getInstance()->getValue('SELECT `tax`
        FROM `' . _DB_PREFIX_ . 'product_tax`
        WHERE `id_product` = ' . (int)$id_product);
        return ($tax ? $tax : 'N');
    }
    
    /**
     * Set tax rate for product
     * @param $id_product
     * @param $tax
     */
    public function setProductTax($id_product, $tax)
    {
        $this->deleteProductTax($id_product);
        Db::getInstance()->insert('product_tax', [
            'id_product' => $id_product,
            'tax' => $tax
        ]);
    }
    
    /**
     * Delete VAT taxes from table
     * @param $id_product
     */
    public function deleteProductTax($id_product)
    {
        Db::getInstance()->delete('product_tax', 'id_product = ' . (int)$id_product);
    }
    
    /**
     * Get tax rate for select
     * @return array
     */
    public function getTaxes()
    {
        $t = TransModPPM::getInstance();
        return [
            ['id' => 'N', 'name' => $t->ld('Without VAT')],
            ['id' => 'Y', 'name' => $t->ld('With VAT')],
        ];
    }
    
}
