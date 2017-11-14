<?php
namespace Monarkhov\Qiwi;

class QiwiAPI
{
    private $url = 'https://edge.qiwi.com/';

    const CURRENCY_RUB = 643;
    const CURRENCY_KZT = 398;

    const PROVIDER_QIWI = 99;
    const PROVIDER_VISA = 1963;

    const OPERATION_ALL = 'ALL';
    const OPERATION_IN = 'IN';
    const OPERATION_OUT = 'OUT';
    const OPERATION_QIWI_CARD = 'QIWI_CARD';

    private $token;
    private $purse;

    public function __construct($purse, $token)
    {
        $this->purse = $purse;
        $this->token = $token;
    }

    public function request($type = 'GET', $method, $data = null, $headers = null)
    {
        $ch = curl_init();
        $headers[] = "Authorization: Bearer {$this->token}";
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        if ($type=='POST') {
            curl_setopt($ch, CURLOPT_URL, $this->url.$method);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_URL, $this->url.$method.(is_array($data)?'?'.http_build_query($data):(($data!==null)?'?'.$data:'')));
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public function transferMoney($purse, $currency, $sum, $comment = null)
    {
        $id = rand(1111, 9999) * time();
        $query['id'] = "$id";
        $query['sum']['amount'] = $sum;
        $query['sum']['currency'] = "$currency";
        $query['paymentMethod']['type'] = 'Account';
        $query['paymentMethod']['accountId'] = "$currency";
        $query['fields']['account'] = $purse;
        if ($comment!=null) {
            $query['comment'] = $comment;
        }
        return json_decode($this->request('POST', 'sinap/api/v2/terms/99/payments', json_encode($query)), true);
    }

    public function getHistory($rows = 50, $operation = 'ALL', $start = null, $end = null)
    {
        $start = date("Y-m-d", $start).'T00:00:00+03:00';
        $end = date("Y-m-d", $end).'T23:59:59+03:00';
        return json_decode($this->request('GET', 'payment-history/v1/persons/'.$this->purse.'/payments', ['rows' => $rows, 'startDate' => $start, 'endDate' => $end, 'operation' => $operation]));
    }
}
