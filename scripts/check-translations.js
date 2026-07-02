import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const files = {
    en: path.join(root, 'lang', 'en.json'),
    hu: path.join(root, 'lang', 'hu.json'),
};

const readJson = (locale, filePath) => {
    try {
        return JSON.parse(fs.readFileSync(filePath, 'utf8'));
    } catch (error) {
        console.error(`Invalid JSON in ${path.relative(root, filePath)} (${locale}): ${error.message}`);
        process.exitCode = 1;
        return null;
    }
};

const translations = Object.fromEntries(
    Object.entries(files).map(([locale, filePath]) => [locale, readJson(locale, filePath)]),
);

if (Object.values(translations).some((value) => value === null)) {
    process.exit(1);
}

const keysByLocale = Object.fromEntries(
    Object.entries(translations).map(([locale, values]) => [locale, Object.keys(values).sort()]),
);

const [enKeys, huKeys] = [keysByLocale.en, keysByLocale.hu];
const missingInHu = enKeys.filter((key) => !keysByLocale.hu.includes(key));
const missingInEn = huKeys.filter((key) => !keysByLocale.en.includes(key));

if (missingInHu.length || missingInEn.length) {
    console.error('Translation key sets are not synchronized.');

    if (missingInHu.length) {
        console.error(`Missing in lang/hu.json (${missingInHu.length}):`);
        missingInHu.forEach((key) => console.error(`  - ${key}`));
    }

    if (missingInEn.length) {
        console.error(`Missing in lang/en.json (${missingInEn.length}):`);
        missingInEn.forEach((key) => console.error(`  - ${key}`));
    }

    process.exit(1);
}

const translationKeys = new Set(enKeys);
const scannedRoots = ['resources/js', 'app/Http', 'app/Services', 'routes', 'config'];
const staticReferencePattern = /(?:trans|\$t|__)\(\s*['"]([^'"]+)['"]/g;

const walk = (directory) => {
    if (!fs.existsSync(directory)) {
        return [];
    }

    return fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
        const entryPath = path.join(directory, entry.name);

        if (entry.isDirectory()) {
            return walk(entryPath);
        }

        return entry.isFile() ? [entryPath] : [];
    });
};

const sourceFiles = scannedRoots.flatMap((directory) => walk(path.join(root, directory)));
const missingReferences = [];

sourceFiles.forEach((filePath) => {
    const relativePath = path.relative(root, filePath);
    const source = fs.readFileSync(filePath, 'utf8');
    const lines = source.split(/\r?\n/);

    lines.forEach((line, index) => {
        for (const match of line.matchAll(staticReferencePattern)) {
            const key = match[1];

            if (key.includes('$') || key.includes('{')) {
                continue;
            }

            if (!translationKeys.has(key)) {
                missingReferences.push(`${relativePath}:${index + 1} ${key}`);
            }
        }
    });
});

if (missingReferences.length) {
    console.error(`Missing static translation references (${missingReferences.length}):`);
    missingReferences.forEach((reference) => console.error(`  - ${reference}`));
    process.exit(1);
}

console.log(`Translation keys are synchronized (${enKeys.length} keys).`);
console.log('Static translation references are present.');
