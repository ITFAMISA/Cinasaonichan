<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/pdf_ordenes_model.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo');
    }

    $file = $_FILES['pdf_file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];

    // Validar que sea un PDF
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($fileExtension !== 'pdf') {
        throw new Exception('Solo se permiten archivos PDF');
    }

    // Validar tamaño (máximo 10MB)
    if ($fileSize > 10 * 1024 * 1024) {
        throw new Exception('El archivo es demasiado grande. Máximo 10MB');
    }

    // Crear directorio de uploads si no existe
    $uploadDir = __DIR__ . '/../../uploads/pdf_ordenes/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Generar nombre único para el archivo
    $uniqueFileName = uniqid('orden_') . '_' . time() . '.pdf';
    $uploadPath = $uploadDir . $uniqueFileName;

    // Mover el archivo
    if (!move_uploaded_file($fileTmpName, $uploadPath)) {
        throw new Exception('Error al guardar el archivo');
    }

    // Extraer texto del PDF
    $textoExtraido = extraerTextoPDF($uploadPath);

    if (empty($textoExtraido)) {
        throw new Exception('No se pudo extraer texto del PDF. El archivo puede estar escaneado o protegido.');
    }

    // Intentar estructurar los datos
    $datosEstructurados = analizarTextoOrden($textoExtraido);

    // Guardar en base de datos
    $model = new PdfOrdenesModel($pdo);
    $idPdf = $model->guardarPdfProcesado([
        ':nombre_archivo' => $fileName,
        ':ruta_archivo' => 'uploads/pdf_ordenes/' . $uniqueFileName,
        ':texto_extraido' => $textoExtraido,
        ':datos_estructurados' => json_encode($datosEstructurados),
        ':template_usado' => null,
        ':estatus' => 'pendiente',
        ':usuario_proceso' => $_SESSION['usuario_id'] ?? 'SYSTEM'
    ]);

    echo json_encode([
        'success' => true,
        'mensaje' => 'PDF procesado correctamente',
        'data' => [
            'id' => $idPdf,
            'texto_extraido' => $textoExtraido,
            'datos_estructurados' => $datosEstructurados,
            'nombre_archivo' => $fileName
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ]);
}

/**
 * Extrae texto de un archivo PDF
 */
function extraerTextoPDF($rutaPdf) {
    $texto = '';

    // Método 1: Intentar con pdftotext (si está instalado)
    if (function_exists('shell_exec')) {
        $comando = 'pdftotext "' . $rutaPdf . '" -';
        $texto = shell_exec($comando);
    }

    // Método 2: Usar librería PHP pura (requiere smalot/pdfparser)
    if (empty($texto) && file_exists(__DIR__ . '/../../vendor/autoload.php')) {
        try {
            require_once __DIR__ . '/../../vendor/autoload.php';
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($rutaPdf);
            $texto = $pdf->getText();
        } catch (Exception $e) {
            // Si falla, continuar con método alternativo
        }
    }

    // Método 3: Extracción básica usando regex (limitado)
    if (empty($texto)) {
        $contenido = file_get_contents($rutaPdf);
        if (preg_match_all('/\(([^\)]+)\)/s', $contenido, $matches)) {
            $texto = implode(' ', $matches[1]);
            $texto = str_replace(['\\n', '\\r'], "\n", $texto);
        }
    }

    return trim($texto);
}

/**
 * Analiza el texto extraído e intenta identificar campos comunes
 */
function analizarTextoOrden($texto) {
    $datos = [
        'numero_orden' => null,
        'fecha_orden' => null,
        'proveedor' => null,
        'cliente' => null,
        'productos' => [],
        'total' => null,
        'moneda' => null,
        'lineas_texto' => []
    ];

    // Dividir en líneas
    $lineas = explode("\n", $texto);
    $datos['lineas_texto'] = array_filter(array_map('trim', $lineas));

    // Buscar número de orden (patrones comunes)
    $patronesOrden = [
        '/Order:\s*([A-Z0-9\-]+)/i',  // Formato Gunderson: "Order: GGYD0585-3"
        '/(?:orden|order|po|purchase\s*order)[\s#:]*([A-Z0-9\-]+)/i',
        '/(?:no\.|num|number)[\s#:]*([A-Z0-9\-]+)/i'
    ];

    foreach ($patronesOrden as $patron) {
        if (preg_match($patron, $texto, $matches)) {
            $datos['numero_orden'] = trim($matches[1]);
            break;
        }
    }

    // Buscar fechas (varios formatos)
    $patronesFecha = [
        '/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/',
        '/(\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2})/',
        '/(?:date|fecha)[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i'
    ];

    foreach ($patronesFecha as $patron) {
        if (preg_match($patron, $texto, $matches)) {
            $datos['fecha_orden'] = trim($matches[1]);
            break;
        }
    }

    // Buscar montos (con símbolos de moneda)
    if (preg_match('/(?:total|amount|monto)[\s:]*\$?\s*([\d,]+\.?\d*)/i', $texto, $matches)) {
        $datos['total'] = str_replace(',', '', $matches[1]);
    }

    // Buscar moneda
    if (preg_match('/\b(USD|MXN|EUR|CAD)\b/i', $texto, $matches)) {
        $datos['moneda'] = strtoupper($matches[1]);
    }

    // Buscar códigos de productos (patrones numéricos comunes)
    preg_match_all('/\b(\d{6,})\b/', $texto, $codigosEncontrados);
    if (!empty($codigosEncontrados[1])) {
        $datos['codigos_detectados'] = array_unique($codigosEncontrados[1]);
    }

    // NUEVA FUNCIONALIDAD: Extraer productos detallados
    $datos['productos'] = extraerProductosDetallados($texto, $lineas);

    return $datos;
}

/**
 * Extrae información detallada de productos del PDF
 */
function extraerProductosDetallados($texto, $lineas) {
    $productos = [];

    // ESTRATEGIA: Buscar productos en múltiples líneas
    // Formato Gunderson: El código está en una línea, la descripción en varias, y cantidad/precio en otra

    $i = 0;
    $totalLineas = count($lineas);

    while ($i < $totalLineas) {
        $linea = trim($lineas[$i]);

        // Buscar línea que empiece con código de producto (XXXXXX-S XXXXXX)
        if (preg_match('/^(\d{6,9})\-S\s+(\d{6,9})/', $linea, $codigoMatch)) {
            $codigoConS = trim($codigoMatch[1]) . '-S';
            $codigoSolo = trim($codigoMatch[2]);

            // Acumular descripción de las siguientes líneas hasta encontrar la línea con cantidad y precio
            $descripcionParts = [];
            $cantidad = null;
            $unidad = null;
            $precioUnitario = null;
            $precioTotal = null;
            $fechaEntrega = null;

            // Buscar en las siguientes 10 líneas máximo
            for ($j = $i + 1; $j < min($i + 10, $totalLineas); $j++) {
                $lineaSiguiente = trim($lineas[$j]);

                // Buscar línea con cantidad, unidad y precios
                // Formato: "09/10/2025	96.00	EA	2,203.18	211,505.28"
                if (preg_match('/(\d{2}\/\d{2}\/\d{4})\s+(\d+\.?\d*)\s+(EA|PZ|KG|LB|MT|UN|FT|UND|PC|PCS)\s+([\d,]+\.\d+)\s+([\d,]+\.\d+)/i', $lineaSiguiente, $datosMatch)) {
                    $fechaEntrega = $datosMatch[1];
                    $cantidad = floatval($datosMatch[2]);
                    $unidad = strtoupper($datosMatch[3]);
                    $precioUnitario = floatval(str_replace(',', '', $datosMatch[4]));
                    $precioTotal = floatval(str_replace(',', '', $datosMatch[5]));

                    // Producto completo encontrado
                    $productos[] = [
                        'material_code' => $codigoSolo,
                        'descripcion' => implode(' ', $descripcionParts),
                        'cantidad' => $cantidad,
                        'unidad_medida' => $unidad,
                        'precio_unitario' => $precioUnitario,
                        'total_linea' => $precioTotal,
                        'fecha_entrega' => $fechaEntrega
                    ];

                    // Mover el índice después del producto procesado
                    $i = $j;
                    break;
                }

                // Si la línea no es datos de producto, es parte de la descripción
                // Ignorar líneas vacías y líneas que parecen ser otra cosa
                if (!empty($lineaSiguiente) &&
                    !preg_match('/^(Partial|Open order|Receipt)$/i', $lineaSiguiente) &&
                    !preg_match('/^\d{6,9}\-S/', $lineaSiguiente)) {
                    $descripcionParts[] = $lineaSiguiente;
                }
            }
        }

        $i++;
    }

    // Si no se encontraron productos, intentar otros patrones
    if (empty($productos)) {
        $productos = extraerProductosPatronesAlternativos($texto, $lineas);
    }

    // Filtrar productos duplicados por código
    $productosUnicos = [];
    $codigosVistos = [];
    foreach ($productos as $producto) {
        $codigo = $producto['material_code'] ?? '';
        if ($codigo && !in_array($codigo, $codigosVistos)) {
            $codigosVistos[] = $codigo;
            $productosUnicos[] = $producto;
        }
    }

    // Enriquecer cada producto con campos adicionales
    foreach ($productosUnicos as &$producto) {
        $producto = array_merge($producto, extraerCamposAdicionales($texto, $producto['material_code'] ?? ''));
    }

    return $productosUnicos;
}

/**
 * Extrae productos usando patrones alternativos
 */
function extraerProductosPatronesAlternativos($texto, $lineas) {
    $productos = [];

    // Solo usar este método como último recurso
    // Buscar patrones muy específicos que indiquen una línea de producto real

    foreach ($lineas as $idx => $linea) {
        // Patrón: Código numérico de producto (6-9 dígitos) seguido de descripción técnica
        // Y que contenga al menos cantidad Y unidad de medida
        if (preg_match('/^([0-9]{6,9}(?:\-[A-Z0-9]+)?)\s+(.+?)\s+(\d+(?:\.\d+)?)\s+(EA|PZ|KG|LB|MT|FT|UN|UND|PC|PCS)/i', $linea, $matches)) {
            $codigo = trim($matches[1]);
            $desc = trim($matches[2]);
            $cantidad = floatval($matches[3]);
            $unidad = strtoupper(trim($matches[4]));

            // Buscar precio en la misma línea
            $precio = null;
            if (preg_match('/\$?\s*([\d,]+\.\d{2})$/i', $linea, $precioMatch)) {
                $precio = floatval(str_replace(',', '', $precioMatch[1]));
            }

            // Solo agregar si tiene características de producto real
            // (código numérico, descripción razonable, cantidad, unidad)
            if (strlen($desc) > 5 && strlen($desc) < 200) {
                $productos[] = [
                    'material_code' => $codigo,
                    'descripcion' => $desc,
                    'cantidad' => $cantidad,
                    'unidad_medida' => $unidad,
                    'precio_unitario' => $precio
                ];
            }
        }
    }

    return $productos;
}

/**
 * Extrae campos adicionales para un producto específico
 */
function extraerCamposAdicionales($texto, $codigoProducto) {
    $campos = [];

    // Buscar drawing number
    if (preg_match('/(?:drawing|dwg|plano)[\s#:]*([A-Z0-9\-]+)/i', $texto, $matches)) {
        $campos['drawing_number'] = trim($matches[1]);
    }

    // Buscar drawing version
    if (preg_match('/(?:rev|version|ver)[\s:]*([A-Z0-9]+)/i', $texto, $matches)) {
        $campos['drawing_version'] = trim($matches[1]);
    }

    // Buscar ECM number
    if (preg_match('/(?:ecm|ecn)[\s#:]*(\d+)/i', $texto, $matches)) {
        $campos['ecm_number'] = trim($matches[1]);
    }

    // Buscar HTS code
    if (preg_match('/(?:hts|hs\s*code|tariff)[\s:]*(\d{6,10})/i', $texto, $matches)) {
        $campos['hts_code'] = trim($matches[1]);
    }

    // Buscar peso (weight)
    if (preg_match('/(?:weight|peso|wt)[\s:]*(\d+(?:\.\d+)?)\s*(kg|lb|g)?/i', $texto, $matches)) {
        $campos['peso'] = floatval($matches[1]);
        $campos['unidad_peso'] = isset($matches[2]) ? strtoupper($matches[2]) : 'KG';
    }

    // Buscar material
    $materiales = ['steel', 'acero', 'aluminum', 'aluminio', 'plastic', 'plastico', 'copper', 'cobre', 'brass', 'stainless'];
    foreach ($materiales as $mat) {
        if (preg_match('/\b(' . $mat . '[^\n]*?)\b/i', $texto, $matches)) {
            $campos['material'] = trim($matches[1]);
            break;
        }
    }

    // Buscar acabado/finish
    $acabados = ['painted', 'pintado', 'anodized', 'anodizado', 'galvanized', 'galvanizado', 'coated', 'plated', 'chromated'];
    foreach ($acabados as $acabado) {
        if (preg_match('/\b(' . $acabado . '[^\n]*?)\b/i', $texto, $matches)) {
            $campos['acabado'] = trim($matches[1]);
            break;
        }
    }

    // Buscar país de origen
    $paises = ['mexico', 'méxico', 'usa', 'united states', 'china', 'japan', 'japon', 'germany', 'alemania', 'canada', 'taiwan', 'korea', 'corea'];
    foreach ($paises as $pais) {
        if (preg_match('/(?:origin|country|made\s+in|origen)[\s:]*(' . $pais . ')/i', $texto, $matches)) {
            $campos['pais_origen'] = ucfirst(trim($matches[1]));
            break;
        }
    }

    // Buscar sistema de calidad
    if (preg_match('/\b(ISO\s*\d+|IATF\s*\d+|AS\s*\d+|J\d+)\b/i', $texto, $matches)) {
        $campos['sistema_calidad'] = strtoupper(trim($matches[1]));
    }

    // Buscar especificaciones
    if (preg_match('/(?:spec|specification|especificacion)[\s:]*([^\n]+)/i', $texto, $matches)) {
        $campos['especificaciones'] = trim($matches[1]);
    }

    // Detectar tipo de parte
    if (preg_match('/\b(standard|custom|raw\s*material)\b/i', $texto, $matches)) {
        $campos['tipo_parte'] = ucwords(strtolower(trim($matches[1])));
    }

    return $campos;
}
