# PHP Site mirror
Make mirror of any site on your domain

### How to use
>add .htaccess

```php
include("Proxy.php");

$proxy = new Proxy();

$proxy->render('http://stackoverflow.com/' . $_GET['url']);
```

### Modify request or response 

```php
$proxy->setRequestHook(function(&$header, &$body) {
    // Modify some header or body before send request
    array_push($header, ['Origin' => $_SERVER["HTTP_HOST"]]);
    array_push($header, ['X-Requested-With' => 'XMLHttpRequest']);
});

$proxy->setResponseHook(function(&$header, &$body) {
    // Modify some header or body before render
});
```
