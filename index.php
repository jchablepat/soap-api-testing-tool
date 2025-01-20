<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOAP Service Tester</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.6.2/axios.min.js"></script>
    <link href="./assets/css/main.css" type="text/css" rel="stylesheet">
    <link rel="shortcut icon" href="./assets/img/favicon.ico">
</head>
<body>
    <div class="container">
        <!-- Panel de entrada -->
        <div class="panel">
            <h2>SOAP Service Tester</h2>
            <div class="form-group" style="position:relative;">
                <label for="wsdl-url">URL del WSDL:</label>
                <input type="search" autocomplete="off" id="wsdl-url" placeholder="https://ejemplo.com/servicio?wsdl" title="Use Service Description">
                <div id="sugerencias" class="suggestions-list"></div>
            </div>
            <button id="load-operations" onclick="loadOperations()">Cargar Operaciones</button>
            
            <div class="form-group" style="margin-top: 20px;">
                <label for="operation-select">Operación:</label>
                <select id="operation-select" onchange="generateParamFields()" disabled>
                    <option value="">Seleccione una operación</option>
                </select>
            </div>

            <div id="params-container" class="params-container"></div>
            
            <button id="execute-btn" onclick="executeOperation()" style="margin-top: 20px;" disabled>
                Ejecutar Operación
            </button>
        </div>

        <!-- Panel de resultado -->
        <div class="panel">
            <h2>Resultado</h2>
            <div id="result" class="result">// El resultado se mostrará aquí</div>
        </div>
    </div>

    <script src="./assets/js/main.js" type="text/javascript"></script>
</body>
</html>