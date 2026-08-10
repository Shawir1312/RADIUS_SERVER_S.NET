<?php
/**
 * S.NET RADIUS Hotspot Management
 * RADIUS CoA / Disconnect-Message Sender (RFC 3576)
 */

class RadiusCoA {

    private string $nas_ip;
    private string $secret;
    private int    $port;
    private int    $timeout;

    public function __construct(string $nas_ip, string $secret, int $port = 3799, int $timeout = 5) {
        $this->nas_ip  = $nas_ip;
        $this->secret  = $secret;
        $this->port    = $port;
        $this->timeout = $timeout;
    }

    /**
     * Send a Disconnect-Request (code 40) to the NAS.
     * Returns true on success (Disconnect-ACK received), false on failure.
     */
    public function disconnect(string $username, ?string $session_id = null): bool {
        $attrs = [
            ['type' => 1, 'value' => $username],  // User-Name
        ];
        if ($session_id) {
            $attrs[] = ['type' => 44, 'value' => $session_id];  // Acct-Session-Id
        }
        return $this->send_packet(40, $attrs);  // Code 40 = Disconnect-Request
    }

    /**
     * Send a CoA-Request (code 43) to the NAS.
     */
    public function change_of_auth(string $username, array $extra_attrs = []): bool {
        $attrs = [
            ['type' => 1, 'value' => $username],
        ];
        foreach ($extra_attrs as $attr) {
            $attrs[] = $attr;
        }
        return $this->send_packet(43, $attrs);  // Code 43 = CoA-Request
    }

    private function send_packet(int $code, array $attributes): bool {
        $identifier = random_int(0, 255);
        $attr_data  = '';

        foreach ($attributes as $attr) {
            $val_encoded = $this->encode_attr($attr['type'], $attr['value']);
            $attr_data  .= $val_encoded;
        }

        // Build packet without authenticator first
        $length        = 20 + strlen($attr_data);
        $authenticator = str_repeat("\x00", 16);
        $packet        = pack('CCn', $code, $identifier, $length)
                       . $authenticator
                       . $attr_data;

        // Replace authenticator with correct MD5 hash
        $authenticator = md5($packet . $this->secret, true);
        $packet        = pack('CCn', $code, $identifier, $length)
                       . $authenticator
                       . $attr_data;

        // Send via UDP
        $sock = @fsockopen("udp://{$this->nas_ip}", $this->port, $errno, $errstr, $this->timeout);
        if (!$sock) return false;

        stream_set_timeout($sock, $this->timeout);
        fwrite($sock, $packet);

        $response = fread($sock, 4096);
        fclose($sock);

        if (!$response || strlen($response) < 20) return false;

        $resp_code = ord($response[0]);
        // Code 41 = Disconnect-ACK, Code 44 = CoA-ACK
        return in_array($resp_code, [41, 44]);
    }

    private function encode_attr(int $type, string $value): string {
        $len    = 2 + strlen($value);
        return pack('CC', $type, $len) . $value;
    }
}

/**
 * Try to disconnect a user via CoA first, then fallback to RouterOS API.
 * Returns ['success'=>bool, 'method'=>'coa'|'api'|'failed', 'error'=>string]
 */
function disconnect_user_from_router(array $router, string $username, ?string $session_id = null): array {
    // Attempt 1: RADIUS CoA Disconnect-Request
    try {
        $coa = new RadiusCoA($router['ip_address'], $router['radius_secret'], COA_PORT, COA_TIMEOUT);
        if ($coa->disconnect($username, $session_id)) {
            return ['success' => true, 'method' => 'coa', 'error' => ''];
        }
    } catch (Throwable $e) {
        // continue to API fallback
    }

    // Attempt 2: RouterOS API fallback
    try {
        require_once LIB_PATH . '/routeros_api.class.php';
        $api = new RouterosAPI();
        $api->debug = false;
        if ($api->connect($router['ip_address'], $router['api_user'], $router['api_password'], (int)$router['api_port'])) {
            // Find active hotspot user
            $active = $api->comm('/ip/hotspot/active/print', ['?user' => $username]);
            if (!empty($active)) {
                $id = $active[0]['.id'] ?? '';
                if ($id) {
                    $api->comm('/ip/hotspot/active/remove', ['.id' => $id]);
                    return ['success' => true, 'method' => 'api', 'error' => ''];
                }
            }
        }
    } catch (Throwable $e) {
        return ['success' => false, 'method' => 'failed', 'error' => $e->getMessage()];
    }

    return ['success' => false, 'method' => 'failed', 'error' => 'Both CoA and API failed'];
}
