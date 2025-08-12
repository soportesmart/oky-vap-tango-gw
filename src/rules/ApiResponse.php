<?php

namespace BusinessRules;
/**
 * Description of Response
 *
 * @author hgmena
 */
abstract class ApiResponse {
    public $operationResult = '';
    public $message = '';  
}

class GenericResponse extends ApiResponse {  
}

class ScoringResponse extends ApiResponse {
    public $risk;
    public $score;
}

class ValidateNumberResponse { 
    public $valid = '';
    public $message = '';
    public $number = '';
    public $local_format = '';
    public $international_format = '';
    public $country_prefix = '';
    public $country_code = '';
    public $country_name = '';
    public $carrier = '';
    public $line_type = '';
    public $validation_provider = '';
}
