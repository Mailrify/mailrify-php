#!/usr/bin/env node
import { writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, '..');
const outPath = path.join(root, 'openapi.yaml');

const defaultUrl = 'https://raw.githubusercontent.com/Mailrify/mailrify-openapi/main/openapi.yaml';
const specUrl = process.env.MAILRIFY_OPENAPI_URL ?? defaultUrl;

console.log(`Downloading OpenAPI spec from ${specUrl}`);

const response = await fetch(specUrl);
if (!response.ok) {
  throw new Error(`Failed to download OpenAPI spec: ${response.status} ${response.statusText}`);
}

const contents = await response.text();
await writeFile(outPath, contents);

const versionMatch = contents.match(/\n\s*version:\s*([^\s]+)/);
const version = process.env.MAILRIFY_OPENAPI_VERSION ?? (versionMatch ? versionMatch[1] : 'unknown');

const metadata = {
  source: specUrl,
  downloadedAt: new Date().toISOString(),
  openapiVersion: version,
};

await writeFile(path.join(root, 'spec-version.json'), JSON.stringify(metadata, null, 2) + '\n');

console.log('Updated openapi.yaml and spec-version.json');
