const fs = require('fs');
const path = require('path');

const dir = path.join(__dirname, 'scripts', 'migration');
const files = fs.readdirSync(dir).filter(f => f.endsWith('.js'));

for (const file of files) {
  const filePath = path.join(dir, file);
  let content = fs.readFileSync(filePath, 'utf8');
  if (content.includes("require('../models/")) {
    content = content.replace(/require\('\.\.\/models\//g, "require('../../models/");
    fs.writeFileSync(filePath, content);
    console.log('Fixed', file);
  }
}
