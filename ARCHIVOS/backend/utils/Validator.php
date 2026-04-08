<?php
// SerTecApp - Input Validator

class Validator {
    private $errors = [];
    private $data = [];
    
    public function __construct($data = []) {
        $this->data = $data;
        $this->errors = [];
    }
    
    /**
     * Validate single field
     */
    public function field($field, $rules) {
        $value = $this->data[$field] ?? null;
        $ruleArray = is_array($rules) ? $rules : explode('|', $rules);
        
        foreach ($ruleArray as $rule) {
            $this->applyRule($field, $value, $rule);
        }
        
        return $this;
    }
    
    /**
     * Validate multiple fields with rules
     * 
     * Example:
     * $validator = new Validator($data);
     * $validator->validate([
     *     'email' => 'required|email',
     *     'password' => 'required|min:6',
     *     'edad' => 'integer|min:18|max:100'
     * ]);
     */
    public function validate($rules) {
        foreach ($rules as $field => $fieldRules) {
            $this->field($field, $fieldRules);
        }
        
        return $this;
    }
    
    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     */
    public function fails() {
        return !$this->passes();
    }
    
    /**
     * Get all errors
     */
    public function errors() {
        return $this->errors;
    }
    
    /**
     * Get first error message
     */
    public function firstError() {
        if (empty($this->errors)) return null;
        return reset($this->errors);
    }
    
    /**
     * Apply single validation rule
     */
    private function applyRule($field, $value, $rule) {
        $parts = explode(':', $rule);
        $ruleName = $parts[0];
        $ruleParam = $parts[1] ?? null;
        
        switch ($ruleName) {
            case 'required':
                if ($value === null || $value === '') {
                    $this->addError($field, "El campo {$field} es requerido");
                }
                break;
                
            case 'email':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "El campo {$field} debe ser un email válido");
                }
                break;
                
            case 'integer':
            case 'int':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, "El campo {$field} debe ser un número entero");
                }
                break;
                
            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    $this->addError($field, "El campo {$field} debe ser numérico");
                }
                break;
                
            case 'min':
                if ($value !== null && $value !== '') {
                    if (is_string($value) && mb_strlen($value) < $ruleParam) {
                        $this->addError($field, "El campo {$field} debe tener al menos {$ruleParam} caracteres");
                    } elseif (is_numeric($value) && $value < $ruleParam) {
                        $this->addError($field, "El campo {$field} debe ser al menos {$ruleParam}");
                    }
                }
                break;
                
            case 'max':
                if ($value !== null && $value !== '') {
                    if (is_string($value) && mb_strlen($value) > $ruleParam) {
                        $this->addError($field, "El campo {$field} no puede exceder {$ruleParam} caracteres");
                    } elseif (is_numeric($value) && $value > $ruleParam) {
                        $this->addError($field, "El campo {$field} no puede ser mayor a {$ruleParam}");
                    }
                }
                break;
                
            case 'in':
                $allowed = explode(',', $ruleParam);
                if ($value !== null && $value !== '' && !in_array($value, $allowed)) {
                    $this->addError($field, "El campo {$field} debe ser uno de: " . implode(', ', $allowed));
                }
                break;
                
            case 'url':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, "El campo {$field} debe ser una URL válida");
                }
                break;
                
            case 'date':
                if ($value !== null && $value !== '') {
                    $d = DateTime::createFromFormat('Y-m-d', $value);
                    if (!$d || $d->format('Y-m-d') !== $value) {
                        $this->addError($field, "El campo {$field} debe ser una fecha válida (YYYY-MM-DD)");
                    }
                }
                break;
                
            case 'datetime':
                if ($value !== null && $value !== '') {
                    $d = DateTime::createFromFormat('Y-m-d H:i:s', $value);
                    if (!$d || $d->format('Y-m-d H:i:s') !== $value) {
                        $this->addError($field, "El campo {$field} debe ser fecha-hora válida (YYYY-MM-DD HH:MM:SS)");
                    }
                }
                break;
                
            case 'phone':
                if ($value !== null && $value !== '') {
                    // Formato flexible para teléfonos argentinos
                    $pattern = '/^[\d\s\-\+\(\)]{8,20}$/';
                    if (!preg_match($pattern, $value)) {
                        $this->addError($field, "El campo {$field} debe ser un teléfono válido");
                    }
                }
                break;
                
            case 'cuit':
                if ($value !== null && $value !== '') {
                    // CUIT argentino: XX-XXXXXXXX-X
                    $cuit = preg_replace('/[^0-9]/', '', $value);
                    if (strlen($cuit) !== 11) {
                        $this->addError($field, "El campo {$field} debe ser un CUIT válido (11 dígitos)");
                    }
                }
                break;
                
            case 'alpha':
                if ($value !== null && $value !== '' && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $value)) {
                    $this->addError($field, "El campo {$field} solo puede contener letras");
                }
                break;
                
            case 'alphanumeric':
                if ($value !== null && $value !== '' && !preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]+$/', $value)) {
                    $this->addError($field, "El campo {$field} solo puede contener letras y números");
                }
                break;
                
            case 'unique':
                // Format: unique:table,column
                if ($ruleParam && $value !== null && $value !== '') {
                    list($table, $column) = explode(',', $ruleParam);
                    if ($this->checkUnique($table, $column, $value)) {
                        $this->addError($field, "El valor de {$field} ya existe");
                    }
                }
                break;
        }
    }
    
    /**
     * Add error message
     */
    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    /**
     * Check if value is unique in database
     */
    private function checkUnique($table, $column, $value) {
        try {
            $db = Database::getInstance();
            $result = $db->fetchOne(
                "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?",
                [$value]
            );
            return $result['count'] > 0;
        } catch (Exception $e) {
            return false; // Si hay error, no bloquear validación
        }
    }
    
    /**
     * Static helper for quick validation
     * 
     * Example:
     * if (!Validator::check($data, ['email' => 'required|email'])) {
     *     // error
     * }
     */
    public static function check($data, $rules) {
        $validator = new self($data);
        $validator->validate($rules);
        return $validator->passes();
    }
    
    /**
     * Static helper that throws exception on failure
     * 
     * Example:
     * Validator::requireValid($data, [
     *     'email' => 'required|email',
     *     'password' => 'required|min:6'
     * ]);
     */
    public static function requireValid($data, $rules) {
        $validator = new self($data);
        $validator->validate($rules);
        
        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }
        
        return true;
    }
}

/**
 * Custom validation exception
 */
class ValidationException extends Exception {
    private $errors;
    
    public function __construct($errors) {
        $this->errors = $errors;
        $firstError = reset($errors);
        $message = is_array($firstError) ? reset($firstError) : $firstError;
        parent::__construct($message);
    }
    
    public function getErrors() {
        return $this->errors;
    }
}
