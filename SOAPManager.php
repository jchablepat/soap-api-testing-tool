<?php
include_once './SoapParametersNames.php';

class SOAPManager {
    private SoapClient $client;
    private string $wsdl;
    private array $options;
    private array $actionArguments;
    private string $sAppPassword = '';
    private array $allTypes;

    public function __construct(string $wsdl) {
        $this->wsdl = $wsdl;

        $this->options = [
            'soap_version' => SOAP_1_2,
            'trace' => true,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            // 'features' => SOAP_SINGLE_ELEMENT_ARRAYS
        ];

        $this->Connect();

        // Cachear todos los tipos al inicio
        $this->allTypes = $this->parseAllTypes();
    }

    public function Connect() : void {
        try {
            $this->client = new SoapClient($this->wsdl, $this->options);
        } catch (SoapFault $e) {
            throw new Exception("Error al conectar con el Servicio SOAP: ". $e->getMessage());
        }
    }
    
    /**
     * Llama a cualquier método del servicio web
     *
     * @param  string $method
     * @param  array $params
     * @return mixed
     */
    public function CallMethod(string $method, array $params = []) {
        try {
            if(isset($this->client)) {
                $this->Connect();
            }

            $result = $this->client->__soapCall($method, [$params]);

            // Registrar la solicitud y respuesta para depuración
            $this->logSoapCall($method);

            return $result;
        } catch (SoapFault $e) {
            throw new Exception("Error al llamar el método '$method':".$e->getMessage());
        }
    }

    private function logSoapCall(string $method): void {
        // Registrar la última solicitud
        $requestXml = $this->client->__getLastRequest();
        $responseXml = $this->client->__getLastResponse();
        
        error_log("Solicitud SOAP para $method: " . PHP_EOL . $requestXml);
        error_log("Respuesta SOAP para $method: " . PHP_EOL . $responseXml);
    }
    
    /**
     * Obtiene la lista de funciones disponibles
     *
     * @return array
     */
    public function GetFunctions() :array {
        try {
            return $this->client->__getFunctions();
        } catch (SoapFault $e) {
            throw new Exception("Error al listar las funciones SOAP: ".$e->getMessage());
        }
    }
    
    /**
     * Obtiene los tipos de datos definidos en el WSDL
     *
     * @return array
     */
    public function getTypes(): array {
        return $this->client->__getTypes();
    }
    
    /**
     * Separamos las propiedades de la estructura del mensaje en un arreglo
     * 
     * Ejemplo: struct { string sPropName }
     *
     * @return array
     */
    private function parseAllTypes(): array {
        $rawTypes = $this->GetTypes();
        $types = [];
        
        foreach ($rawTypes as $type) {
            if (preg_match('/struct\s+(\w+)\s*{(.*?)}/s', $type, $matches)) {
                $typeName = $matches[1];
                $typeContent = $matches[2];
                
                $properties = [];
                if (preg_match_all('/\s+(\w+)\s+(\w+)(?:\s+|\[.*?\])?\s*;/s', $typeContent, $propMatches)) {
                    for ($i = 0; $i < count($propMatches[1]); $i++) {
                        $properties[] = [
                            'type' => $propMatches[1][$i],
                            'name' => $propMatches[2][$i]
                        ];
                    }
                }
                
                $types[$typeName] = $properties;
            }
        }
        return $types;
    }
    
    /**
     * Call specific method 'GetPrefixes' directly of contract WCF(Windows Communication Foundation) service ...
     *
     * @param  int $iIdHotelChain
     * @return mixed
     */
    public function Call_GetPrefixies(int $iIdHotelChain)
    {
        $this->actionArguments = [
            SoapParametersNames::Prefixies_IdHotelChain => $iIdHotelChain,
            SoapParametersNames::AppPassword => $this->sAppPassword
        ];

        $response = $this->client->GetPrefixes($this->actionArguments);
        
        return $response;
    }

    public function analyzeOperations(): array {
        $result = [];
        $functions = $this->GetFunctions();
        $types = $this->GetTypes();
        
        foreach ($functions as $function) {
            // Extraer nombre de la función y parámetros
            if (preg_match('/(\w+)\s+(\w+)\((.*?)\)/', $function, $matches)) {
                $returnType = $matches[1];
                $operationName = $matches[2];
                $params = $matches[3];

                // Buscar el tipo de request
                $responseType = $operationName . 'Response';

                // Obtener los parámetros directamente de la definición de la función
                $inputParams = $this->parseInputParameters($params, $types, $operationName);
                
                $result[$operationName] = [
                    'return_type' => $returnType,
                    'parameters' => $this->parseParameters($params),
                    'parameter_types' => $this->findParameterTypes($operationName, $types),
                    'input_parameters' => $inputParams,//$this->findComplexType($requestType, $types),
                    'output_parameters' => $this->findComplexType($responseType, $types)
                ];
            }
        }
        
        return $result;
    }

