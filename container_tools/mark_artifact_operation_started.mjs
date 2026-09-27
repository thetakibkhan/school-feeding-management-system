import { mkdir, writeFile } from "node:fs/promises";
import { resolve } from "node:path";

const options = new Map();
for (let index = 2; index < process.argv.length; index += 2) {
  options.set(process.argv[index], process.argv[index + 1]);
}

const operationKind = options.get("--operation-kind");
const expectedOutputCount = Number(options.get("--expected-output-count"));
const outputFormat = options.get("--output-format");

if (!['create', 'edit'].includes(operationKind)
  || !Number.isInteger(expectedOutputCount)
  || expectedOutputCount < 1
  || !outputFormat) {
  throw new Error("Expected --operation-kind create|edit --expected-output-count N --output-format FORMAT");
}

const markerDirectory = resolve("tmp/pdfs");
await mkdir(markerDirectory, { recursive: true });
await writeFile(
  resolve(markerDirectory, ".artifact-operation-started.json"),
  `${JSON.stringify({ operationKind, expectedOutputCount, outputFormat, startedAt: new Date().toISOString() }, null, 2)}\n`,
  "utf8",
);
