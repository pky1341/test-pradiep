<?php
class ApiResponseHandler
{
    /** @var \Psr\Log\LoggerInterface */
    private $logger;
    
    /** @var array Default values for missing fields */
    private $defaults = [
        'user' => [
            'id' => 0,
            'name' => 'Unknown User',
            'email' => null,
            'status' => 'inactive'
        ],
        'transaction' => [
            'id' => null,
            'amount' => 0,
            'status' => 'unknown',
            'timestamp' => null
        ]
    ];
    
    /**
     * Constructor
     * 
     * @param \Psr\Log\LoggerInterface $logger PSR-3 compatible logger
     */
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * Process API Response
     * 
     * @param string $response Raw API response
     * @param string $responseType Expected response type (user|transaction)
     * @return array Processed and validated response data
     * @throws \InvalidArgumentException If response type is invalid
     */
    public function processResponse($response, $responseType)
    {
        // Validate response type
        if (!isset($this->defaults[$responseType])) {
            throw new \InvalidArgumentException("Invalid response type: {$responseType}");
        }
        
        try {
            $data = $this->parseJson($response);
            
            $processedData = $this->validateAndSanitize($data, $responseType);
            
            return $processedData;
            
        } catch (\Exception $e) {
            $this->logger->error('API response processing failed', [
                'error' => $e->getMessage(),
                'response_type' => $responseType,
                'response_preview' => substr($response, 0, 500) . (strlen($response) > 500 ? '...' : ''),
            ]);
            
            return $this->defaults[$responseType];
        }
    }
    
    /**
     * Parse JSON safely
     * 
     * @param string $jsonString Raw JSON string
     * @return array Decoded JSON data
     * @throws \RuntimeException If JSON cannot be parsed
     */
    private function parseJson($jsonString)
    {
        if (empty($jsonString)) {
            throw new \RuntimeException("Empty API response received");
        }
        
        $data = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("JSON parsing error: " . json_last_error_msg());
        }
        
        if (!is_array($data)) {
            throw new \RuntimeException("Decoded JSON is not an array");
        }
        
        return $data;
    }
    
    /**
     * Validate and sanitize API response data
     * 
     * @param array $data The parsed JSON data
     * @param string $responseType Type of response (user|transaction)
     * @return array Validated and completed data
     */
    private function validateAndSanitize($data, $responseType)
    {
        $defaults = $this->defaults[$responseType];
        $result = [];
        
        // Loop through expected fields and apply defaults if missing
        foreach ($defaults as $field => $defaultValue) {
            if (isset($data[$field])) {
                $result[$field] = $this->sanitizeField($data[$field], $defaultValue);
            } else {
                $this->logger->warning("Missing field in {$responseType} response", [
                    'field' => $field,
                    'using_default' => $defaultValue
                ]);
                $result[$field] = $defaultValue;
            }
        }
        
        $unexpectedFields = array_diff(array_keys($data), array_keys($defaults));
        if (!empty($unexpectedFields)) {
            $this->logger->info("Unexpected fields in {$responseType} response", [
                'fields' => $unexpectedFields
            ]);
        }
        
        return $result;
    }
    
    /**
     * Sanitize individual field based on expected type
     * 
     * @param mixed $value The value to sanitize
     * @param mixed $defaultValue The default value (used for type inference)
     * @return mixed Sanitized value
     */
    private function sanitizeField($value, $defaultValue)
    {
        if ($value === null) {
            return $defaultValue;
        }
        
        if (is_int($defaultValue) && !is_int($value)) {
            if (is_numeric($value)) {
                return (int)$value;
            }
            return $defaultValue;
        } 
        
        if (is_float($defaultValue) && !is_float($value)) {
            if (is_numeric($value)) {
                return (float)$value;
            }
            return $defaultValue;
        }
        
        if (is_string($defaultValue) && !is_string($value)) {
            return (string)$value;
        }
        
        return $value;
    }
}

/**
 * Usage Example
 */
// Using Monolog for PSR-3 compatible logging
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;

function processApiResponse($apiResponseString, $responseType)
{
    $logger = new Logger('api_integration');
    
    // Add processors for additional context
    $logger->pushProcessor(new WebProcessor());
    $logger->pushProcessor(new IntrospectionProcessor());
    
    // Daily rotating log file with custom format
    $formatter = new LineFormatter(
        "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
        null, 
        true,
        true 
    );
    
    // Normal logs
    $logHandler = new RotatingFileHandler(
        __DIR__ . '/logs/api_integration.log',
        30,
        Logger::INFO
    );
    $logHandler->setFormatter($formatter);
    $logger->pushHandler($logHandler);
    
    $errorLogHandler = new StreamHandler(
        __DIR__ . '/logs/api_errors.log',
        Logger::ERROR
    );
    $errorLogHandler->setFormatter($formatter);
    $logger->pushHandler($errorLogHandler);
    
    $handler = new ApiResponseHandler($logger);
    
    try {
        $processedData = $handler->processResponse($apiResponseString, $responseType);
        
        // Log successful processing
        $logger->info("Successfully processed {$responseType} API response", [
            'data_preview' => json_encode(array_intersect_key(
                $processedData,
                array_flip(['id', 'status']) // Log only non-sensitive fields
            ))
        ]);
        
        return $processedData;
        
    } catch (\InvalidArgumentException $e) {
        $logger->error("Invalid input parameter", [
            'error' => $e->getMessage(),
            'response_type' => $responseType
        ]);
        
        return null;
    } catch (\Exception $e) {
        $logger->critical("Unhandled exception in API response processing", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'response_type' => $responseType
        ]);
        
        return null;
    }
}

// Example usage
$apiResponse = '{"id": 123, "name": "John Doe", "status": "active"}';
$processedData = processApiResponse($apiResponse, 'user');

// Handle malformed JSON
$malformedResponse = '{id: 123, name: "Missing quotes", status: active}';
$processedData = processApiResponse($malformedResponse, 'user');

// Handle missing fields
$incompleteResponse = '{"id": 456}';
$processedData = processApiResponse($incompleteResponse, 'user');