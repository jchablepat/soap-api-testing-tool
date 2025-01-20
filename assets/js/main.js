let currentOperations = {};
// Obtener referencias a los elementos
const input = document.getElementById('wsdl-url');
const sugerencias = document.getElementById('sugerencias');
// Manejadores de eventos
input.addEventListener('input', UpdateOptions);
        
// Cerrar sugerencias al hacer clic fuera
document.addEventListener('click', (e) => {
    if (e.target !== input && e.target !== sugerencias) {
        sugerencias.style.display = 'none';
    }
});

// Para Edge: mostrar sugerencias al hacer clic en el input
input.addEventListener('click', () => {
    if (input.value.length <= 0) {
        UpdateOptions();
    }
});

// Función para agregar opciones al datalist
function UpdateOptions() {
    const wsdls = [
        "https://www.crcind.com/csp/samples/SOAP.Demo.CLS?WSDL=1",
        "http://sandbox-services-app.vt-software.com/WORLDPASS/WPAmazingJourneys.svc/wsdl?singleWsdl", 
        "http://www.stubademo.com/RXLStagingServices/ASMX/XmlService.asmx?WSDL",
        "https://secure.teenvio.com/v4/public/api/soap/wsdl.xml"
    ];
    
    // const filtro = input.value.toLowerCase();
    // const wsdlFiltered = wsdls.filter(wsdl => 
    //     wsdl.toLowerCase().includes(filtro)
    // );
    
    // if (filtro.length > 0) {
        sugerencias.innerHTML = '';
        // wsdlFiltered.forEach(...)
        wsdls.forEach(fruta => {
            const div = document.createElement('div');
            div.textContent = fruta;
            div.onclick = () => {
                input.value = fruta;
                sugerencias.style.display = 'none';
            };
            sugerencias.appendChild(div);
        });
        sugerencias.style.display = 'block';
    // } else {
    //     sugerencias.style.display = 'none';
    // }
}

async function loadOperations() {
    const wsdlUrl = document.getElementById('wsdl-url').value;
    const loadBtn = document.getElementById('load-operations');
    const select = document.getElementById('operation-select');
    const executeBtn = document.getElementById('execute-btn');
    const result = document.getElementById('result');
    const paramscontainer = document.getElementById('params-container');
    paramscontainer.innerHTML = '';
    
    if (!wsdlUrl) {
        alert('Por favor ingrese una URL de WSDL');
        return;
    }

    try {
        loadBtn.disabled = true;
        loadBtn.textContent = 'Cargando...';
        select.innerHTML = '<option value="">Cargando operaciones...</option>';

        const response = await axios.post('soapProxy.php', { wsdl: wsdlUrl, mtd: 100 });
        currentOperations = response.data;

        select.innerHTML = '<option value="">Seleccione una operación</option>';
        Object.keys(currentOperations).forEach(op => {
            const option = document.createElement('option');
            option.value = op;
            option.textContent = op;
            select.appendChild(option);
        });

        select.disabled = false;
        executeBtn.disabled = false;
    } catch (error) {
        alert('Error al cargar las operaciones: ' + error.message);
        select.innerHTML = '<option value="">Error al cargar operaciones</option>';
        result.innerHTML = `<div class="error">${error.response.data.error || error.message}</div>`;
    } finally {
        loadBtn.disabled = false;
        loadBtn.textContent = 'Cargar Operaciones';
    }
}

function generateParamFields() {
    const operation = document.getElementById('operation-select').value;
    const container = document.getElementById('params-container');
    container.innerHTML = '';

    if (!operation || !currentOperations[operation]) return;

    const inputParams = currentOperations[operation].input_parameters;

    if (inputParams.properties) {
        inputParams.properties.forEach(prop => {
            CreateElement(prop, 0, prop.name);
        });
    }
}

