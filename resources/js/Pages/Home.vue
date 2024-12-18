<template>
  <v-app>
    <v-main>
      <!-- Tarjeta de búsqueda -->
      <v-container :class="{'d-flex justify-center align-center': true, 'fill-height': !Object.keys(results).length}">
        <v-card class="pa-5" elevation="3" max-width="600">
          <v-card-title color="primary" class="text-2xl font-bold mb-4 text-center">Consultar IMEIs</v-card-title>
          <v-form @submit.prevent="submit" class="d-flex flex-column align-center">
            <v-text-field
              v-model="imeis"
              label="IMEIs (separados por comas)"
              class="mb-4 mx-auto"
              width="100%"
              type="text"
              color="deep-purple-accent-4"
              :error-messages="errors.imeis"
              @keypress.enter.prevent="replaceEnterWithComma"
              @input="cleanInput"
              required
            ></v-text-field>
            <v-btn type="submit" color="deep-purple-accent-4" width="50vh" class="mt-4">Consultar</v-btn>
          </v-form>
        </v-card>
      </v-container>
  
      <!-- Contenedor de resultados -->
      <v-container fluid class="mt-4 results-container">
        <v-row>
          <v-col
            v-for="(fileResults, fileName) in results"
            :key="fileName"
            cols="12"
            sm="6"
            md="4"
            lg="3"
          >
            <v-card :class="{'not-found': fileName === 'N/A'}" class="mb-4 result-card" :data-card="fileName">
              <v-card-title class="headline">Archivo: {{ fileName }}</v-card-title>
              <v-card-text>
                <div v-if="fileResults.length">
                  <strong>IMEIs:</strong> {{ fileResults.map(result => result.imei).join(', ') }}
                </div>
                <v-btn
                  v-if="fileName !== 'N/A'"
                  @click="highlightImei(fileResults)"
                  :color="loadingFile === fileName ? 'primary' : successFile === fileName ? 'green' : 'orange-accent-4'"
                  class="mt-2 action-btn"
                  width="100%"
                  :disabled="loadingFile === fileName"
                >
                  <v-progress-circular
                    v-if="loadingFile === fileName"
                    indeterminate
                    size="20"
                    width="2"
                    color="white"
                    class="mr-2"
                  ></v-progress-circular>
                  {{ loadingFile === fileName ? 'Procesando...' : successFile === fileName ? '¡Completado!' : 'Resaltar IMEIs y Descargar PDF' }}
                </v-btn>
  
                <div class="justify-print">
                  <div v-if="fileName !== 'N/A'" class="qr-code-container small-qr">
                    <canvas :id="`qrcode-${fileName}`"></canvas>
                    <v-btn icon @click="printCard(fileName)" class="print-btn action-btn">
                      <v-icon size="x-large">mdi-printer</v-icon>
                    </v-btn>
                  </div>
                  <div v-if="fileName !== 'N/A'" class="observations">
                    <span class="observation">Escanee para descargar sus manifiestos resaltados</span>
                  </div>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
    <!-- Botón flotante para imprimir/descargar PDF -->
    <v-btn v-if="results && Object.keys(results).length"
      fab
      bottom
      right
      color="grey-darken-4" 
      @click="printAllResults"
      class="floating-button"
    >
    <v-icon size="x-large">mdi-printer</v-icon>
    </v-btn>

    <!-- Navegación inferior con botones para ir a la página de consulta y la de subida de archivos -->
    <v-bottom-navigation v-model="value" :bg-color="color" mode="shift" class="nav-bar">
      <v-btn text @click="navigateTo('home')">
        <v-icon>mdi-barcode-scan</v-icon>
        <span>Consulta</span>
      </v-btn>
      <v-btn text @click="navigateTo('upload')">
        <v-icon>mdi-file-upload</v-icon>
        <span>Archivos</span>
      </v-btn>
    </v-bottom-navigation>
  </v-app>
  
</template>

