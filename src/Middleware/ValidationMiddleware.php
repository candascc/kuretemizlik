<?php
/**
 * Validation Middleware
 * Automatically validates incoming requests based on defined rules
 */

class ValidationMiddleware implements MiddlewareInterface
{
    private $rules = [];
    private $customMessages = [];
    
    public function __construct(array $rules, array $customMessages = [])
    {
        $this->rules = $rules;
        $this->customMessages = $customMessages;
    }
    
    public function __invoke(callable $next): callable
    {
        return function() use ($next) {
            // Get request data based on method
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $data = $method === 'GET' ? $_GET : ($method === 'POST' ? $_POST : []);
            
            // Merge with JSON payload if present
            if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
                $raw = file_get_contents('php://input');
                $json = json_decode($raw, true);
                if (is_array($json)) {
                    $data = array_merge($data, $json);
                }
            }
            
            // Create validator
            $validator = new Validator($data);
            
            // Apply validation rules
            foreach ($this->rules as $field => $rule) {
                $rules = is_array($rule) ? $rule : explode('|', $rule);
                
                foreach ($rules as $singleRule) {
                    $this->applyRule($validator, $field, $singleRule);
                }
            }
            
            // Check if validation fails
            if ($validator->fails()) {
                $firstError = $validator->firstError();
                $allErrors = $validator->errors();
                
                // Log validation failure
                if (class_exists('Logger')) {
                    Logger::warning('Validation failed', [
                        'field' => $validator->firstErrorField(),
                        'errors' => $allErrors,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                }
                
                // Determine response type
                $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? 'text/html';
                $isApiRequest = strpos($acceptHeader, 'application/json') !== false || 
                               strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false;
                
                if ($isApiRequest) {
                    // API response
                    header('Content-Type: application/json');
                    http_response_code(422);
                    echo json_encode([
                        'success' => false,
                        'error' => $firstError,
                        'errors' => $allErrors
                    ]);
                    exit;
                } else {
                    // Web response
                    Utils::flash('error', $firstError);
                    
                    // Redirect back if referer exists, otherwise to home
                    $referer = $_SERVER['HTTP_REFERER'] ?? base_url();
                    redirect($referer);
                }
            }
            
            // Validation passed, continue
            return $next();
        };
    }
    
    /**
     * Apply a single validation rule
     */
    private function applyRule(Validator $validator, string $field, string $rule): void
    {
        $parts = explode(':', $rule);
        $ruleName = $parts[0];
        $value = $parts[1] ?? null;
        
        $customMessage = $this->customMessages[$field . '.' . $ruleName] ?? null;
        
        switch ($ruleName) {
            case 'required':
                $validator->required($field, $customMessage);
                break;
                
            case 'nullable':
                // Field is optional
                break;
                
            case 'email':
                $validator->email($field, $customMessage);
                break;
                
            case 'numeric':
                $validator->numeric($field, $customMessage);
                break;
                
            case 'integer':
                $validator->integer($field, $customMessage);
                break;
                
            case 'string':
                $validator->string($field, $value ? (int)$value : null, $customMessage);
                break;
                
            case 'min':
                $validator->min($field, (int)$value, $customMessage);
                break;
                
            case 'max':
                $validator->max($field, (int)$value, $customMessage);
                break;
                
            case 'date':
                $validator->date($field, $customMessage);
                break;
                
            case 'datetime':
                $validator->datetime($field, $customMessage);
                break;
                
            case 'url':
                $validator->url($field, $customMessage);
                break;
                
            case 'phone':
                $validator->regex($field, '/^[\d\s+\-()]+$/', $customMessage ?: "$field geçerli bir telefon numarası olmalıdır");
                break;
                
            case 'alpha':
                $validator->alpha($field, $value === 'spaces', $customMessage);
                break;
                
            case 'alphanum':
                $validator->alphaNum($field, $value === 'spaces', $customMessage);
                break;
                
            case 'in':
                $values = explode(',', $value ?? '');
                $validator->in($field, $values, $customMessage);
                break;
                
            case 'not_in':
                $values = explode(',', $value ?? '');
                $validator->notIn($field, $values, $customMessage);
                break;
                
            case 'confirmed':
                $confirmField = $field . '_confirmation';
                $validator->confirmed($field, $confirmField, $customMessage);
                break;
                
            case 'different':
                $validator->different($field, $value, $customMessage);
                break;
                
            case 'same':
                $validator->same($field, $value, $customMessage);
                break;
                
            case 'unique':
                // Format: unique:table,column,except_id
                $parts = explode(',', $value ?? '');
                $table = $parts[0] ?? '';
                $column = $parts[1] ?? null;
                $exceptId = $parts[2] ?? null;
                $validator->unique($field, $table, $column, $exceptId, $customMessage);
                break;
                
            case 'exists':
                // Format: exists:table,column
                $parts = explode(',', $value ?? '');
                $table = $parts[0] ?? '';
                $column = $parts[1] ?? $field;
                $validator->exists($field, $table, $column, $customMessage);
                break;
                
            case 'positive':
                $validator->positive($field, $customMessage);
                break;
                
            case 'future_date':
                $validator->futureDate($field, $customMessage);
                break;
                
            case 'past_date':
                $validator->pastDate($field, $customMessage);
                break;
        }
    }
    
    /**
     * Create validation middleware with rules
     */
    public static function create(array $rules, array $customMessages = []): self
    {
        return new self($rules, $customMessages);
    }
}

