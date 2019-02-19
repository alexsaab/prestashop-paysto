{*
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
*}

{extends file="helpers/form/form.tpl"}

{block name="defaultForm"}
    <div class="{if {versionCompare v='1.6.0.0' op='<'}}ps_15{/if}">
        <div class="form-group clearfix">
            <div class="col-lg-12">
                <button class="btn btn-default" onclick="$('.doc_panel').toggle('slow');">
                    {l s='Documentation' mod='paysto'}
                </button>
            </div>
        </div>
        <div style="display: none" class="doc_panel form-group clearfix">
            <div class="col-lg-12">
                <div class="panel">
                    <div class="panel-heading">
                        {l s='Documentation' mod='paysto'}
                    </div>
                    <p>
                        <ul>
                            <li>Настройка модуля: <br/>
                                Перед тем, как начать пользоваться модулем, его нужно настроить. Для этого вы должны быть
                                подключены к платежной системе Paysto. Там вам нужно будет добавить свой сайт, после чего
                                будет доступен код магазина (Merchant id), и возможность создать свой секретный ключ. Эти
                                параметры вам также нужно будет прописать в настройках модуля.
                            </li>
                            <li>Для лучшей безопасности вам нужно будет установить разрешения для осуществления обратных
                                вызовов только от определенных IP адресов серверов Paysto: <br/>
                                95.213.209.218, <br/>
                                95.213.209.219, <br/>
                                95.213.209.220, <br/>
                                95.213.209.221, <br/>
                                95.213.209.222
                            </li>
                            <li> Дополнительные настройки: <br/>
                                Перед оплатой заказа в Paysto передаются данные для Онлайн-Кассы: налог на товар, название,
                                цена, количество. <br/>
                                Налог на товар настраивается для каждого товара отдельно. Для этого используется стандартный
                                функционал Prestashop, который находится в форме редактирования товара, на вкладке “цены”.
                                Значение “Не начислять” равно 0% налога. Чтобы эта опция работала, налог в магазине должен
                                быть включен. Если налог отключен, то в данных для онлайн-кассы налог будет указан, как “нет
                                налога”.
                            </li>
                            <li>Кроме того вам необходиом будет указать ставку НДС для доставки. Есть или нет.</li>
                         </ul>
                        <br/>
                    </p>
                </div>
            </div>
        </div>
    </div>
    {$smarty.block.parent}
{/block}

{block name="input"}
    {if {versionCompare v='1.6.0.0' op='<'} && $input.type == 'html'}
        {$input.html_content|no_escape}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}