<script>
import { ref, nextTick, onMounted } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import { usePage } from '@inertiajs/inertia-vue3';
import '@mdi/font/css/materialdesignicons.css'; // Asegurarse de usar css-loader
import moment from 'moment-timezone';
import { PDFDocument, rgb } from 'pdf-lib';
import * as pdfjsLib from 'pdfjs-dist';
import QRCode from 'qrcode';
import html2pdf from 'html2pdf.js';
pdfjsLib.GlobalWorkerOptions.workerSrc = `https://unpkg.com/pdfjs-dist@${pdfjsLib.version}/build/pdf.worker.min.mjs`;

export default {
  setup() {
    const { props } = usePage();
    const imeis = ref(''); // Variable reactiva para almacenar los IMEIs ingresados
    const results = ref(props.results || {}); // Variable reactiva para almacenar los resultados de la consulta
    const errors = ref(props.errors || {});
    const loadingFile = ref(null); // Variable reactiva para el archivo que se está procesando
    const successFile = ref(null); // Variable reactiva para el archivo que se ha procesado con éxito

    // Función para formatear la fecha
    const formatDate = (date) => {
      if (date === 'N/A') return 'N/A';
      return moment(date).tz('America/Bogota').format('YYYY-MM-DD HH:mm:ss');
    };

    // Función para enviar el formulario de consulta
    const submit = () => {
      errors.value = {}; // Limpiar errores anteriores
      imeis.value = imeis.value.replace(/,$/, ''); // Elimina la última coma si existe
      Inertia.post('/check-imei', { imeis: imeis.value }, {
        onError: (pageErrors) => {
          errors.value = pageErrors; // Mostrar errores de validación
        },
        onSuccess: (page) => {
          const rawResults = page.props.results;
          const sortedResults = {}; // Crear un nuevo objeto para los resultados ordenados
          
          // Primero agregar los resultados que no sean N/A
          Object.keys(rawResults).forEach(fileName => {
            if (fileName !== 'N/A') {
              sortedResults[fileName] = rawResults[fileName];
            }
          });

          // Agregar los resultados N/A al final
          if (rawResults['N/A']) {
            sortedResults['N/A'] = rawResults['N/A'];
          }

          results.value = sortedResults; // Asignar los resultados ordenados

          nextTick(generateQRCodes); // Generar los códigos QR después de que el DOM se actualice
        },
      });
    };

    // Función para navegar entre páginas
    const navigateTo = (route) => {
      Inertia.visit(route === 'home' ? '/' : `/${route}`);
    };

    // Función para compartir el IMEI por WhatsApp
    const shareWhatsApp = (result) => {
      const url = `https://wa.me/?text=Consulta%20el%20IMEI%20aquí:%20${result.pdf_url}`;
      window.open(url, '_blank');
    };

    // Función para compartir el IMEI por Email
    const shareEmail = (result) => {
      const subject = 'Consulta de IMEI';
      const body = `Consulta el IMEI aquí: ${result.pdf_url}`;
      const url = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
      window.open(url, '_blank');
    };

    // Función para resaltar los IMEIs en el PDF y descargarlo
    const highlightImei = async (fileResults) => {
      const searchTexts = fileResults.map(result => result.imei); // Obtiene los IMEIs a buscar
      const pdfName = fileResults[0].name_pdf;

      loadingFile.value = pdfName; // Indicar que el archivo se está procesando
      successFile.value = null;

      const url = `/storage/uploads/${pdfName}`; // URL del archivo PDF a resaltar
      const existingPdfBytes = await fetch(url).then(res => res.arrayBuffer()); // Descarga el PDF como arrayBuffer

      const pdfDoc = await PDFDocument.load(existingPdfBytes); // Carga el PDF en pdf-lib
      const pages = pdfDoc.getPages(); // Obtiene las páginas del PDF
      // const pageIndicesToKeep = new Set([0]); // Mantener siempre la página 1 (índice 0)
      const pageIndicesToKeep = new Set(); // Iniciar sin ninguna página incluida por defecto

      // Usar pdfjs-dist para obtener posiciones del texto
      const loadingTask = pdfjsLib.getDocument(url);
      const pdf = await loadingTask.promise;

      let currentSectionPages = []; // Páginas de la sección actual con "Privada"
      let hasImeisInCurrentSection = false; // Verificar si hay IMEIs resaltados en la sección actual

      for (let pageIndex = 0; pageIndex < pdf.numPages; pageIndex++) {
        const page = await pdf.getPage(pageIndex + 1); // Obtiene la página actual
        const textContent = await page.getTextContent(); // Obtiene el contenido del texto de la página
        const { width, height } = pages[pageIndex].getSize(); // Obtiene el tamaño de la página
        const pageText = textContent.items.map(item => item.str).join('');

        // Variables para procesar IMEIs en la página actual
	    	let hasHighlightedImeis = false;

        // Variables para combinar líneas divididas en múltiples ítems
        let combinedLine = '';
        let lineItems = [];
        let combinedLines = [];

        // Itera sobre los ítems de texto en la página
        textContent.items.forEach((item, index) => {
          combinedLine += item.str.replace(/\n+/g, ''); // Combina texto eliminando saltos de línea
          lineItems.push(item);

          // Verifica si el siguiente ítem está en una nueva línea
          if (index + 1 < textContent.items.length && textContent.items[index + 1].transform[5] !== item.transform[5]) {
            combinedLines.push({ text: combinedLine, items: lineItems }); // Agrega la línea combinada a la lista
            combinedLine = ''; // Resetea la línea combinada
            lineItems = []; // Resetea los ítems de la línea
          }
        });

        // Agrega la última línea combinada
        combinedLines.push({ text: combinedLine, items: lineItems });
        // Procesar los IMEIs en las líneas combinadas
        searchTexts.forEach((searchText) => {
          const pattern = /\b\d{15}\b/g; // Patrón para encontrar números de 15 dígitos (IMEI)
          let imeiFound = false;

          for (let i = 0; i < combinedLines.length; i++) {
            let line = combinedLines[i];
            let nextLine = combinedLines[i + 1] ? combinedLines[i + 1].text : '';

            // Verificar si el IMEI está dividido entre dos líneas
            for (let j = 1; j < searchText.length; j++) {
              if (line.text.endsWith(searchText.substring(0, j)) && nextLine.startsWith(searchText.substring(j))) {
                line.text += nextLine; // Combina la línea actual con la siguiente
                imeiFound = true;
                hasHighlightedImeis = true;
                // Páginas sin "Privada"
                if (!pageText.includes("Privada") && hasHighlightedImeis) {
                  console.log(`Página ${pageIndex} contiene IMEIs resaltados. Conservando.`);
                  hasImeisInCurrentSection = true; // La sección tiene IMEIs resaltados
                  pageIndicesToKeep.add(pageIndex);
                }

                // Resaltar primera parte del IMEI en la línea actual
                let accumulatedLength = line.text.length - nextLine.length;
                let remainingLength = accumulatedLength;

                for (const item of line.items) {
                  const itemText = item.str.replace(/\n+/g, '');
                  if (remainingLength <= itemText.length) {
                    const matchWidth = calculateTextWidth(searchText.substring(0, j), item.transform[0]);
                    const matchHeight = item.transform[0];
                    const precedingTextWidth = calculateTextWidth(itemText.substring(0, remainingLength), item.transform[0]);
                    const x = item.transform[4] + precedingTextWidth - matchWidth;
                    const y = height - item.transform[5] + 1;

                    pages[pageIndex].drawRectangle({
                      x,
                      y: height - y,
                      width: matchWidth,
                      height: matchHeight,
                      color: rgb(1, 1, 0), // verde(0,1,0)
                      opacity: 0.5,
                    });
                    break;
                  }
                  remainingLength -= itemText.length;
                }

                // Resaltar segunda parte del IMEI en la siguiente línea
                accumulatedLength = 0;
                remainingLength = searchText.length - j;

                for (const item of combinedLines[i + 1].items) {
                  const itemText = item.str.replace(/\n+/g, '');
                  if (accumulatedLength <= itemText.length) {
                    const x = item.transform[4];
                    const y = height - item.transform[5] + 1;
                    const matchWidth = calculateTextWidth(searchText.substring(j), item.transform[0]);
                    const matchHeight = item.transform[0];

                    pages[pageIndex].drawRectangle({
                      x,
                      y: height - y,
                      width: matchWidth,
                      height: matchHeight,
                      color: rgb(1, 1, 0), // amarillo
                      opacity: 0.5,
                    });
                    break;
                  }
                  accumulatedLength -= itemText.length;
                }

                // Ajustar `i` para continuar en la siguiente línea después de procesar un IMEI dividido
                i++;
                break;
              }
            }

            if (!imeiFound) {
              // Resaltar IMEIs completos en una sola línea
              let match;
              while ((match = pattern.exec(line.text)) !== null) {
                if (match[0] === searchText) {
                  const matchIndex = match.index;

                  let accumulatedLength = matchIndex;
                  for (const item of line.items) {
                    const itemText = item.str.replace(/\n+/g, '');
                    if (accumulatedLength <= itemText.length) {
                      const precedingTextWidth = calculateTextWidth(itemText.substring(0, accumulatedLength), item.transform[0]);
                      console.log(`Texto que precede: ${precedingTextWidth}`);

                      const x = item.transform[4] + precedingTextWidth;
                      const y = height - item.transform[5] + 1;
                      const matchWidth = calculateTextWidth(match[0], item.transform[0]);
                      const matchHeight = item.transform[0];

                      pages[pageIndex].drawRectangle({
                        x,
                        y: height - y,
                        width: matchWidth,
                        height: matchHeight,
                        color: rgb(1, 1, 0), // rojo (1,0,0)
                        opacity: 0.5,
                      });
                      hasHighlightedImeis = true;
                      // Páginas sin "Privada"
                      if (!pageText.includes("Privada") && hasHighlightedImeis) {
                        console.log(`Página ${pageIndex} contiene IMEIs resaltados. Conservando.`);
                        hasImeisInCurrentSection = true; // La sección tiene IMEIs resaltados
                        pageIndicesToKeep.add(pageIndex);
                      }
                      break;
                    }
                    accumulatedLength -= itemText.length;
                  }
                }
              }
            }
          }
        });
        // Detectar páginas con "Privada"
        if (pageText.includes("Privada")) {
          // Evaluar la sección previa antes de iniciar una nueva
          if (currentSectionPages.length > 0) {
            console.log(`Fin de sección en página ${pageIndex - 1}. Sección actual: ${currentSectionPages}`);

            // Si no hay IMEIs resaltados en la sección previa, eliminar las páginas
            if (!hasImeisInCurrentSection) {
              console.log(`No se encontraron IMEIs resaltados en la sección. Eliminando páginas: ${currentSectionPages}`);
              currentSectionPages.forEach((privadaPage) => pageIndicesToKeep.delete(privadaPage));
            } else {
              console.log(`Se encontraron IMEIs resaltados en la sección. Páginas mantenidas: ${currentSectionPages}`);
            }
          }
          // Reiniciar evaluación para la nueva sección
          hasImeisInCurrentSection = false;
          currentSectionPages = [];
          // Procesar la nueva portada
          if (hasHighlightedImeis) {
            console.log(`Página ${pageIndex} contiene "Privada" e IMEIs resaltados. Conservando.`);
            hasImeisInCurrentSection = true; // La sección tiene IMEIs resaltados
            pageIndicesToKeep.add(pageIndex); // Conservar la página
          } else {
            console.log(`Página ${pageIndex} contiene "Privada". Marcando como posible portada.`);
            currentSectionPages.push(pageIndex); // Registrar como posible portada
            pageIndicesToKeep.add(pageIndex);
          }
          continue; // Salta al siguiente ciclo
        }
      }
      // Evaluar la última sección al final del documento
      if (currentSectionPages.length > 0) {
        console.log(`Fin de sección en página ${pdf.numPages - 1}. Sección actual: ${currentSectionPages}`);

        if (!hasImeisInCurrentSection) {
          console.log(`No se encontraron IMEIs resaltados en la última sección. Eliminando páginas: ${currentSectionPages}`);
          currentSectionPages.forEach((privadaPage) => pageIndicesToKeep.delete(privadaPage));
        } else {
          console.log(`Se encontraron IMEIs resaltados en la última sección. Páginas mantenidas: ${currentSectionPages}`);
        }
      }

      // Crear un nuevo documento PDF que solo contenga las páginas seleccionadas
      const newPdfDoc = await PDFDocument.create();

      for (const pageIndex of pageIndicesToKeep) {
        const [page] = await newPdfDoc.copyPages(pdfDoc, [pageIndex]);
        newPdfDoc.addPage(page);
      }

      // Guardar y descargar el PDF modificado
      const newPdfBytes = await newPdfDoc.save();
      const blob = new Blob([newPdfBytes], { type: 'application/pdf' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = `highlighted_${pdfName}`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      loadingFile.value = null; // Indicar que el archivo ha terminado de procesarse
      successFile.value = pdfName;

      setTimeout(() => {
        successFile.value = null; // Restaurar el botón a su estado original después de unos segundos
      }, 3000);
    };

    // Función para calcular el ancho del texto utilizando las métricas de la fuente
    const calculateTextWidth = (text, fontSize) => {
      const scale = fontSize / 1000; // Escala del tamaño de la fuente
      const courierWidth = 600; // Ancho típico de caracteres en Courier New
      return text.length * courierWidth * scale;
    };

    // Función para reemplazar ENTER con una coma
    const replaceEnterWithComma = (event) => {
      imeis.value += ',';
    };

    // Función para limpiar la entrada de caracteres no deseados
    const cleanInput = () => {
      // Reemplaza todos los caracteres no deseados y elimina comas consecutivas
      imeis.value = imeis.value
        .replace(/[^0-9,]/g, '')    // Reemplaza todos los caracteres que no sean números o comas
        .replace(/,+/g, ',')        // Reemplaza múltiples comas consecutivas por una sola coma
        .replace(/^,|,$/g, '');     // Elimina comas al principio o al final del string
    };
    
    //Configuración del QR
    var optsQR = {
      // errorCorrectionLevel: 'H',
      type: 'image/jpeg',
      quality: 1,
      margin: 3,
      color: {
        dark:"#000000FF",
        light:"#FDFDFDFF"
      }
    }

    // Función para generar códigos QR
    const generateQRCodes = () => {
      Object.keys(results.value).forEach(fileName => {
        if (fileName !== 'N/A') {
          const canvas = document.getElementById(`qrcode-${fileName}`);
          if (canvas) {
            const url = `${window.location.origin}/highlight?filename=${fileName}&imeis=${results.value[fileName].map(result => result.imei).join(',')}`;
            QRCode.toCanvas(canvas, url, optsQR, (error) => {
              if (error) console.error(error);
            });
          }
        }
      });
    };
    // Configuración archivo PDF QRs
    var opt = {
      margin:       1,
      filename:     'IMEIs Teinnova Mayorista.pdf',
      image:        { type: 'jpeg', quality: 1 },
      html2canvas:  { scale: 2 },
      jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' },
      pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
    };
    
    // Función para imprimir el contenido de la tarjeta
    const printCard = async (fileName) => {

      // Margen y alineación al momento de imprimir
      const justifyCard = document.querySelector(".justify-print");
      justifyCard.classList.add('justify');
      
      // Selecciona contenido de la tarjeta a imprimir
      const cardContent = document.querySelector(`[data-card="${fileName}"]`);
      cardContent.classList.add('print-mode');
      await html2pdf().set(opt).from(cardContent).save();
      
      cardContent.classList.remove('print-mode');
      justifyCard.classList.remove('justify');
    };

  // Configuración archivo PDF Todas las tarjetas y QRs
  const optAll = {
    margin: 1,
    filename: 'IMEIs_Results.pdf',
    image: { type: 'jpeg', quality: 1 },
    html2canvas: { scale: 2 },
    jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' },
    pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
  };
 
  // Función para imprimir todas las tarjetas y QRs
  const printAllResults = async () => {
    const resultsContainer = document.querySelector('.results-container'); // Selecciona el contenedor de los resultados
    if (!resultsContainer) {
    console.error("No se encontró el contenedor de resultados.");
    return;
  }

  const allCards = resultsContainer.querySelectorAll('.result-card'); // Selecciona todas las tarjetas de resultados
  if (!allCards.length) {
    console.error("No se encontraron tarjetas de resultados.");
    return;
  }

  // Añade la clase 'print-mode' para ocultar botones antes de la impresión
  allCards.forEach(card => {
    card.classList.add('print-mode');
  });

  try {
    // Genera un archivo PDF con todas las tarjetas
    await html2pdf().set(optAll).from(resultsContainer).save();
  } catch (error) {
    console.error("Error generando el PDF:", error);
  }

  // Remueve la clase 'print-mode' después de la impresión
  allCards.forEach(card => {
    card.classList.remove('print-mode');
  });
};

  onMounted(() => {
    generateQRCodes();
  });

    return { imeis, results, errors, submit, navigateTo, shareWhatsApp, shareEmail, highlightImei, formatDate, replaceEnterWithComma, cleanInput, printCard, printAllResults, loadingFile, successFile };
  },
  data: () => ({ value: 0 }), // Inicializa el estado del botón de navegación
  computed: {
    color () {
      switch (this.value) {
        case 0: return 'deep-purple-accent-4'
        case 1: return 'light-blue-darken-4'
        default: return 'deep-purple-accent-4'
      }
    },
  },
  icons: {
    defaultSet: 'mdi',
  },
};
</script>

<style scoped>
.fill-height {
  height: 100vh;
}
.share-buttons {
  display: flex;
  justify-content: space-around;
}
.not-found {
  background-color: #f8d7da;
  color: #721c24;
}
.qr-code-container {
  display: flex;
  justify-content: center;
  margin-top: 16px;
  position: relative;
}
.print-btn {
  position: absolute;
  bottom: -20px;
  right: 2px;
}
.observations {
  display: flex;
  justify-content: center;
  white-space: inherit;
}
.observation {
  font-size: xx-small;
  width: 160px;
  text-align: center;
}
.action-btn {
  display: inline-block;
}
.print-mode .action-btn {
  display: none;
}
.justify {
  display: grid;
  justify-content: left;
  margin-left: -10px;
}
.floating-button {
  position: fixed;
  bottom: 16px;
  right: 16px;
  z-index: 1005;
  height: 64px;
  border-radius: 32px;
}
.result-card {
  font-size: 0.85rem; /* Reduce el tamaño de la fuente en las tarjetas de resultados */
}

.small-qr canvas {
  width: 70px; /* Reduce el tamaño del QR */
  height: 70px; /* Reduce el tamaño del QR */
  display: block; /* Asegura que los QR ocupen espacio y se impriman correctamente */margin: auto;
}

.headline {
  font-size: 1.25rem; /* Reduce ligeramente el tamaño de la cabecera de la tarjeta */
}

@media (max-width: 600px) {
  .result-card {
    font-size: 0.75rem; /* Reduce más la fuente en pantallas pequeñas */
  }

  .small-qr canvas {
    width: 50px; /* Reduce más el tamaño del QR en pantallas pequeñas */
    height: 50px; /* Reduce más el tamaño del QR en pantallas pequeñas */
  }
}

.nav-bar {
  bottom: 4px !important;
  left: 20% !important;
  width: calc(60% + 0px) !important;
  border-radius: 20px;
  opacity: 0.9;
}

</style>