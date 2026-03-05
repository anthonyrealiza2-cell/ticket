<?php
// test-template.php
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Template Download</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        body { font-family: Arial; padding: 20px; }
        button { padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        pre { background: #f4f4f4; padding: 10px; }
    </style>
</head>
<body>
    <h2>Test Template Download and Parse</h2>
    <button onclick="testTemplate()">Download and Test Template</button>
    <pre id="result"></pre>

    <script>
        function testTemplate() {
            const result = document.getElementById('result');
            result.textContent = 'Downloading template...';
            
            // Download the template
            fetch('download-template.php?type=tickets')
                .then(response => response.arrayBuffer())
                .then(data => {
                    result.textContent += '\n✅ Template downloaded successfully';
                    result.textContent += `\nFile size: ${data.byteLength} bytes`;
                    
                    // Try to parse it
                    try {
                        const workbook = XLSX.read(data, { type: 'array' });
                        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                        const jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });
                        
                        result.textContent += '\n✅ Template parsed successfully';
                        result.textContent += `\nRows in template: ${jsonData.length}`;
                        result.textContent += `\nHeaders: ${jsonData[0].join(' | ')}`;
                        
                        // Count empty rows
                        let emptyRows = 0;
                        for (let i = 1; i < jsonData.length; i++) {
                            if (!jsonData[i] || jsonData[i].every(cell => !cell)) {
                                emptyRows++;
                            }
                        }
                        result.textContent += `\nEmpty rows: ${emptyRows}`;
                        result.textContent += `\n\n✅ Template is valid and ready for import!`;
                        
                    } catch (e) {
                        result.textContent += `\n❌ Error parsing template: ${e.message}`;
                    }
                })
                .catch(error => {
                    result.textContent += `\n❌ Error downloading template: ${error.message}`;
                });
        }
    </script>
</body>
</html>