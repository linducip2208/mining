<?php

namespace App\Services\Weighbridge;

/**
 * TCP/IP driver contract. A poller daemon opens the socket described
 * in device config (host/port) and ingests frames via SerialProvider::parseFrame.
 * This class exposes the latest ingested reading, same as serial.
 */
class TcpProvider extends SerialProvider
{
    public function code(): string
    {
        return 'TCP';
    }
}