    private function parseInputParameters(string $params, array $types, string $operationName): array {
        if (empty(trim($params))) {
            return ['properties' => []];
        }

        // Dividir los parámetros si hay múltiples
        $paramList = array_map('trim', explode(',', $params));
        $properties = [];

        foreach ($paramList as $param) {
            // Separar tipo y nombre del parámetro
            $parts = array_values(array_filter(explode(' ', $param)));
            if (count($parts) >= 2) {
                $type = $parts[0];
                $name = $parts[1];

                $typeInfo = $this->analyzeType($type);
                if ($typeInfo) {
                    if($name == '$parameters') {
                        // Si es un tipo simple, agregarlo directamente
                        $properties = $typeInfo;
                    } else {
                        $properties[] = [
                            'name' => $name,
                            'type' => $type,
                            'complex' => true,
                            'properties' => $typeInfo
                        ];
                    }
                }
            }
        }

        return ['properties' => $properties];
    }

    private function findComplexType(string $typeName, array $types): array {
        $result = [];
        
        foreach ($types as $type) {
            // Buscar la definición exacta del tipo
            if (preg_match('/struct\s+' . preg_quote($typeName) . '\s+{(.*?)}/s', $type, $matches)) {
                $properties = [];
                
                // Extraer cada propiedad, manejando múltiples líneas y espacios
                if (preg_match_all('/\s+(\w+)\s+(\w+);/', $matches[1], $propMatches)) {
                    for ($i = 0; $i < count($propMatches[1]); $i++) {
                        $propType = $propMatches[1][$i];
                        $propName = $propMatches[2][$i];

                        $properties[] = [
                            'type' => $propType,
                            'name' => $propName
                        ];
                    }
                }
                
                $result = [
                    'type_name' => $typeName,
                    'properties' => $properties
                ];
                break;
            }
        }
        
        return $result;
    }

    private function analyzeType(string $typeName): ?array {
        if (!isset($this->allTypes[$typeName])) {
            return null;
        }

        $properties = [];
        foreach ($this->allTypes[$typeName] as $property) {
            $propertyType = $property['type'];
            $propertyName = $property['name'];
            
            // Verificar si este tipo también es complejo
            $nestedType = $this->analyzeType($propertyType);
            
            if ($nestedType !== null) {
                $properties[] = [
                    'name' => $propertyName,
                    'type' => $propertyType,
                    'complex' => true,
                    'properties' => $nestedType
                ];
            } else {
                $properties[] = [
                    'name' => $propertyName,
                    'type' => $propertyType,
                    'complex' => false
                ];
            }
        }

        return $properties;
    }
    
    private function parseParameters(string $params): array {
        if (empty($params)) {
            return [];
        }
        
        $parameters = [];
        $params = explode(',', $params);
        
        foreach ($params as $param) {
            $parts = array_values(array_filter(explode(' ', trim($param))));
            if (count($parts) >= 2) {
                $parameters[] = [
                    'type' => $parts[0],
                    'name' => $parts[1]
                ];
            }
        }
        
        return $parameters;
    }
    
    private function findParameterTypes(string $operationName, array $types): array {
        $paramTypes = [];
        
        foreach ($types as $type) {
            // Buscar estructuras relacionadas con la operación
            if (strpos($type, $operationName) !== false) {
                $paramTypes[] = $this->parseType($type);
            }
        }
        
        return $paramTypes;
    }
    
    private function parseType(string $type): array {
        $result = ['raw' => $type];
        
        // Extraer nombre del tipo
        if (preg_match('/struct\s+(\w+)\s+{/', $type, $matches)) {
            $result['name'] = $matches[1];
            
            // Extraer propiedades
            if (preg_match_all('/\s+(\w+)\s+(\w+);/', $type, $matches)) {
                $properties = [];
                for ($i = 0; $i < count($matches[1]); $i++) {
                    $properties[] = [
                        'type' => $matches[1][$i],
                        'name' => $matches[2][$i]
                    ];
                }
                $result['properties'] = $properties;
            }
        }
        
        return $result;
    }

    public function generateSampleRequest(string $operationName): array {
        $operations = $this->analyzeOperations();
        
        if (!isset($operations[$operationName])) {
            throw new Exception("Operación no encontrada: $operationName");
        }
        
        $sample = [];
        $inputParams = $operations[$operationName]['input_parameters'];

        if (isset($inputParams['properties'])) {
            foreach ($inputParams['properties'] as $prop) {
                $sample[$prop['name']] = $this->getExampleValue($prop['type']);
            }
        }
        
        return $sample;
    }
    
    private function getExampleValue(string $type): mixed {
        return match (strtolower($type)) {
            'string' => 'ejemplo',
            'int', 'integer' => 1,
            'float', 'double' => 1.0,
            'boolean', 'bool' => true,
            'datetime' => '2024-01-01T00:00:00',
            default => null
        };
    }
}