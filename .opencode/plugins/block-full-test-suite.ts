import type { Plugin } from "@opencode-ai/plugin"

// Blocks running the FULL test suite locally (php artisan test without a filter).
// Full E2E/regression runs on GitHub CI (test.yml, PostgreSQL 15) — local OOMs at 128M.
// See AGENTS.md "ATURAN FULL TEST SUITE (ENFORCE)".

const FULL_SUITE_PATTERNS = [
  /\bphp\s+artisan\s+test\s*$/i,
  /\bphp\s+artisan\s+test\s+2>&1\s*$/i,
  /\bphp\s+artisan\s+test\s+\|/i,
]

function isFullSuite(command: string): boolean {
  const trimmed = command.trim()
  if (!FULL_SUITE_PATTERNS.some((re) => re.test(trimmed))) return false
  // Allow explicit --filter / file path / folder targeting specific tests.
  if (/(--filter|--testsuite|--exclude-group|--group)/i.test(trimmed)) return false
  if (/tests\/(Feature|Unit|Browser)\//i.test(trimmed)) return false
  if (/\.php/i.test(trimmed)) return false
  return true
}

const NoFullSuiteLocally: Plugin = async () => {
  return {
    "tool.execute.before": async (input, output) => {
      const tool = String(input?.tool ?? "").toLowerCase()
      if (tool !== "bash" && tool !== "shell") return
      const args = output?.args
      if (!args || typeof args !== "object") return

      const command = (args as Record<string, unknown>).command
      if (typeof command !== "string" || !command) return
      if (!isFullSuite(command)) return

      throw new Error(
        "🚫 BLOCKED: full test suite dilarang di local. " +
          "Commit + push ke main/vercel/1.x — GitHub CI (test.yml, PostgreSQL 15) yang jalanin full regression. " +
          "Local: hanya test spesifik dengan --filter / path file / folder."
      )
    },
  }
}

export default NoFullSuiteLocally satisfies Plugin