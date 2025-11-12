<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Librería para manejo de errores HTTP, incluyendo error 413 (Request Entity Too Large)
 * Implementa compresión de datos, división en chunks y reintentos con backoff exponencial
 */
class HttpErrorHandler {

    private $CI;
    private $max_retries = 3;
    private $backoff_delays = [1, 2, 4]; // segundos
    private $chunk_size = 1024 * 1024; // 1MB por defecto
    private $compression_enabled = true;

    public function __construct()
    {
        // Solo cargar CI si está disponible (para compatibilidad con testing)
        if (function_exists('get_instance')) {
            $this->CI =& get_instance();
            if (isset($this->CI->session)) {
                $this->CI->load->library('session');
            }
        }
    }

    /**
     * Configura los parámetros de manejo de errores
     */
    public function configure($config = [])
    {
        if (isset($config['max_retries'])) {
            $this->max_retries = $config['max_retries'];
        }
        if (isset($config['backoff_delays'])) {
            $this->backoff_delays = $config['backoff_delays'];
        }
        if (isset($config['chunk_size'])) {
            $this->chunk_size = $config['chunk_size'];
        }
        if (isset($config['compression_enabled'])) {
            $this->compression_enabled = $config['compression_enabled'];
        }
    }

    /**
     * Ejecuta una solicitud HTTP con manejo de errores 413
     */
    public function execute_request($url, $data, $method = 'POST', $headers = [])
    {
        $attempt = 0;
        $last_error = null;

        while ($attempt < $this->max_retries) {
            try {
                $result = $this->_make_request($url, $data, $method, $headers, $attempt);

                // Si la solicitud fue exitosa, retornar el resultado
                if ($result['success']) {
                    return $result;
                }

                // Si es error 413, intentar mitigación
                if ($result['http_code'] == 413) {
                    $this->_log_error("Error 413 detectado en intento " . ($attempt + 1) . ": " . $result['error']);

                    // Intentar con datos comprimidos
                    if ($this->compression_enabled && $attempt == 0) {
                        $compressed_data = $this->_compress_data($data);
                        if ($compressed_data !== false) {
                            $this->_log_error("Reintentando con datos comprimidos");
                            $headers_compressed = array_merge($headers, [
                                'Content-Encoding: gzip',
                                'Accept-Encoding: gzip'
                            ]);
                            $result = $this->_make_request($url, $compressed_data, $method, $headers_compressed, $attempt);
                            if ($result['success']) {
                                return $result;
                            }
                        }
                    }

                    // Intentar dividir en chunks si es aplicable
                    if ($this->_can_chunk_data($data) && $attempt == 1) {
                        $chunks = $this->_split_into_chunks($data);
                        if (count($chunks) > 1) {
                            $this->_log_error("Reintentando dividiendo en " . count($chunks) . " chunks");
                            return $this->_execute_chunked_request($url, $chunks, $method, $headers);
                        }
                    }
                }

                $last_error = $result['error'];

            } catch (Exception $e) {
                $last_error = $e->getMessage();
                $this->_log_error("Excepción en intento " . ($attempt + 1) . ": " . $last_error);
            }

            $attempt++;

            // Aplicar backoff exponencial si no es el último intento
            if ($attempt < $this->max_retries) {
                $delay = isset($this->backoff_delays[$attempt - 1]) ? $this->backoff_delays[$attempt - 1] : 1;
                $this->_log_error("Esperando {$delay} segundos antes del siguiente intento");
                sleep($delay);
            }
        }

        // Si todos los intentos fallaron, notificar al usuario
        $this->_notify_user_failure($url, $last_error);
        return [
            'success' => false,
            'error' => "Todos los intentos fallaron. Último error: " . $last_error,
            'http_code' => 413
        ];
    }

