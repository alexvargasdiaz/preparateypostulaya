<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Preguntas\Models\AreaAcademica;
use Modules\Preguntas\Models\Concepto;
use Modules\Preguntas\Models\Pregunta;
use Modules\Preguntas\Models\Alternativa;

/**
 * Crea cursos (conceptos) y preguntas para las áreas académicas de
 * Salud, Económicas y Sociales (el área de Ingenierías ya se llena
 * con DemoDataSeeder).
 */
class PreguntasOtrasAreasSeeder extends Seeder
{
    private const AREAS_CONCEPTOS = [
        'Ciencias de la Salud, Farmacia y Bioquímica' => [
            'Anatomía humana',
            'Biología celular',
            'Química general',
            'Química orgánica',
            'Bioquímica',
            'Genética',
            'Ecología y medio ambiente',
            'Microbiología',
            'Salud pública',
        ],
        'Ciencias Económicas, Administrativas y de Gestión' => [
            'Razonamiento matemático',
            'Razonamiento verbal',
            'Matemática financiera',
            'Contabilidad básica',
            'Economía',
            'Administración y gestión',
            'Finanzas',
            'Marketing',
            'Estadística',
        ],
        'Ciencias Sociales, Derecho y de Humanidades' => [
            'Historia del Perú',
            'Historia universal',
            'Geografía',
            'Formación cívica',
            'Literatura',
            'Lenguaje y comunicación',
            'Sociología',
            'Filosofía',
            'Derecho constitucional',
        ],
    ];

    public function run(): void
    {
        $banco = $this->bancoPreguntas();
        $total = 0;

        foreach (self::AREAS_CONCEPTOS as $areaNombre => $conceptosNombres) {
            $area = AreaAcademica::where('nombre', $areaNombre)->first();
            if (!$area) {
                $this->command->warn("  → Área no encontrada: {$areaNombre}");
                continue;
            }

            foreach ($conceptosNombres as $conceptoNombre) {
                $concepto = Concepto::firstOrCreate(
                    ['nombre' => $conceptoNombre, 'area_academica_id' => $area->id],
                    ['descripcion' => "Preguntas sobre {$conceptoNombre}"]
                );

                $pregs = $banco[$conceptoNombre] ?? [];
                foreach ($pregs as $pData) {
                    $pregunta = Pregunta::updateOrCreate(
                        ['enunciado' => $pData['enunciado'], 'concepto_id' => $concepto->id],
                        [
                            'area_academica_id' => $area->id,
                            'concepto_id' => $concepto->id,
                            'tipo' => 'opcion_multiple',
                            'dificultad' => $pData['dificultad'] ?? 'media',
                            'activa' => true,
                        ]
                    );

                    foreach ($pData['alternativas'] as $j => $texto) {
                        Alternativa::updateOrCreate(
                            ['pregunta_id' => $pregunta->id, 'orden' => $j],
                            ['texto' => $texto, 'es_correcta' => $j === $pData['correcta']]
                        );
                    }
                    $total++;
                }

                $this->command->info("  ✅ {$areaNombre} → {$conceptoNombre}: " . count($pregs) . ' preguntas');
            }
        }

        $this->command->info("✅ Total creado: {$total} preguntas en 3 áreas");
    }

