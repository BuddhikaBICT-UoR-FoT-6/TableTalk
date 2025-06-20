<?php
namespace Core;

class JWT {
    private static $secret = 'super_secret_tabletalk_key_2025';

    /**
     * Encodes a payload into a JWT (JSON Web Token) string using HS256.
     *
     * @param array $payload The associative array containing claims.
     * @return string The signed JWT string.
     */
    public static function encode($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secret, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Decodes and validates a JWT string.
     *
     * @param string $jwt The JWT token to decode.
     * @return array|false The decoded payload as an associative array, or false if invalid/expired.
     */
    public static function decode($jwt) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return false;
        }

        $header = $parts[0];
        $payload = $parts[1];
        $signatureProvided = $parts[2];

        $signature = hash_hmac('sha256', $header . "." . $payload, self::$secret, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        if ($base64UrlSignature === $signatureProvided) {
            $payloadDecoded = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
            // Check expiration if 'exp' is present
            if (isset($payloadDecoded['exp']) && $payloadDecoded['exp'] < time()) {
                return false;
            }
            return $payloadDecoded;
        }

        return false;
    }

    /**
     * Retrieves the Bearer token from the HTTP Authorization header.
     *
     * @return string|null The extracted token, or null if not found.
     */
    public static function getBearerToken() {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { // Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * Validates the request's Bearer token and checks if the user's role is authorized.
     * Terminate request with 401/403 if validation fails.
     *
     * @param array $allowedRoles Array of allowed roles (e.g. ['admin', 'customer']).
     * @return array The decoded token payload.
     */
    public static function requireRole($allowedRoles) {
        $token = self::getBearerToken();
        if (!$token) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing Authorization header']);
            exit();
        }

        $payload = self::decode($token);
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token']);
            exit();
        }

        if (!in_array($payload['role'], $allowedRoles)) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }

        return $payload;
    }
}
