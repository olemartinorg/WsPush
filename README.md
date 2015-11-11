# WsPush
An extremely simple (and limited) WebSocket client (in PHP) that can only push data to a WebSocket server

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
