# qiwi-api
PHP class for qiwi api

# Examples
$wallet['number'] = '380972095301';<BR>
$wallet['token'] = 'YourApiKey';<BR>
$wallet['proxy_server'] = 'IP:PORT';<BR>
$wallet['proxy_user'] = 'USERNAME:PASS';<BR>
$wallet['proxy_type'] = 'socks5/socks4/http';<BR>
$handler = new QiwiAPI($wallet); <BR>
$handler->transferToQiwiWallet('+380972095301', QiwiAPI::CURRENCY_RUB, 1, 'thank you'); <BR>
$handler->getHistory(50,QiwiAPI::OPERATION_ALL,1504015224,time());
