const fs = require('fs');
const path = 'app/admin/page.tsx';
let c = fs.readFileSync(path, 'utf8');

// Encontrar la linea problematica y reemplazarla
// El archivo tiene $\{o.id\} como caracteres literales
// Los reemplazamos por concatenacion simple
const lines = c.split('\n');
for (let i = 0; i < lines.length; i++) {
  if (lines[i].includes('admin/orden')) {
    console.log('ANTES:', JSON.stringify(lines[i]));
    // Reemplazar con concatenacion directa sin template literals
    lines[i] = lines[i].replace(
      /onClick=\{[^}]*router\.push\([^)]*admin\/orden[^)]*\)\}/,
      "onClick={() => { router.push('/admin/orden?id=' + String(o.id)); }}"
    );
    console.log('DESPUES:', JSON.stringify(lines[i]));
  }
}
c = lines.join('\n');
fs.writeFileSync(path, c);
console.log('DONE');
