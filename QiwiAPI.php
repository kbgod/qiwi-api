<?php
namespace Monarkhov\Qiwi;

class QiwiAPI
{
    private $url = 'https://edge.qiwi.com/';

    const CURRENCY_RUB = 643;
    const CURRENCY_KZT = 398;
    const CURRENCY_USD = 840;

    const PROVIDER_QIWI = 99;
    const PROVIDER_VISA = 1963;

    const OPERATION_ALL = 'ALL';
    const OPERATION_IN = 'IN';
    const OPERATION_OUT = 'OUT';
    const OPERATION_QIWI_CARD = 'QIWI_CARD';

    const TRANSACTION_STATUS_SUCCESS = 'SUCCESS';

    private $token;
    private $purse;
    private $proxy_server;
    private $proxy_type;
    private $proxy_user;

    public function __construct($purse)
    {
        $this->purse = $purse['number'];
        $this->token = $purse['token'];
        if(isset($purse['proxy_server']) AND mb_strlen($purse['proxy_server'])>0) {
            $this->proxy_server = $purse['proxy_server'];
            $this->proxy_type = $purse['proxy_type'];
            $this->proxy_user = $purse['proxy_user'];
        }
    }

    public function request($type = 'GET', $method, $data = null, $headers = null)
    {
        $ch = curl_init();
        if($this->token!=1) $headers[] = "Authorization: Bearer {$this->token}";
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        if(mb_strlen($this->proxy_server)>0) {
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy_server);
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            switch ($this->proxy_type) {
                case 'socks5':
                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                    break;

                case 'socks4':
                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
                    break;

                default:
                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                    break;
            }
        }
        if(mb_strlen($this->proxy_user)>0) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxy_user);
        }
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

    public function getMe() {
        return json_decode($this->request('GET', 'person-profile/v1/profile/current'));
    }

    public function getBalances() {
        $result = json_decode($this->request('GET', 'funding-sources/v2/persons/'.$this->purse.'/accounts'), true);
        $list = [];
        if($result == false or $result == null) return false;
        if(isset($result['errorCode'])) {
            return ['ok' => false, 'message' => ((isset($result['userMessage']))?$result['userMessage']:'сообщение отсутствует')];
        }
        $result = $result['accounts'];
        foreach ($result as $balance) {
            $alias = explode('_', $balance['alias']);
            if($alias[0]=='qw')$list[] = ['amount' => $balance['balance']['amount'], 'currency' => $balance['currency'], 'currency_caption' => strtoupper($alias[2]) ];
        }
        return ['ok' => true, 'list' => $list];
    }

    public function getCommission($p_id) {
        return json_decode($this->request('GET', 'sinap/providers/'.$p_id.'/form'), true)['content']['terms']['commission']['ranges'][0];
    }

    public function transferToQiwiWallet($purse, $currency, $sum, $comment = null, $accountID = null)
    {
        if($accountID==null) $accountID = $currency;
        $id = rand(1111, 9999) * time();
        $query['id'] = "$id";
        $query['sum']['amount'] = $sum;
        $query['sum']['currency'] = "$currency";
        $query['paymentMethod']['type'] = 'Account';
        $query['paymentMethod']['accountId'] = "$accountID";
        $query['fields']['account'] = $purse;
        if ($comment!=null) {
            $query['comment'] = $comment;
        }
        return json_decode($this->request('POST', 'sinap/api/v2/terms/99/payments', json_encode($query)), true);
    }

    public function transferToWebmoney($purse, $currency, $sum, $accountID = null) {
        if($accountID==null) $accountID = $currency;
        $id = rand(1111, 9999) * time();
        $query['id'] = "$id";
        $query['sum']['amount'] = $sum;
        $query['sum']['currency'] = "$currency";
        $query['paymentMethod']['type'] = 'Account';
        $query['paymentMethod']['accountId'] = "$accountID";
        $query['fields']['account'] = $purse;
        $provider = '';
        switch ($purse[0]) {
            case 'R': $provider = '31271'; break;
            case 'E': $provider = '32111'; break;
            case 'Z': $provider = '32110'; break;
        }
        return json_decode($this->request('POST', 'sinap/api/v2/terms/'.$provider.'/payments', json_encode($query)), true);
    }

    public function transferToRuVISA($purse, $currency, $sum, $accountID = null) {
        if($accountID==null) $accountID = $currency;
        $id = rand(1111, 9999) * time();
        $query['id'] = "$id";
        $query['sum']['amount'] = $sum;
        $query['sum']['currency'] = "$currency";
        $query['paymentMethod']['type'] = 'Account';
        $query['paymentMethod']['accountId'] = "$accountID";
        $query['fields']['account'] = $purse;
        return json_decode($this->request('POST', 'sinap/api/v2/terms/1963/payments', json_encode($query)), true);
    }

    public function transferToRuMasterCard($purse, $currency, $sum, $accountID = null) {
        if($accountID==null) $accountID = $currency;
        $id = rand(1111, 9999) * time();
        $query['id'] = "$id";
        $query['sum']['amount'] = $sum;
        $query['sum']['currency'] = "$currency";
        $query['paymentMethod']['type'] = 'Account';
        $query['paymentMethod']['accountId'] = "$accountID";
        $query['fields']['account'] = $purse;
        return json_decode($this->request('POST', 'sinap/api/v2/terms/21013/payments', json_encode($query)), true);
    }

    public function getHistory($rows = 50, $operation = 'ALL', $start = null, $end = null)
    {
        $start = date('Y-m-d\TH:i:sP', $start);
        $end = date('Y-m-d\TH:i:sP', $end);
        $response = json_decode($this->request('GET', 'payment-history/v1/persons/'.$this->purse.'/payments', ['rows' => $rows, 'startDate' => $start, 'endDate' => $end, 'operation' => $operation]), true);
        return $response;
        if(isset($response['data'])) return $response['data'];
        else return false;
    }
}
