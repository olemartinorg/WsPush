<?php
    /**
     * Class WsPush
     *
     * This is an extremely basic WebSocket client, based on RFC 6455. It supports only a subset of the full WebSocket
     * protocol, and will only allow you to send messages over the websocket (not receive messages).
     */
    class WsPush {

        /**
         * @var resource
         */
        private $socket;


        /**
         * Constructor
         *
         * @param string $wsUrl Web socket URL (ex. ws://host:port/path)
         * @throws ErrorException
         * @throws RuntimeException
         */
        public function __construct($wsUrl) {
            $host = parse_url($wsUrl, PHP_URL_HOST);
            $port = parse_url($wsUrl, PHP_URL_PORT);
            $path = parse_url($wsUrl, PHP_URL_PATH);

            $httpHeaders = sprintf(
                "GET %s HTTP/1.1"."\r\n".
                "Host: %s"."\r\n".
                "User-Agent: %s"."\r\n".
                "Upgrade: websocket"."\r\n".
                "Connection: Upgrade"."\r\n".
                "Sec-WebSocket-Key: %s"."\r\n".
                "Sec-WebSocket-Version: 13"."\r\n\r\n",

                $path,
                $host.":".$port,
                'WsPush',
                base64_encode(md5(mt_rand(0, PHP_INT_MAX).''.microtime(true)))
            );

            set_error_handler(function($errno, $errstr, $errfile, $errline) {
                throw new \ErrorException($errstr, $errno, 1, $errfile, $errline);
            }, E_ALL);

            $this->socket = fsockopen('tcp://'.$host, $port, $errorNum, $errorMsg, 2);

            if ($errorNum) {
                throw new RuntimeException("Failed to connect to WebSocket server at $wsUrl ($errorMsg)");
            }

            fwrite($this->socket, $httpHeaders);

            // Read return header/handshake, but ignore it
            fread($this->socket, 2000);

            restore_error_handler();
        }


        /**
         * Send a message over the websocket
         *
         * This wraps the message in a WebSocket frame, masks it, and sends it over the socket.
         *
         * @param string $message The message to send
         * @return void
         */
        public function send($message) {
            if (!$this->socket) {
                throw new RuntimeException("Tried to send message over a closed WebSocket connection");
            }

            $length = strlen($message);

            // Fin: true
            // Reserved: 0x00
            // Op code: Text (1)
            $frame = "\x81";

            if ($length < 126) {
                // The length fits in 7 bits, and we can add the masking bit in the leftmost 8th bit
                $frame .= pack('c', $length ^ 128);
            } elseif ($length <= 65535) {
                // Mask: 1
                // Length: 126 (allows an extended payload length)
                // Extended payload length: 16bit unsigned short
                $frame .= "\xfe".pack('n', $length);
            } else {
                // Mask: 1
                // Length: 127 (allows an extended payload length)
                // Extended payload length: 64bit unsigned long long
                if(PHP_INT_MAX > 2147483647) {
                    $frame .= "\xff".pack('NN', $length >> 32, $length);
                } else {
                    $frame .= "\xff".pack('NN', 0, $length);
                }
            }

            // Add mask to frame (should be a strong random number)
            // 32bit unsigned int (4 bytes)
            $mask = pack("N", mt_rand(0, 4294967295));
            $frame .= $mask;

            // Mask the payload and add it to the frame
            for ($i=0; $i<$length; $i++) {
                $frame .= $message[$i] ^ $mask[$i % 4];
            }

            // All done, let's send it!
            fwrite($this->socket, $frame);
        }

        /**
         * Close the WebSocket connection
         *
         * @return void
         */
        public function close() {
            if(!$this->socket) {
                return;
            }

            // Fin: true
            // Reserved: 0x00
            // Op code: Connection close (8)
            $frame = "\x88";

            // Mask: 1
            // Length: 0
            $frame .= "\x80";

            // Add a mask (not used for anything, but needed)
            $frame .= pack("N", mt_rand(0, 4294967295));

            fwrite($this->socket, $frame);
            fclose($this->socket);
            $this->socket = null;
        }

    }
