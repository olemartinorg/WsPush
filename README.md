# WsPush
An extremely simple (and limited) WebSocket client (in PHP) that can only push data to a WebSocket server. It works with RFC 6455, which (at the time of writing) is the most recent WebSocket protocol version. It works well with IE10+ and other modern browsers (see https://en.wikipedia.org/wiki/WebSocket for more information). 

Example usage:
```php
require_once('WsPush.php');
try {
    $ws = new WsPush('ws://example.com');
    $ws->send('This is a test message');
    $ws->close();
} catch (Exception $e) {
    echo "Failed to send WebSocket message: ".$e->getMessage();
}
```
