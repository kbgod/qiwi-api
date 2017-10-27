<?php 
namespace Qiwi;

class QiwiAPI {

	private $token;
	private $url = 'https://edge.qiwi.com/';

	const CURRENCY_RUB = 643;


	const PROVIDER_QIWI = 99;
	const PROVIDER_VISA = 1963;

	public function __construct($token)
	{
		$this->token = $token;
	}

	public function request($type = 'GET', $method, $data = null, $headers)
	{
		$ch = curl_init();
		$headers = ["Authorization: Bearer {$this->token}", 'Content-Type: application/json', 'Accept: application/json'];
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		if($type=='POST')
		{
			curl_setopt($ch, CURLOPT_URL, $this->url.$method);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		else
		{
			curl_setopt($ch, CURLOPT_URL, $this->url.$method.(is_array($data)?'?'.http_build_query($data):(($data!==null)?'?'.$data:'')));
		}
        	$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}

	public function transferMoney($purse, $currency, $sum, $comment = null)	{
		$id = rand(1111, 9999) * time();
		$query['id'] = "$id";
		$query['sum']['amount'] = $sum;
		$query['sum']['currency'] = "$currency";
		$query['paymentMethod']['type'] = 'Account';
		$query['paymentMethod']['accountId'] = "$currency";
		$query['fields']['account'] = $purse;
		if($comment!=null) $query['comment'] = $comment;
		print_r(json_encode($query));
		return $this->request('POST','sinap/api/v2/terms/99/payments', json_encode($query));
	}

	public function getHistory($purse, $start, $end, $rows = 50) {
		$start = urlencode(date("Y-m-d",$start).'T00:00:00Z');
		$end = urlencode(date("Y-m-d",$end).'T23:59:59Z');
		$query = http_build_query(['rows' => $rows]);
		return json_decode($this->request('GET', 'payment-history/v1/persons/'.$purse.'/payments', $query), true)['data'];
	}


}

?>