function CreateElement(prop, level = 0) {
    const container = document.getElementById('params-container');
    const row = document.createElement('div');
    row.className = `param-row`;

    if (level > 0) {
        row.classList.add('param-child');
    }

    // Contenedor para la fila principal que incluirá el botón toggle y el contenido
    const rowContent = document.createElement('div');
    rowContent.className = 'row-content';

    // Crear botón toggle si tiene propiedades hijo
    if (prop.properties && prop.properties.length > 0) {
        const toggleButton = document.createElement('button');
        toggleButton.className = 'toggle-btn expanded';
        toggleButton.innerHTML = '▼';
        toggleButton.onclick = function(e) {
            e.preventDefault();
            const isExpanded = this.classList.contains('expanded');
            // Obtener el contenedor de hijos más cercano
            const childrenContainer = this.closest('.param-row').querySelector('.children-container');
            
            if (isExpanded) {
                this.innerHTML = '▶';
                this.classList.remove('expanded');
                // this.classList.add('collapsed');
                childrenContainer.style.display = 'none';
            } else {
                this.innerHTML = '▼';
                // this.classList.remove('collapsed');
                this.classList.add('expanded');
                childrenContainer.style.display = 'block';
            }
        };
        rowContent.appendChild(toggleButton);
        rowContent.classList.add("row-item");
    }
    
    const label = document.createElement('label');
    label.innerHTML = `${prop.name} <span class="param-type">(${prop.type})</span>:`;

    const input = document.createElement('input');
    // Añadimos una clase adicional para los elementos anidados
    if (!prop.properties && level > 0 || (!prop.properties && level == 0)) {
        input.type = InputType(prop.type);
        input.id = `param-${prop.name}`;
        input.placeholder = getPlaceholderForType(prop.type);
    }

    rowContent.appendChild(label);
    
    if (!prop.properties && level > 0 || (!prop.properties && level == 0)) {
        rowContent.appendChild(input);
    }
    row.appendChild(rowContent);

    // Recursivamente creamos los elementos hijos, incrementando el nivel
    if (prop.properties) {
        const childrenContainer = document.createElement('div');
        childrenContainer.className = 'children-container';

        prop.properties.forEach(prop2 => {
            CreateElement(prop2, level + 1);

            // Mover los elementos hijo creados al contenedor de hijos
            const lastChild = container.lastElementChild;
            childrenContainer.appendChild(lastChild);
        });
        row.appendChild(childrenContainer);
    }

    container.appendChild(row);
}

function InputType(type) {
    var types = {
        'int' : 'number',
        'date' : 'date',
        'text' : 'text',
        'boolean': 'checkbox',
        'date' : 'date'
    };

    return types[type] || 'text';
}

function getPlaceholderForType(type) {
    switch(type.toLowerCase()) {
        case 'string': return 'texto';
        case 'int': case 'integer': return '123';
        case 'float': case 'double': return '123.45';
        case 'boolean': return 'true/false';
        case 'datetime': return '2024-01-01T00:00:00';
        default: return 'valor';
    }
}

async function executeOperation() {
    const operation = document.getElementById('operation-select').value;
    const wsdlUrl = document.getElementById('wsdl-url').value;
    const resultDiv = document.getElementById('result');
    const executeBtn = document.getElementById('execute-btn');

    if (!operation || !wsdlUrl) {
        alert('Por favor seleccione una operación y asegúrese de tener una URL de WSDL');
        return;
    }

    const params = {};
    const paramInputs = document.querySelectorAll('#params-container input');
    paramInputs.forEach(input => {
        const paramName = input.id.replace('param-', '');
        params[paramName] = input.type == "checkbox" ? input.checked : input.value;
    });

    try {
        executeBtn.disabled = true;
        executeBtn.textContent = 'Ejecutando...';
        resultDiv.textContent = 'Ejecutando operación...';

        const response = await axios.post('soapProxy.php', {
            mtd: 101,
            wsdl: wsdlUrl,
            operation: operation,
            params: params
        });

        // resultDiv.textContent = JSON.stringify(response.data, null, 2);
            // Formatear el resultado
        let resultHtml = `
            <div>
                <h3>Data:</h3>
                <pre class="result">${JSON.stringify(response.data.data, null, 2)}</pre>
                <div class="xml-title">
                    Details
                    <button class="toggle-btn" onclick="toggleXML('result')">Mostrar/Ocultar</button>
                </div>
                <div id="result-xml" class="soap-state collapsed">
                    ${response.data.state}
                </div>
                
                <div class="xml-title">
                    Request XML
                    <button class="toggle-btn" onclick="toggleXML('request')">Mostrar/Ocultar</button>
                </div>
                <div id="request-xml" class="xml-container collapsed">
                    <pre class="xml-content">${formatXML(response.data.request)}</pre>
                </div>

                <div class="xml-title">
                    Response XML
                    <button class="toggle-btn" onclick="toggleXML('response')">Mostrar/Ocultar</button>
                </div>
                <div id="response-xml" class="xml-container collapsed">
                    <pre class="xml-content">${formatXML(response.data.response)}</pre>
                </div>
            </div>
        `;

        resultDiv.innerHTML = resultHtml;
    } catch (error) {
        resultDiv.textContent = `Error: ${error.response.data.error || error.message}`;
    } finally {
        executeBtn.disabled = false;
        executeBtn.textContent = 'Ejecutar Operación';
    }
}

