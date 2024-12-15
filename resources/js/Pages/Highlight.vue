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
      const { filename, imeis } = getUrlParams(window.location.href);
      const searchTexts = imeis.split(',');

      const url = `/storage/uploads/${filename}`;
      const existingPdfBytes = await fetch(url).then(res => res.arrayBuffer());

      const pdfDoc = await PDFDocument.load(existingPdfBytes);
      const pages = pdfDoc.getPages();
      const pageIndicesToKeep = new Set([0]);

      const loadingTask = pdfjsLib.getDocument(url);
      const pdf = await loadingTask.promise;

      for (let pageIndex = 0; pageIndex < pdf.numPages; pageIndex++) {
        const page = await pdf.getPage(pageIndex + 1);
        const textContent = await page.getTextContent();
        const { width, height } = pages[pageIndex].getSize();

        let combinedLine = '';
        let lineItems = [];
        let combinedLines = [];

        textContent.items.forEach((item, index) => {
          combinedLine += item.str.replace(/\n+/g, '');
          lineItems.push(item);

          if (index + 1 < textContent.items.length && textContent.items[index + 1].transform[5] !== item.transform[5]) {
            combinedLines.push({ text: combinedLine, items: lineItems });
            combinedLine = '';
            lineItems = [];
          }
        });

        combinedLines.push({ text: combinedLine, items: lineItems });

        searchTexts.forEach(searchText => {
          const pattern = /\b\d{15}\b/g;
          let imeiFound = false;

          for (let i = 0; i < combinedLines.length; i++) {
            let line = combinedLines[i];
            let nextLine = combinedLines[i + 1] ? combinedLines[i + 1].text : '';

            for (let j = 1; j < searchText.length; j++) {
              if (line.text.endsWith(searchText.substring(0, j)) && nextLine.startsWith(searchText.substring(j))) {
                line.text += nextLine;
                console.log(`IMEI dividido entre líneas ${i + 1} y ${i + 2}: ${line.text}`);

                let accumulatedLength = line.text.length - nextLine.length;
                let remainingLength = accumulatedLength;

                for (const item of line.items) {
                  const itemText = line.text.replace(/\n+/g, '');

                  if (remainingLength <= itemText.length) {
                    const precedingText = itemText.substring(0, remainingLength);
                    const precedingTextWidth = calculateTextWidth(precedingText, item.transform[0]);

                    const x = item.transform[4] + precedingTextWidth - 9;
                    const y = height - item.transform[5] - 0.5;
                    const matchWidth = (calculateTextWidth(searchText.substring(0, j), item.transform[0])) + 9;
                    const matchHeight = item.transform[0];

                    pages[pageIndex].drawRectangle({
                      x: x,
                      y: height - y - 2,
                      width: matchWidth,
                      height: matchHeight,
                      color: rgb(1, 1, 0),
                      opacity: 0.5,
                    });

                    break;
                  }
                  remainingLength -= itemText.length;
                }

                accumulatedLength = 0;
                remainingLength = searchText.length - j;

                for (const item of combinedLines[i + 1].items) {
                  const itemText = item.str.replace(/\n+/g, '');

                  if (accumulatedLength <= itemText.length) {
                    const x = item.transform[4];
                    const y = height - item.transform[5] - 0.5;
                    const matchWidth = (calculateTextWidth(searchText.substring(j), item.transform[0])) + 9;
                    const matchHeight = item.transform[0];

                    pages[pageIndex].drawRectangle({
                      x: x,
                      y: height - y - 2,
                      width: matchWidth,
                      height: matchHeight,
                      color: rgb(1, 1, 0),
                      opacity: 0.5,
                    });

                    imeiFound = true;
                    pageIndicesToKeep.add(pageIndex);
                    break;
                  }
                  accumulatedLength -= itemText.length;
                }

                break;
              }
            }

            if (imeiFound) break;

            let match;
            while ((match = pattern.exec(line.text)) !== null) {
              if (match[0] === searchText) {
                const matchIndex = match.index;

                let accumulatedLength = matchIndex;
                let remainingLength = matchIndex;

                for (const item of line.items) {
                  const itemText = line.text.replace(/\n+/g, '');

                  if (remainingLength <= itemText.length) {
                    const precedingText = itemText.substring(0, remainingLength);
                    const precedingTextWidth = calculateTextWidth(precedingText, item.transform[0]);

                    const x = item.transform[4] + precedingTextWidth - 9;
                    const y = height - item.transform[5] - 0.5;
                    const matchWidth = (calculateTextWidth(match[0], item.transform[0])) + 9;
                    const matchHeight = item.transform[0];

                    pages[pageIndex].drawRectangle({
                      x: x,
                      y: height - y - 2,
                      width: matchWidth,
                      height: matchHeight,
                      color: rgb(1, 1, 0),
                      opacity: 0.5,
                    });

                    imeiFound = true;
                    pageIndicesToKeep.add(pageIndex);
                    break;
                  }
                  remainingLength -= itemText.length;
                }
              }
            }
          }
        });
      }

      const newPdfDoc = await PDFDocument.create();
      const [coverPage] = await newPdfDoc.copyPages(pdfDoc, [0]);
      newPdfDoc.addPage(coverPage);

      for (const pageIndex of pageIndicesToKeep) {
        if (pageIndex !== 0) {
          const [page] = await newPdfDoc.copyPages(pdfDoc, [pageIndex]);
          newPdfDoc.addPage(page);
        }
      }

      const newPdfBytes = await newPdfDoc.save();
      const blob = new Blob([newPdfBytes], { type: 'application/pdf' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = `highlighted_${filename}`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      loading.value = false;
    };

    const calculateTextWidth = (text, fontSize) => {
      const scale = fontSize / 1000;
      const courierWidth = 600;
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
