# qiwi-api
PHP class for qiwi api

# Examples
$handler = new QiwiAPI('380972095301','YourApiKey'); <BR>
$handler->transferMoney('+380972095301', QiwiAPI::CURRENCY_RUB, 1, 'thank you');

$handler->getHistory(50,QiwiAPI::OPERATION_ALL,1504015224,time());
