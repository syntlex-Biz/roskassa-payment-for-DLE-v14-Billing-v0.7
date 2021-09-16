<?php if (!defined('BILLING_MODULE')) die("Hacking attempt!");

/**
 * RosKassa
 *
 * @link          https://roskassa.net/
 * @faq           https://roskassa.net/docs/
 */

class RosKassa
{
    private $url = 'https://pay.roskassa.net';

    function Settings($config)
    {
        $html = array();

        $html[] = array(
            'ID Магазина:',
            'Из настроек магазина в личном кабинете my.roskassa.net',
            '<input name="save_con[shop_id]" class="edit bk" type="text" value="' . $config['shop_id'] . '" style="width: 100%">',
        );

        $html[] = array(
            'Секретный ключ:',
            'Из настроек магазина в личном кабинете my.roskassa.net',
            '<input name="save_con[secret_key]" class="edit bk" type="password" value="' . $config['secret_key'] . '" style="width: 100%">',
        );

        $html[] = array(
            'Режим для тестирования:',
            'Можете использовать его для тестирования платежей',
            '<select name="save_con[test]" class="uniform">
                <option value="0" ' . ($config['test'] == '0' ? 'selected' : '') . '>Выключить</option>
                <option value="1" ' . ($config['test'] == '1' ? 'selected' : '') . '>Включить</option>
            </select>'
        );

        $html[] = array(
            'Валюта оплаты:',
            'Используется на сайте roskassa.net',
            '<select name="save_con[server_currency]" class="uniform">
                <option value="RUB" ' . ($config['server_currency'] == 'RUB' ? 'selected' : '') . '>RUB</option>
                <option value="USD" ' . ($config['server_currency'] == 'USD' ? 'selected' : '') . '>USD</option>
                <option value="EUR" ' . ($config['server_currency'] == 'EUR' ? 'selected' : '') . '>EUR</option>
            </select>',
        );

        $html[] = array(
            'Язык интерфейса:',
            'Выберите язык интерфейса оплаты.',
            '<select name="save_con[lang]" class="uniform">
                <option value="ru" ' . ($config['lang'] == 'ru' ? 'selected' : '') . '>Русский</option>
                <option value="en" ' . ($config['lang'] == 'en' ? 'selected' : '') . '>Английский</option>
            </select>',
        );

        return $html;
    }

    private function getSign(array $data, $secret_key)
    {
        ksort($data);

        return md5(http_build_query($data) . $secret_key);
    }

    private function getForm(array $data)
    {
        $form = '<form method="post" action="' . $this->url . '" accept-charset="UTF-8" id="paysys_form">';

        foreach ($data as $key => $value)
        {
            $form .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
        }

        $form .= '<input type="submit" class="btn" value="Оплатить" />';
        $form .= '</form>';

        return $form;
    }

    function Form($order_id, $config, $invoice, $description, $DevTools)
    {
        $amount = number_format($invoice['invoice_pay'], 2, '.', '');

        $data = array(
            'shop_id'   => $config['shop_id'],
            'order_id'  => $order_id,
            'amount'    => $amount,
            'currency'  => $config['server_currency']
        );

        if ($config['test'] == 1)
        {
            $data['test'] = 1;
        }

        $signature = $this->getSign($data, $config['secret_key']);

        $data['lang'] = $config['lang'];
        $data['sign'] = $signature;

        return $this->getForm($data);
    }

    function check_id($data)
    {
        return $data["order_id"];
    }

    function check_ok($data)
    {
        return 'YES';
    }

    function check_out($data, $config, $invoice)
    {
        global $_REQUEST;

        if (empty($_REQUEST['sign'])) {
            return 'Error: signature format';
        }

        $data = $_POST;

        unset($data['sign']);

        $signatureGen = $this->getSign($data, $config['secret_key']);

        if ($_REQUEST["amount"] != number_format($invoice['invoice_pay'], 2, '.', '')) {
            return 'Error: amount';
        }

        if ($signatureGen == $_REQUEST["sign"]) {
            return 200;
        } else {
            return 'Error: signature:: ' . $_REQUEST["sign"] . ' != ' . $signatureGen;
        }
    }
}

$Paysys = new RosKassa;