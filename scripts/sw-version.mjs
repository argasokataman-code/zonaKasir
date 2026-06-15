import { readFileSync, writeFileSync } from "fs";
import { execSync } from "child_process";

const swPath = "public/serviceworker.js";
const sw = readFileSync(swPath, "utf8");

// Try git short hash first, fallback to timestamp
let version;
try {
  version = execSync("git rev-parse --short HEAD", {
    encoding: "utf8",
  }).trim();
} catch {
  version = Date.now().toString(36);
}

const updated = sw.replace(
  /const CACHE_VERSION = '.*'/,
  `const CACHE_VERSION = '${version}'`
);

writeFileSync(swPath, updated);
console.log(`[sw:version] CACHE_VERSION = ${version}`);
