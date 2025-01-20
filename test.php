<?php
header('Content-Type: application/json');
include_once './SOAPManager.php';

try {
    // URL del WSDL
    $wsdl = 'https://secure.teenvio.com/v4/public/api/soap/wsdl.xml';

    $soapClient = new SOAPManager($wsdl);

    // Conectar al servicio
    $soapClient->Connect();

    // Ver funciones disponibles
    print_r($soapClient->getTypes());
    // echo json_encode($soapClient->getFunctionsInfo(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    // Llamar a un método del servicio
    // $params = [
    //     'iIdHotelChain' => 1,
    //     'sAppPassword' => 'XXX'
    // ];

    // $result = $soapClient->CallMethod("GetPrefixes", $params);
    // print_r($result);

    // Llamar a un método con el nombre directamente
    // $retrievePrefixes = $soapClient->Call_GetPrefixies(1);
    // print_r($retrievePrefixes->GetPrefixesResult->PrefixResponse);

    // echo json_encode($soapClient->analyzeOperations(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    // Ver todas las operaciones y sus parámetros
    // $operations = $soapClient->analyzeOperations();
    // foreach ($operations as $name => $details) {
    //     echo "<h3>Operación: $name\n"."</h3>";
    //     echo "<h4>Tipo de retorno: {$details['return_type']}\n</h4>";
    //     echo "<h5>Parámetros:\n</h5>";
    //     echo json_encode($details['parameter_types'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    //     echo "\n";
    // }

    // Generar un ejemplo de request para una operación específica
    $sampleRequest = $soapClient->generateSampleRequest('GetPrefixes');
    print_r($sampleRequest);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

