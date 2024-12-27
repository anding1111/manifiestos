<template>
  <v-app>
    <v-main>
      <v-container class="d-flex justify-center align-center fill-height">
        <v-card class="pa-5" elevation="3" max-width="600" :class="{'success': !loading}">
          <v-card-title color="primary" class="text-2xl font-bold mb-4 text-center">
            <span v-if="loading">Generando y descargando IMEIs, por favor espere...</span>
            <span v-else>Proceso completado</span>
          </v-card-title>
          <v-card-text class="text-center">
            <v-progress-circular v-if="loading" indeterminate color="primary" size="64"></v-progress-circular>
            <v-icon v-else color="green" size="64">mdi-check-circle</v-icon>
          </v-card-text>
        </v-card>
      </v-container>
    </v-main>
  </v-app>
</template>

<script>
import { ref, onMounted } from 'vue';
import { PDFDocument, rgb } from 'pdf-lib';
import * as pdfjsLib from 'pdfjs-dist';
import { Inertia } from '@inertiajs/inertia';

pdfjsLib.GlobalWorkerOptions.workerSrc = `https://unpkg.com/pdfjs-dist@${pdfjsLib.version}/build/pdf.worker.min.mjs`;

export default {
  setup() {
    const loading = ref(true);

    const getUrlParams = (url) => {
      const params = {};
      new URL(url).searchParams.forEach((value, key) => {
        params[key] = value;
      });
      return params;
    };

    const highlightImeiFromUrl = async () => {
      try {
        const { props } = Inertia.page; // Obtén los datos pasados por el servidor
        const filename = props.filename; // Nombre del archivo PDF
        let pdfName = filename; // Asignar filename a pdfName para usarlo como nombre del PDF descargado
        let searchTexts = props.imeis; // IMEIs a resaltar

        if (!filename || !searchTexts) {
          throw new Error("Datos insuficientes para procesar el PDF.");
        }

        // Asegúrate de que `searchTexts` sea un array
        if (typeof searchTexts === 'string') {
          searchTexts = JSON.parse(searchTexts); // Intenta parsear si es un string JSON
        }
        if (!Array.isArray(searchTexts)) {
          throw new Error("IMEIs no están en formato de array.");
        }

        const url = `/storage/uploads/${filename}`;
        const existingPdfBytes = await fetch(url).then((res) => res.arrayBuffer());

        const pdfDoc = await PDFDocument.load(existingPdfBytes);
        const pages = pdfDoc.getPages();
        const pageIndicesToKeep = new Set([0]);

        const loadingTask = pdfjsLib.getDocument(url);
        const pdf = await loadingTask.promise;

        // Variables adicionales
        let currentSectionPages = []; // Inicialización correcta
        let hasImeisInCurrentSection = false; // Inicialización correcta

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

            // Si no hay IMEIs resaltados en la sección previa, eliminar las páginas
            if (!hasImeisInCurrentSection) {
              currentSectionPages.forEach((privadaPage) => pageIndicesToKeep.delete(privadaPage));
            } 
          }
          // Reiniciar evaluación para la nueva sección
          hasImeisInCurrentSection = false;
          currentSectionPages = [];
          // Procesar la nueva portada
          if (hasHighlightedImeis) {
            hasImeisInCurrentSection = true; // La sección tiene IMEIs resaltados
            pageIndicesToKeep.add(pageIndex); // Conservar la página
          } else {
            currentSectionPages.push(pageIndex); // Registrar como posible portada
            pageIndicesToKeep.add(pageIndex);
          }
          continue; // Salta al siguiente ciclo
        }
      }
      // Evaluar la última sección al final del documento
      if (currentSectionPages.length > 0) {

        if (!hasImeisInCurrentSection) {
          currentSectionPages.forEach((privadaPage) => pageIndicesToKeep.delete(privadaPage));
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

      loading.value = false;
      } catch (error) {
        console.error('Error al procesar los IMEIs:', error);
        loading.value = false;
      }
    };

    // Función para calcular el ancho del texto utilizando las métricas de la fuente
    const calculateTextWidth = (text, fontSize) => {
      const scale = fontSize / 1000; // Escala del tamaño de la fuente
      const courierWidth = 600; // Ancho típico de caracteres en Courier New
      return text.length * courierWidth * scale;
    };

    onMounted(() => {
      highlightImeiFromUrl();
    });

    return { loading };
  }
};
</script>

<style scoped>
.fill-height {
  height: 100vh;
}
.success {
  background-color: #d4edda;
  color: #155724;
}
</style>