    /**
     * Realiza la solicitud HTTP usando cURL
     */
    private function _make_request($url, $data, $method, $headers, $attempt)
    {
        $ch = curl_init();

        // Configurar opciones básicas
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Configurar método y datos
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (is_array($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        } elseif ($method === 'GET') {
            if (!empty($data) && is_array($data)) {
                $url .= '?' . http_build_query($data);
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }

        // Ejecutar solicitud
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'error' => "Error cURL: " . $error,
                'http_code' => 0
            ];
        }

        return [
            'success' => ($http_code >= 200 && $http_code < 300),
            'response' => $response,
            'http_code' => $http_code,
            'error' => ($http_code != 200) ? "HTTP {$http_code}: " . $response : null
        ];
    }

    /**
     * Comprime datos usando gzip
     */
    private function _compress_data($data)
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }

        return gzencode($data, 9); // Máxima compresión
    }

    /**
     * Verifica si los datos pueden ser divididos en chunks
     */
    private function _can_chunk_data($data)
    {
        // Solo dividir arrays o strings grandes
        if (is_array($data)) {
            return count($data) > 10; // Más de 10 elementos
        }
        if (is_string($data)) {
            return strlen($data) > $this->chunk_size;
        }
        return false;
    }

    /**
     * Divide los datos en chunks más pequeños
     */
    private function _split_into_chunks($data)
    {
        $chunks = [];

        if (is_array($data)) {
            // Dividir array en chunks más pequeños
            $chunk_size = max(1, ceil(count($data) / 3)); // Dividir en máximo 3 chunks
            $chunks = array_chunk($data, $chunk_size);
        } elseif (is_string($data)) {
            // Dividir string en chunks
            $data_length = strlen($data);
            $num_chunks = ceil($data_length / $this->chunk_size);

            for ($i = 0; $i < $num_chunks; $i++) {
                $start = $i * $this->chunk_size;
                $chunks[] = substr($data, $start, $this->chunk_size);
            }
        }

        return $chunks;
    }

    /**
     * Ejecuta solicitudes en chunks
     */
    private function _execute_chunked_request($url, $chunks, $method, $headers)
    {
        $results = [];

        foreach ($chunks as $index => $chunk) {
            $chunk_headers = array_merge($headers, [
                'X-Chunk-Index: ' . $index,
                'X-Chunk-Total: ' . count($chunks)
            ]);

            $result = $this->_make_request($url, $chunk, $method, $chunk_headers, 0);
            $results[] = $result;

            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => "Error en chunk " . ($index + 1) . ": " . $result['error'],
                    'http_code' => $result['http_code']
                ];
            }
        }

        return [
            'success' => true,
            'response' => $results,
            'message' => 'Solicitud procesada en ' . count($chunks) . ' chunks'
        ];
    }

    /**
     * Registra errores en el log
     */
    private function _log_error($message)
    {
        log_message('error', '[HttpErrorHandler] ' . $message);
    }

    /**
     * Notifica al usuario sobre el fallo final
     */
    private function _notify_user_failure($url, $error)
    {
        $this->_log_error("Notificando fallo al usuario - URL: {$url}, Error: {$error}");

        // Aquí se podría implementar notificación por email, Slack, etc.
        // Por ahora, solo registramos en el log
        log_message('error', '[HttpErrorHandler] Notificación de fallo enviada al usuario');
    }

    /**
     * Valida el tamaño de los datos antes de enviar
     */
    public function validate_data_size($data, $max_size_mb = 10)
    {
        $max_size_bytes = $max_size_mb * 1024 * 1024;

        if (is_array($data)) {
            $data_size = strlen(json_encode($data));
        } elseif (is_string($data)) {
            $data_size = strlen($data);
        } else {
            $data_size = 0;
        }

        if ($data_size > $max_size_bytes) {
            return [
                'valid' => false,
                'error' => "Los datos exceden el límite de {$max_size_mb}MB (tamaño actual: " . round($data_size / 1024 / 1024, 2) . "MB)"
            ];
        }

        return ['valid' => true];
    }
}

/* End of file HttpErrorHandler.php */
/* Location: ./application/libraries/HttpErrorHandler.php */