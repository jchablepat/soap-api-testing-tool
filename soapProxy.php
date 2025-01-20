<?php
// header('Content-Type: application/json');
header('Content-Type: text/xml; charset=utf-8');

include_once './SOAPManager.php';

$data = json_decode(file_get_contents('php://input'), true);

$iMethod = null;
if(isset($data["mtd"])) {
    $iMethod = (int)$data["mtd"];
}

function GetOperations() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $wsdl = $data['wsdl'] ?? null;

        if (!$wsdl) {
            throw new Exception('WSDL URL is required');
        }

        $soapClient = new SOAPManager($wsdl);
        $operations = $soapClient->analyzeOperations();

        echo json_encode($operations);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function TestOperation() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $wsdl = $data['wsdl'] ?? null;
        $operation = $data['operation'] ?? null;
        $params = $data['params'] ?? [];

        if (!$wsdl || !$operation) {
            throw new Exception('WSDL URL and operation are required');
        }

        $client = new SoapClient($wsdl, [
            'soap_version' => SOAP_1_2,
            'trace' => true,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE
        ]);

        $result = $client->__soapCall($operation, [$params]);

        // Convertir el resultado a un array para JSON
        $result = json_decode(json_encode($result), true);

        echo json_encode([
            'success' => true,
            'data' => $result,
            'request' => $client->__getLastRequest(),
            'response' => $client->__getLastResponse(),
            'state' => $client->__getLastResponseHeaders()
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function BadRequest() {
    $aResponse = [
        "state" => false,
        "error" => "bad request not found"
    ];

    echo json_encode($aResponse);
}

switch ($iMethod) {
    case 100:
        GetOperations();
        break;
    case 101:
        TestOperation();
        break;

    default:
        BadRequest();
        exit();
        break;
}