    private function bancoPreguntas(): array
    {
        $banco = [];

        $banco['Anatomía humana'] = [
            ['enunciado' => '¿Cuál es el hueso más largo del cuerpo humano?', 'alternativas' => ['Tibia', 'Fémur', 'Húmero', 'Radio', 'Peroné'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La unidad funcional del riñón es:', 'alternativas' => ['Nefrona', 'Neurona', 'Alvéolo', 'Glomérulo', 'Cápsula'], 'correcta' => 0, 'dificultad' => 'media'],
            ['enunciado' => '¿Cuántas cámaras tiene el corazón humano?', 'alternativas' => ['2', '3', '4', '5', '6'], 'correcta' => 2, 'dificultad' => 'facil'],
            ['enunciado' => 'El órgano encargado de filtrar la sangre y producir orina es:', 'alternativas' => ['Hígado', 'Pulmón', 'Riñón', 'Páncreas', 'Bazo'], 'correcta' => 2, 'dificultad' => 'facil'],
            ['enunciado' => 'La hormona que regula el nivel de glucosa en la sangre es:', 'alternativas' => ['Adrenalina', 'Insulina', 'Tiroxina', 'Estrógeno', 'Melatonina'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'El intercambio gaseoso en los pulmones ocurre en:', 'alternativas' => ['Bronquios', 'Tráquea', 'Alvéolos', 'Laringe', 'Faringe'], 'correcta' => 2, 'dificultad' => 'facil'],
        ];

        $banco['Biología celular'] = [
            ['enunciado' => 'La estructura celular encargada de la producción de energía (ATP) es:', 'alternativas' => ['Ribosoma', 'Mitocondria', 'Lisosoma', 'Aparato de Golgi', 'Retículo endoplasmático'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => '¿En qué orgánulo ocurre la síntesis de proteínas?', 'alternativas' => ['Núcleo', 'Mitocondria', 'Ribosoma', 'Vacuola', 'Membrana'], 'correcta' => 2, 'dificultad' => 'media'],
            ['enunciado' => 'El material genético de la célula se encuentra principalmente en:', 'alternativas' => ['Citoplasma', 'Núcleo', 'Ribosoma', 'Membrana celular', 'Vacuola'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'El proceso por el cual la célula captura sustancias formando una vesícula se llama:', 'alternativas' => ['Exocitosis', 'Endocitosis', 'Difusión', 'Ósmosis', 'Fagocitosis'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Las células procariotas se caracterizan por:', 'alternativas' => ['Tener núcleo definido', 'No tener núcleo definido', 'Tener cloroplastos', 'Ser multicelulares', 'No tener pared celular'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'La fotosíntesis ocurre en el orgánulo llamado:', 'alternativas' => ['Mitocondria', 'Cloroplasto', 'Núcleo', 'Lisosoma', 'Centriolo'], 'correcta' => 1, 'dificultad' => 'facil'],
        ];

        $banco['Química general'] = [
            ['enunciado' => 'La fórmula química del agua es:', 'alternativas' => ['CO2', 'H2O', 'O2', 'H2O2', 'NaCl'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'El número atómico del carbono es:', 'alternativas' => ['4', '6', '8', '12', '14'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'El pH de una solución neutra es:', 'alternativas' => ['0', '5', '7', '10', '14'], 'correcta' => 2, 'dificultad' => 'facil'],
            ['enunciado' => 'Un mol de cualquier sustancia contiene aproximadamente:', 'alternativas' => ['6.022 × 10²³ partículas', '3.14 × 10²³ partículas', '1 × 10²³ partículas', '2 × 10²⁴ partículas', '9 × 10²¹ partículas'], 'correcta' => 0, 'dificultad' => 'dificil'],
            ['enunciado' => 'El enlace químico que comparte electrones entre átomos se llama:', 'alternativas' => ['Iónico', 'Covalente', 'Metálico', 'Hidrógeno', 'Van der Waals'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'El símbolo químico del hierro es:', 'alternativas' => ['Fe', 'Ir', 'He', 'Hf', 'F'], 'correcta' => 0, 'dificultad' => 'facil'],
        ];

        $banco['Química orgánica'] = [
            ['enunciado' => 'La fórmula del metano es:', 'alternativas' => ['CH4', 'C2H6', 'C3H8', 'C2H4', 'CO2'], 'correcta' => 0, 'dificultad' => 'facil'],
            ['enunciado' => 'Los compuestos orgánicos se caracterizan por contener principalmente:', 'alternativas' => ['Oxígeno', 'Carbono', 'Nitrógeno', 'Azufre', 'Fósforo'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'El grupo funcional de los alcoholes es:', 'alternativas' => ['-COOH', '-OH', '-CHO', '-NH2', '-CO-'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'El benceno tiene la fórmula molecular:', 'alternativas' => ['C6H6', 'C6H12', 'C6H14', 'C5H10', 'C7H8'], 'correcta' => 0, 'dificultad' => 'media'],
            ['enunciado' => '¿Cuál de los siguientes es un hidrocarburo saturado?', 'alternativas' => ['Eteno', 'Etino', 'Etano', 'Benceno', 'Butadieno'], 'correcta' => 2, 'dificultad' => 'media'],
            ['enunciado' => 'La reacción de esterificación produce:', 'alternativas' => ['Ácido y agua', 'Éster y agua', 'Sal y agua', 'Alcohol y gas', 'Cetona y agua'], 'correcta' => 1, 'dificultad' => 'dificil'],
        ];

        $banco['Bioquímica'] = [
            ['enunciado' => 'El monómero de las proteínas es:', 'alternativas' => ['Glucosa', 'Nucleótido', 'Aminoácido', 'Ácido graso', 'Glicerol'], 'correcta' => 2, 'dificultad' => 'facil'],
            ['enunciado' => 'El monómero de los ácidos nucleicos es:', 'alternativas' => ['Aminoácido', 'Nucleótido', 'Monosacárido', 'Glicerol', 'Ácido graso'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'La enzima digestiva que degrada los almidones es:', 'alternativas' => ['Pepsina', 'Amilasa', 'Lipasa', 'Tripsina', 'Lactasa'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'El ATP almacena energía en sus enlaces:', 'alternativas' => ['Glucosídicos', 'Peptídicos', 'Fosfato', 'Hidrógeno', 'Éster'], 'correcta' => 2, 'dificultad' => 'media'],
            ['enunciado' => 'La vitamina que se obtiene principalmente del sol y regula el calcio es la:', 'alternativas' => ['Vitamina A', 'Vitamina C', 'Vitamina D', 'Vitamina E', 'Vitamina K'], 'correcta' => 2, 'dificultad' => 'facil'],
        ];

        $banco['Genética'] = [
            ['enunciado' => '¿Quién es considerado el padre de la genética?', 'alternativas' => ['Charles Darwin', 'Gregor Mendel', 'James Watson', 'Lamarck', 'Morgan'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La molécula que porta la información genética es:', 'alternativas' => ['ARN', 'ADN', 'Proteína', 'ATP', 'Glucosa'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Si una planta de genotipo Aa se cruza con otra Aa, la probabilidad de descendencia aa es:', 'alternativas' => ['0%', '25%', '50%', '75%', '100%'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Las bases nitrogenadas del ADN son:', 'alternativas' => ['Adenina, guanina, citosina, uracilo', 'Adenina, guanina, citosina, timina', 'Adenina, uracilo, citosina, timina', 'Guanina, citosina, uracilo, timina', 'Adenina, guanina, uracilo, timina'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'El genoma humano contiene aproximadamente:', 'alternativas' => ['10 mil genes', '20 mil genes', '100 mil genes', '500 mil genes', '1 millón de genes'], 'correcta' => 1, 'dificultad' => 'dificil'],
            ['enunciado' => 'Un fenotipo es:', 'alternativas' => ['La información genética de un individuo', 'Las características observables de un organismo', 'El tipo de células de un organismo', 'La secuencia de ADN', 'El número de cromosomas'], 'correcta' => 1, 'dificultad' => 'facil'],
        ];

        $banco['Ecología y medio ambiente'] = [
            ['enunciado' => 'El conjunto de seres vivos que habitan en una misma zona se llama:', 'alternativas' => ['Ecosistema', 'Biocenosis', 'Biotopo', 'Hábitat', 'Nicho'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'El nivel más alto de organización ecológica es:', 'alternativas' => ['Población', 'Comunidad', 'Ecosistema', 'Biosfera', 'Individuo'], 'correcta' => 3, 'dificultad' => 'media'],
            ['enunciado' => 'El fenómeno por el cual los gases de efecto invernadero retienen el calor se llama:', 'alternativas' => ['Lluvia ácida', 'Efecto invernadero', 'Smog', 'Erosión', 'Deforestación'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Un organismo que produce su propio alimento se llama:', 'alternativas' => ['Consumidor', 'Descomponedor', 'Productor', 'Depredador', 'Carroñero'], 'correcta' => 2, 'dificultad' => 'facil'],
            ['enunciado' => 'El reciclaje del papel contribuye a:', 'alternativas' => ['Aumentar la deforestación', 'Reducir la tala de árboles', 'Generar más basura', 'Contaminar el agua', 'Aumentar el CO2'], 'correcta' => 1, 'dificultad' => 'facil'],
        ];

        $banco['Microbiología'] = [
            ['enunciado' => 'Las bacterias pertenecen al reino:', 'alternativas' => ['Protista', 'Monera', 'Fungi', 'Animalia', 'Plantae'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Los virus se caracterizan por:', 'alternativas' => ['Ser células completas', 'Necesitar una célula huésped para reproducirse', 'Ser visibles a simple vista', 'Tener metabolismo propio', 'Poseer ADN y ARN siempre'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'El antibiótico penicilina fue descubierto por:', 'alternativas' => ['Louis Pasteur', 'Alexander Fleming', 'Robert Koch', 'Edward Jenner', 'Antonie van Leeuwenhoek'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Las bacterias patógenas son aquellas que:', 'alternativas' => ['Producen antibióticos', 'Causan enfermedades', 'Viven en el intestino', 'Son inofensivas', 'Producen alimentos'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La técnica de tinción más usada para clasificar bacterias es:', 'alternativas' => ['Gram', 'Giemsa', 'Ziehl-Neelsen', 'Wright', 'Hematoxilina'], 'correcta' => 0, 'dificultad' => 'dificil'],
        ];

        $banco['Salud pública'] = [
            ['enunciado' => 'Las vacunas funcionan generando:', 'alternativas' => ['Antibióticos', 'Inmunidad frente a enfermedades', 'Vitaminas', 'Anticuerpos temporales', 'Resistencia a fármacos'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La OMS es el organismo de la ONU encargado de:', 'alternativas' => ['La agricultura', 'La salud mundial', 'La educación', 'El comercio', 'La energía'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La desnutrición infantil se combate principalmente con:', 'alternativas' => ['Más hospitales', 'Alimentación adecuada y acceso a servicios de salud', 'Vacunas gratuitas', 'Medicamentos', 'Reformas de ley'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'El lavado de manos previene principalmente:', 'alternativas' => ['Enfermedades respiratorias', 'Enfermedades diarreicas y respiratorias', 'Cáncer', 'Enfermedades cardiovasculares', 'Diabetes'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La vigilancia epidemiológica permite:', 'alternativas' => ['Curar todas las enfermedades', 'Detectar y monitorear brotes de enfermedades', 'Eliminar los virus', 'Reemplazar a los médicos', 'Fabricar medicamentos'], 'correcta' => 1, 'dificultad' => 'media'],
        ];

        $banco['Razonamiento matemático'] = [
            ['enunciado' => 'Si 3 obreros hacen 30 metros de obra en 5 días, ¿cuántos metros harán 6 obreros en 10 días?', 'alternativas' => ['60', '90', '120', '150', '180'], 'correcta' => 2, 'dificultad' => 'media'],
            ['enunciado' => 'Un número aumentado en su 25% es 50. ¿Cuál es el número?', 'alternativas' => ['35', '40', '45', '37.5', '42'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'En una fiesta hay 30 personas y cada una saluda a todas las demás con un apretón de manos. ¿Cuántos apretones hay?', 'alternativas' => ['435', '450', '420', '465', '480'], 'correcta' => 0, 'dificultad' => 'dificil'],
            ['enunciado' => '¿Qué número continúa la serie: 1, 1, 2, 6, 24, __?', 'alternativas' => ['48', '96', '120', '144', '240'], 'correcta' => 2, 'dificultad' => 'media'],
            ['enunciado' => 'Si el lápiz cuesta S/2 más que la goma y juntos cuestan S/8, ¿cuánto cuesta la goma?', 'alternativas' => ['S/2', 'S/3', 'S/4', 'S/5', 'S/6'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'El 30% de 400 es:', 'alternativas' => ['100', '120', '130', '140', '150'], 'correcta' => 1, 'dificultad' => 'facil'],
        ];

        $banco['Razonamiento verbal'] = [
            ['enunciado' => 'Elija el sinónimo de "idóneo":', 'alternativas' => ['Inútil', 'Adecuado', 'Imperfecto', 'Defectuoso', 'Pésimo'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Elija el antónimo de "efímero":', 'alternativas' => ['Pasajero', 'Duradero', 'Breve', 'Fugaz', 'Transitorio'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Complete la analogía: libro es a leer como pincel es a:', 'alternativas' => ['Dibujar', 'Pintar', 'Escribir', 'Diseñar', 'Colorear'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La palabra "óptimo" significa:', 'alternativas' => ['Peor posible', 'El mejor o muy bueno', 'Regular', 'Malísimo', 'Incierto'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La oración con correcta puntuación es:', 'alternativas' => ['Hola como estás', '¿Hola, cómo estás?', 'Hola como estas', '¿Hola cómo estás?', 'Hola, ¿cómo estás?'], 'correcta' => 4, 'dificultad' => 'dificil'],
        ];

        $banco['Matemática financiera'] = [
            ['enunciado' => 'Si depositas S/1000 a una tasa anual del 5% durante 2 años (interés simple), el interés es:', 'alternativas' => ['S/50', 'S/100', 'S/150', 'S/200', 'S/250'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Un préstamo de S/2000 al 10% mensual genera en un mes un interés de:', 'alternativas' => ['S/100', 'S/150', 'S/200', 'S/250', 'S/300'], 'correcta' => 2, 'dificultad' => 'facil'],
            ['enunciado' => 'El interés compuesto se diferencia del simple porque:', 'alternativas' => ['Es menor siempre', 'Calcula intereses sobre intereses', 'No genera ganancias', 'Solo se usa en bancos', 'No existe'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Un artículo de S/250 tiene 20% de descuento. El precio final es:', 'alternativas' => ['S/180', 'S/190', 'S/200', 'S/210', 'S/220'], 'correcta' => 2, 'dificultad' => 'facil'],
            ['enunciado' => 'La tasa de interés que considera capitalización periódica se llama:', 'alternativas' => ['Tasa simple', 'Tasa efectiva', 'Tasa nominal', 'Tasa fija', 'Tasa real'], 'correcta' => 2, 'dificultad' => 'dificil'],
        ];

        $banco['Contabilidad básica'] = [
            ['enunciado' => 'La ecuación contable fundamental es:', 'alternativas' => ['Activo = Pasivo + Patrimonio', 'Activo = Pasivo - Patrimonio', 'Pasivo = Activo + Patrimonio', 'Patrimonio = Pasivo + Activo', 'Activo = Ingresos + Gastos'], 'correcta' => 0, 'dificultad' => 'facil'],
            ['enunciado' => 'El libro contable obligatorio para registrar todas las transacciones es:', 'alternativas' => ['Libro de actas', 'Diario', 'Memoria', 'Registro de accionistas', 'Libro de aportes'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Un activo es:', 'alternativas' => ['Una deuda de la empresa', 'Un bien o derecho de la empresa', 'Un gasto', 'Un impuesto', 'Una obligación'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'El documento que comprueba una venta al contado es:', 'alternativas' => ['Boleta de venta', 'Contrato', 'Carta de crédito', 'Acta', 'Comprobante de préstamo'], 'correcta' => 0, 'dificultad' => 'facil'],
            ['enunciado' => 'El IGV en Perú es un impuesto:', 'alternativas' => ['A la renta', 'A las ventas', 'A la propiedad', 'A las importaciones solamente', 'A los salarios'], 'correcta' => 1, 'dificultad' => 'media'],
        ];

        $banco['Economía'] = [
            ['enunciado' => 'La ley de la demanda establece que al subir el precio:', 'alternativas' => ['La cantidad demandada sube', 'La cantidad demandada baja', 'La oferta sube', 'Nada cambia', 'La demanda es infinita'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La inflación es:', 'alternativas' => ['La baja general de precios', 'El aumento sostenido del nivel de precios', 'El aumento del empleo', 'La reducción de la producción', 'El aumento de las exportaciones'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'El PBI mide:', 'alternativas' => ['La inflación', 'La producción total de bienes y servicios de un país', 'El desempleo', 'Las reservas internacionales', 'La deuda externa'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'El Banco Central de Reserva del Perú se encarga de:', 'alternativas' => ['Recaudar impuestos', 'Controlar la emisión monetaria y la inflación', 'Financiar empresas', 'Pagar sueldos públicos', 'Regular el comercio exterior'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'La escasez es un concepto económico que significa:', 'alternativas' => ['Que hay demasiados recursos', 'Que los recursos son limitados frente a necesidades ilimitadas', 'Que no hay dinero', 'Que no existen bienes', 'Que la producción es nula'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'La devaluación de la moneda implica:', 'alternativas' => ['Que la moneda vale más', 'Que la moneda pierde valor frente a otras', 'Que los precios bajan', 'Que aumentan las importaciones baratas', 'Que no hay efecto'], 'correcta' => 1, 'dificultad' => 'media'],
        ];

        $banco['Administración y gestión'] = [
            ['enunciado' => 'Las funciones de la administración son:', 'alternativas' => ['Comprar, vender, cobrar, pagar', 'Planear, organizar, dirigir y controlar', 'Producir, distribuir y vender', 'Contratar y despedir', 'Innovar y exportar'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'El padre de la administración científica es:', 'alternativas' => ['Henry Fayol', 'Frederick Taylor', 'Max Weber', 'Peter Drucker', 'Abraham Maslow'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'La misión de una empresa responde a:', 'alternativas' => ['¿Cuánto gana?', '¿Para qué existe y cuál es su propósito?', '¿Quiénes son sus clientes?', '¿Dónde está ubicada?', '¿Cuántos empleados tiene?'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Una estructura organizacional vertical se caracteriza por:', 'alternativas' => ['Tener pocos niveles jerárquicos', 'Tener muchos niveles de mando', 'No tener jefes', 'Ser plana', 'No existir'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'El liderazgo transformacional busca:', 'alternativas' => ['Mantener el statu quo', 'Inspirar y motivar cambios positivos', 'Controlar estrictamente', 'Evitar riesgos', 'Centralizar decisiones'], 'correcta' => 1, 'dificultad' => 'media'],
        ];

        $banco['Finanzas'] = [
            ['enunciado' => 'El valor del dinero en el tiempo indica que:', 'alternativas' => ['S/100 hoy vale igual que en 10 años', 'S/100 hoy vale más que en el futuro', 'S/100 hoy vale menos que en el futuro', 'El dinero no cambia', 'Solo afecta a las empresas'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Un bono es:', 'alternativas' => ['Una acción de la empresa', 'Un título de deuda que paga intereses', 'Un préstamo bancario', 'Un seguro', 'Una cuenta de ahorro'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'El VAN positivo indica que un proyecto:', 'alternativas' => ['Genera pérdidas', 'Es rentable', 'Es indiferente', 'Debe rechazarse', 'No se puede evaluar'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'El riesgo financiero se refiere a:', 'alternativas' => ['La certeza total de resultados', 'La posibilidad de pérdida o rendimiento incierto', 'La ausencia de deudas', 'Los impuestos', 'La inflación'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Diversificar una inversión significa:', 'alternativas' => ['Invertir todo en un solo activo', 'Distribuir el capital en varios activos para reducir riesgo', 'Gastar más', 'Evitar invertir', 'Guardar el dinero en casa'], 'correcta' => 1, 'dificultad' => 'facil'],
        ];

        $banco['Marketing'] = [
            ['enunciado' => 'Las "4P" del marketing son:', 'alternativas' => ['Producto, precio, plaza y promoción', 'Precio, publicidad, persona y plaza', 'Producto, precio, producción y promoción', 'Precio, plaza, promoción y plan', 'Producto, plan, precio y persona'], 'correcta' => 0, 'dificultad' => 'facil'],
            ['enunciado' => 'El marketing mix se conoce también como:', 'alternativas' => ['Estrategia de ventas', 'Las 4P', 'El plan de negocios', 'La investigación de mercado', 'El presupuesto'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La segmentación de mercado consiste en:', 'alternativas' => ['Vender a todos por igual', 'Dividir el mercado en grupos con características similares', 'Bajar precios', 'Aumentar publicidad', 'Eliminar competidores'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Una marca fuerte genera principalmente:', 'alternativas' => ['Costos más altos', 'Lealtad y diferenciación frente a la competencia', 'Menos clientes', 'Menos ingresos', 'Más quejas'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'La investigación de mercado sirve para:', 'alternativas' => ['Adivinar el futuro', 'Conocer las necesidades y comportamiento del consumidor', 'Publicar anuncios', 'Aumentar precios', 'Reducir producción'], 'correcta' => 1, 'dificultad' => 'facil'],
        ];

        $banco['Estadística'] = [
            ['enunciado' => 'La media aritmética de 4, 8, 12 es:', 'alternativas' => ['6', '8', '10', '12', '14'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La mediana del conjunto {3, 7, 9, 15, 21} es:', 'alternativas' => ['7', '9', '15', '3', '21'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La moda de {2, 3, 3, 5, 7} es:', 'alternativas' => ['2', '3', '5', '7', '4'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La desviación estándar mide:', 'alternativas' => ['La tendencia central', 'La dispersión de los datos respecto a la media', 'El número de datos', 'La suma de los datos', 'La probabilidad'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'La probabilidad de obtener cara al lanzar una moneda es:', 'alternativas' => ['0.25', '0.5', '0.75', '1', '0'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Una muestra estadística es:', 'alternativas' => ['Toda la población', 'Una parte representativa de la población', 'El promedio de datos', 'La moda', 'Un gráfico'], 'correcta' => 1, 'dificultad' => 'facil'],
        ];

        $banco['Historia del Perú'] = [
            ['enunciado' => 'La cultura Moche se desarrolló en la costa:', 'alternativas' => ['Sur del Perú', 'Norte del Perú', 'Central del Perú', 'Amazonía', 'Sierra sur'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'La independencia del Perú fue proclamada el:', 'alternativas' => ['28 de julio de 1821', '9 de diciembre de 1824', '28 de julio de 1810', '15 de julio de 1821', '20 de enero de 1820'], 'correcta' => 0, 'dificultad' => 'facil'],
            ['enunciado' => 'El inca Pachacútec es conocido por:', 'alternativas' => ['Fundar Cusco', 'Expandir el Tahuantinsuyo y construir Machu Picchu', 'Ser el último inca', 'Conquistar el imperio español', 'Dividir el imperio'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'La Guerra del Pacífico (1879-1883) enfrentó al Perú con:', 'alternativas' => ['Argentina', 'Chile', 'Colombia', 'Ecuador', 'Bolivia solamente'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'La Constitución vigente del Perú fue promulgada en:', 'alternativas' => ['1979', '1993', '2001', '2011', '1985'], 'correcta' => 1, 'dificultad' => 'media'],
        ];

        $banco['Historia universal'] = [
            ['enunciado' => 'La Revolución Francesa comenzó en el año:', 'alternativas' => ['1776', '1789', '1804', '1815', '1750'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'La Primera Guerra Mundial terminó en:', 'alternativas' => ['1914', '1918', '1922', '1929', '1939'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'El muro de Berlín cayó en:', 'alternativas' => ['1985', '1989', '1991', '1993', '1975'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'El Renacimiento tuvo su origen en:', 'alternativas' => ['Francia', 'Italia', 'Inglaterra', 'Alemania', 'España'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'La Segunda Guerra Mundial inició con la invasión de:', 'alternativas' => ['Francia', 'Polonia', 'URSS', 'Gran Bretaña', 'Japón'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'La civilización egipcia se desarrolló a orillas del río:', 'alternativas' => ['Éufrates', 'Nilo', 'Tigris', 'Ganges', 'Danubio'], 'correcta' => 1, 'dificultad' => 'facil'],
        ];

        $banco['Geografía'] = [
            ['enunciado' => 'El departamento más extenso del Perú es:', 'alternativas' => ['Loreto', 'Ucayali', 'Cusco', 'Puno', 'Arequipa'], 'correcta' => 0, 'dificultad' => 'media'],
            ['enunciado' => 'El cañón más profundo del mundo se encuentra en:', 'alternativas' => ['Cusco', 'Arequipa (Colca)', 'Áncash', 'Cajamarca', 'Huánuco'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'El lago Titicaca se encuentra entre Perú y:', 'alternativas' => ['Chile', 'Bolivia', 'Ecuador', 'Colombia', 'Brasil'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La cordillera de los Andes atraviesa el Perú de:', 'alternativas' => ['Norte a sur', 'Este a oeste', 'Solo el sur', 'Solo el norte', 'Diagonalmente'], 'correcta' => 0, 'dificultad' => 'facil'],
            ['enunciado' => 'El río Amazonas nace en territorio:', 'alternativas' => ['Brasileño', 'Peruano', 'Colombiano', 'Ecuatoriano', 'Boliviano'], 'correcta' => 1, 'dificultad' => 'media'],
        ];

        $banco['Formación cívica'] = [
            ['enunciado' => 'El órgano del Estado encargado de hacer las leyes es:', 'alternativas' => ['El Ejecutivo', 'El Congreso', 'El Poder Judicial', 'La Contraloría', 'El Jurado Nacional'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La Declaración Universal de los Derechos Humanos fue proclamada en:', 'alternativas' => ['1918', '1945', '1948', '1955', '1960'], 'correcta' => 2, 'dificultad' => 'media'],
            ['enunciado' => 'La forma de gobierno del Perú es:', 'alternativas' => ['Presidencialista', 'Monárquica', 'Parlamentaria', 'Federal', 'Teocrática'], 'correcta' => 0, 'dificultad' => 'facil'],
            ['enunciado' => 'Votar en las elecciones es un:', 'alternativas' => ['Obligatorio para todos los mayores de 18', 'Derecho y deber ciudadano', 'Solo un derecho', 'Solo para profesionales', 'Opcional'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'La institución encargada de organizar los procesos electorales en el Perú es:', 'alternativas' => ['La SUNAT', 'La ONPE', 'La RENIEC', 'La PCM', 'El INEI'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'La corrupción pública se combate principalmente con:', 'alternativas' => ['Transparencia y rendición de cuentas', 'Más impuestos', 'Censura', 'Menos elecciones', 'Privacidad total'], 'correcta' => 0, 'dificultad' => 'media'],
        ];

        $banco['Literatura'] = [
            ['enunciado' => 'La obra "Cien años de soledad" fue escrita por:', 'alternativas' => ['Mario Vargas Llosa', 'Gabriel García Márquez', 'Julio Cortázar', 'Pablo Neruda', 'Jorge Luis Borges'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => '"El Quijote" fue escrito por:', 'alternativas' => ['Lope de Vega', 'Miguel de Cervantes', 'Francisco de Quevedo', 'Garcilaso de la Vega', 'Calderón de la Barca'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'El realismo mágico es un movimiento literario característico de:', 'alternativas' => ['Europa', 'América Latina', 'Asia', 'África', 'Oceanía'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Los versos de arte menor son aquellos que tienen:', 'alternativas' => ['Más de 8 sílabas', '8 sílabas o menos', '10 sílabas', '11 sílabas', '14 sílabas'], 'correcta' => 1, 'dificultad' => 'dificil'],
            ['enunciado' => 'La poesía épica narra:', 'alternativas' => ['Sentimientos del autor', 'Hazañas de héroes y pueblos', 'Historias de amor', 'Cuentos infantiles', 'Refranes'], 'correcta' => 1, 'dificultad' => 'media'],
        ];

        $banco['Lenguaje y comunicación'] = [
            ['enunciado' => 'La palabra "árbol" es aguda, grave o esdrújula?', 'alternativas' => ['Aguda', 'Grave', 'Esdrújula', 'Sobreesdrújula', 'Ninguna'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'El enunciado "¡Qué lindo día!" es una oración:', 'alternativas' => ['Enunciativa', 'Exclamativa', 'Interrogativa', 'Dubitativa', 'Desiderativa'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'La comunicación no verbal incluye:', 'alternativas' => ['Las palabras', 'Los gestos y expresiones', 'Los textos', 'Las cartas', 'Los correos'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'El "emisor" en la comunicación es:', 'alternativas' => ['Quien recibe el mensaje', 'Quien produce el mensaje', 'El canal', 'El código', 'El contexto'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La palabra correctamente escrita es:', 'alternativas' => ['Exámen', 'Examen', 'Exámenes es plural', 'Examenes', 'Exámene'], 'correcta' => 1, 'dificultad' => 'facil'],
        ];

        $banco['Sociología'] = [
            ['enunciado' => 'El padre de la sociología es:', 'alternativas' => ['Karl Marx', 'Auguste Comte', 'Émile Durkheim', 'Max Weber', 'Herbert Spencer'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'La socialización primaria ocurre principalmente:', 'alternativas' => ['En la universidad', 'En la familia', 'En el trabajo', 'En el Estado', 'En los medios'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La estratificación social se refiere a:', 'alternativas' => ['La eliminación de clases', 'La jerarquización de grupos sociales', 'La igualdad total', 'La migración', 'La urbanización'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'La anomia es un concepto de Durkheim que significa:', 'alternativas' => ['Orden social', 'Ausencia de normas y desorientación social', 'Solidaridad', 'Cohesión', 'Progreso'], 'correcta' => 1, 'dificultad' => 'dificil'],
            ['enunciado' => 'La cultura se transmite principalmente mediante:', 'alternativas' => ['La herencia biológica', 'El aprendizaje social', 'Los genes', 'El azar', 'La genética'], 'correcta' => 1, 'dificultad' => 'facil'],
        ];

        $banco['Filosofía'] = [
            ['enunciado' => 'Sócrates es conocido por:', 'alternativas' => ['Crear el empirismo', 'La mayéutica y el conocimiento del alma', 'Fundar el estoicismo', 'Escribir la República', 'El escepticismo'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'El autor de "La República" es:', 'alternativas' => ['Aristóteles', 'Platón', 'Sócrates', 'Descartes', 'Kant'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => '"Pienso, luego existo" es la frase de:', 'alternativas' => ['Platón', 'Descartes', 'Nietzsche', 'Hume', 'Sartre'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La ética estudia:', 'alternativas' => ['El cosmos', 'Los valores morales y el comportamiento humano', 'Las leyes físicas', 'El lenguaje', 'El arte'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Aristóteles fue discípulo de:', 'alternativas' => ['Sócrates', 'Platón', 'Heráclito', 'Demócrito', 'Epicuro'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'El existencialismo sostiene que:', 'alternativas' => ['La esencia precede a la existencia', 'La existencia precede a la esencia', 'Todo está predestinado', 'No existe la libertad', 'Solo existe la materia'], 'correcta' => 1, 'dificultad' => 'dificil'],
        ];

        $banco['Derecho constitucional'] = [
            ['enunciado' => 'La Constitución es:', 'alternativas' => ['Una ley cualquiera', 'La norma suprema que organiza el Estado y garantiza derechos', 'Un decreto', 'Un tratado internacional', 'Un reglamento interno'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'El habeas corpus protege:', 'alternativas' => ['La propiedad', 'La libertad personal', 'El medio ambiente', 'Los datos personales', 'La salud'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'El Tribunal Constitucional del Perú garantiza:', 'alternativas' => ['La ejecución de leyes', 'La supremacía constitucional y derechos fundamentales', 'La recaudación', 'Las elecciones', 'Los impuestos'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Los derechos fundamentales se clasifican en:', 'alternativas' => ['Civiles, políticos, económicos, sociales y culturales', 'Solo civiles', 'Solo económicos', 'Solo penales', 'Solo tributarios'], 'correcta' => 0, 'dificultad' => 'media'],
            ['enunciado' => 'El amparo protege derechos distintos a:', 'alternativas' => ['La libertad', 'La libertad personal', 'El trabajo', 'La educación', 'La salud'], 'correcta' => 1, 'dificultad' => 'dificil'],
        ];

        return $banco;
    }
}
