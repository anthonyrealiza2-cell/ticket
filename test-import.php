<?php
// Test script to verify import-tickets.php is working
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Import</title>
</head>
<body>
    <h2>Test Import Endpoint</h2>
    
    <div>
        <button onclick="testImport()">Test import-tickets.php</button>
        <button onclick="testDatabase()">Test Database</button>
    </div>
    
    <pre id="result" style="background: #f4f4f4; padding: 10px; margin-top: 20px;"></pre>
    
    <script>
    function testImport() {
        const result = document.getElementById('result');
        result.textContent = 'Testing...';
        
        fetch('import-tickets.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                records: [{
                    company: 'Test Company',
                    contact: 'Test Contact',
                    concern: 'Test Concern'
                }],
                duplicate_handling: 'skip',
                skip_empty_rows: true
            })
        })
        .then(response => response.text())
        .then(text => {
            result.textContent = text;
        })
        .catch(error => {
            result.textContent = 'Error: ' + error.message;
        });
    }
    
    function testDatabase() {
        const result = document.getElementById('result');
        result.textContent = 'Testing database...';
        
        fetch('test-db.php')
        .then(response => response.text())
        .then(text => {
            result.textContent = text;
        })
        .catch(error => {
            result.textContent = 'Error: ' + error.message;
        });
    }
    </script>
</body>
</html>