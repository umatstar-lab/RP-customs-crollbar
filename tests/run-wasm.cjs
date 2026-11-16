const fs = require('node:fs');
const path = require('node:path');
const { PhpNode } = require('php-wasm/PhpNode');
const cssTree = require('css-tree');

(async () => {
    const php = new PhpNode({ version: process.argv[2] || '8.3' });
    let output = '';
    php.addEventListener('output', event => {
        output += Array.isArray(event.detail) ? event.detail.join('') : String(event.detail);
    });
    php.addEventListener('error', event => process.stderr.write(String(event.detail)));
    const root = path.resolve(__dirname, '..');
    const source = fs.readFileSync(path.join(root, 'src/Support/Settings.php'), 'utf8').replace(/^<\?php/, '');
    const encoded = Buffer.from(source).toString('base64');
    const code = fs.readFileSync(path.join(__dirname, 'security.php'), 'utf8')
        .replace("require __DIR__ . '/../src/Support/Settings.php';", `eval(base64_decode('${encoded}'));`);
    await php.run(code);
    const result = JSON.parse(output);
    for (const css of result.css) {
        cssTree.parse(css, { onParseError: error => { throw error; } });
    }
    delete result.css;
    result.css_parser = 'PASS';
    console.log(JSON.stringify(result));
})().catch(error => { console.error(error); process.exitCode = 1; });