function formatXML(xml) {
    if (!xml) return '';
    
    // Función auxiliar para escapar caracteres especiales
    function escapeXML(text) {
        const escapeMap = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&apos;'
        };
        return text.replace(/[&<>"']/g, match => escapeMap[match]);
    }

    // Función auxiliar para detectar si una línea es un comentario XML
    function isComment(line) {
        return line.trim().startsWith('<!--') && line.trim().endsWith('-->');
    }

    // Función auxiliar para detectar si una línea es CDATA
    function isCDATA(line) {
        return line.trim().startsWith('<![CDATA[') && line.trim().endsWith(']]>');
    }

    // Función auxiliar para detectar si una línea es una etiqueta de procesamiento
    function isProcessingInstruction(line) {
        return line.trim().startsWith('<?') && line.trim().endsWith('?>');
    }

    // Escapar el XML
    let formatted = escapeXML(xml);

    // Preservar espacios en CDATA y comentarios
    const cdataBlocks = [];
    const commentBlocks = [];
    let blockCounter = 0;

    // Guardar bloques CDATA
    formatted = formatted.replace(/(&lt;!\[CDATA\[.*?\]\]&gt;)/gs, (match) => {
        const placeholder = `###CDATA${blockCounter}###`;
        cdataBlocks.push({ placeholder, content: match });
        blockCounter++;
        return placeholder;
    });

    // Guardar comentarios
    formatted = formatted.replace(/(&lt;!--.*?--&gt;)/gs, (match) => {
        const placeholder = `###COMMENT${blockCounter}###`;
        commentBlocks.push({ placeholder, content: match });
        blockCounter++;
        return placeholder;
    });

    // Añadir saltos de línea
    formatted = formatted
        .replace(/>\s*/g, '>\n')
        .replace(/(<\/[^>]*>)/g, '$1\n')
        .replace(/(<[^\/][^>]*[^\/]>)\s*/g, '$1\n');

    // Procesar línea por línea
    let indent = 0;
    let result = '';
    const lines = formatted.split('\n');

    for (let i = 0; i < lines.length; i++) {
        let line = lines[i].trim();
        if (!line) continue;

        // Restaurar CDATA y comentarios
        const cdataMatch = cdataBlocks.find(block => line.includes(block.placeholder));
        const commentMatch = commentBlocks.find(block => line.includes(block.placeholder));

        if (cdataMatch) {
            line = line.replace(cdataMatch.placeholder, cdataMatch.content);
        } else if (commentMatch) {
            line = line.replace(commentMatch.placeholder, commentMatch.content);
        }

        // Manejar la indentación
        if (line.match(/^<\//)) {
            indent = Math.max(0, indent - 1);
        }

        // Añadir la línea con la indentación correcta
        if (line.length > 0) {
            result += '  '.repeat(indent) + line + '\n';
        }

        // Ajustar la indentación para la siguiente línea
        if (!line.match(/^<\?/) && // No es una instrucción de procesamiento
            !line.match(/^<!/)) {   // No es un comentario o DOCTYPE
            if (line.match(/<[^/][^>]*[^/]>$/) && 
                !line.match(/\/>/)) { // Es una etiqueta de apertura
                indent++;
            }
        }
    }

    // Aplicar coloreado sintáctico
    result = result
        // Etiquetas
        .replace(/&lt;(\/?[a-zA-Z0-9:-]+)(?=\s|&gt;)/g, '<span class="xml-tag">&lt;$1</span>')
        .replace(/(&lt;\/[a-zA-Z0-9:-]+)&gt;/g, '<span class="xml-tag">$1&gt;</span>')
        // Atributos
        .replace(/([a-zA-Z0-9:-]+)=(&quot;.*?&quot;)/g, '<span class="xml-attr-name">$1</span>=<span class="xml-attr-value">$2</span>')
        // Cierre de etiquetas
        .replace(/&gt;/g, '<span class="xml-tag">&gt;</span>')
        // CDATA
        .replace(/(&lt;!\[CDATA\[.*?\]\]&gt;)/g, '<span class="xml-cdata">$1</span>')
        // Comentarios
        .replace(/(&lt;!--.*?--&gt;)/g, '<span class="xml-comment">$1</span>')
        // Instrucciones de procesamiento
        .replace(/(&lt;\?.*?\?&gt;)/g, '<span class="xml-processing">&1</span>');

    return result;
}

function toggleXML(type) {
    const element = document.getElementById(`${type}-xml`);
    element.classList.toggle('collapsed');
}