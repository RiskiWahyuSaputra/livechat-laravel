import fs from "node:fs";
import os from "node:os";
import path from "node:path";
import { spawn } from "node:child_process";

const appUrl = trimTrailingSlash(
  (process.env.OPENCLAW_LARAVEL_APP_URL || process.env.APP_URL || "").trim(),
);
const pullUrl = (
  process.env.OPENCLAW_LARAVEL_OUTBOUND_PULL_URL ||
  `${appUrl}/api/openclaw/whatsapp/outbound/pull`
).trim();
const ackUrl = (
  process.env.OPENCLAW_LARAVEL_OUTBOUND_ACK_URL ||
  `${appUrl}/api/openclaw/whatsapp/outbound/ack`
).trim();
const token = (
  process.env.OPENCLAW_OUTBOUND_BRIDGE_TOKEN ||
  process.env.OPENCLAW_BRIDGE_TOKEN ||
  ""
).trim();
const channel = (process.env.OPENCLAW_WHATSAPP_CHANNEL || "whatsapp").trim();
const account = (process.env.OPENCLAW_WHATSAPP_ACCOUNT || "").trim();
const cliPath = (process.env.OPENCLAW_CLI_PATH || "openclaw").trim();
const pollIntervalMs = toPositiveInt(process.env.OPENCLAW_OUTBOUND_POLL_INTERVAL_MS, 2000);
const leaseSeconds = toPositiveInt(process.env.OPENCLAW_OUTBOUND_LEASE_SECONDS, 60);
const workerId =
  (process.env.OPENCLAW_OUTBOUND_WORKER_ID || `${os.hostname()}-${process.pid}`).trim();
const logFile = (
  process.env.OPENCLAW_OUTBOUND_WORKER_LOG ||
  path.join(os.homedir(), ".openclaw", "logs", "laravel-whatsapp-outbound-worker.log")
).trim();

let stopping = false;

process.on("SIGINT", () => {
  stopping = true;
  log("worker stopping (SIGINT)");
});

process.on("SIGTERM", () => {
  stopping = true;
  log("worker stopping (SIGTERM)");
});

if (!pullUrl || !ackUrl) {
  log("pull/ack URL belum terkonfigurasi. Isi APP_URL atau OPENCLAW_LARAVEL_OUTBOUND_*_URL.");
  process.exit(1);
}

log(`worker started pull=${pullUrl} ack=${ackUrl} cli=${cliPath}`);

while (!stopping) {
  try {
    await pollOnce();
  } catch (error) {
    log(`poll exception: ${error instanceof Error ? error.message : String(error)}`);
  }

  if (stopping) {
    break;
  }

  await sleep(pollIntervalMs);
}

async function pollOnce() {
  const response = await fetch(pullUrl, {
    method: "POST",
    headers: buildHeaders(),
    body: JSON.stringify({
      limit: 10,
      lease_seconds: leaseSeconds,
      worker_id: workerId,
    }),
  });

  if (!response.ok) {
    const body = await response.text();
    log(`pull failed status=${response.status} body=${body}`);
    return;
  }

  const payload = await response.json();
  const items = Array.isArray(payload.items) ? payload.items : [];

  for (const item of items) {
    await processItem(item);
  }
}

async function processItem(item) {
  const id = String(item?.id || "").trim();
  const payload = item?.payload && typeof item.payload === "object" ? item.payload : {};
  const action = String(payload.action || "send").trim();

  if (!id) {
    return;
  }

  if (action !== "send") {
    await acknowledge(id, false, `unsupported action: ${action}`);
    return;
  }

  const target = String(payload.target || "").trim();
  const message = formatMessage(String(payload.message || ""), payload.buttons);
  const mediaUrl = String(payload.media_url || "").trim();
  const resolvedChannel = String(payload.channel || channel || "whatsapp").trim();
  const resolvedAccount = String(payload.account || account || "").trim();

  if (!target) {
    await acknowledge(id, false, "missing target");
    return;
  }

  if (!message && !mediaUrl) {
    await acknowledge(id, false, "missing message and media");
    return;
  }

  const args = ["message", "send", "--channel", resolvedChannel, "--target", target];

  if (resolvedAccount) {
    args.push("--account", resolvedAccount);
  }

  if (message) {
    args.push("--message", message);
  }

  const isWin = process.platform === "win32";
  const displayArgs = isWin 
    ? args.map(arg => `"${String(arg).replace(/"/g, '""')}"`)
    : args;

  log(`EXECUTING: ${cliPath} ${displayArgs.join(" ")}`);

  const result = await runCommand(cliPath, args);
  if (result.code === 0) {
    log(`sent job=${id} target=${target}`);
    await acknowledge(id, true);
    return;
  }

  const error = [result.stderr, result.stdout].filter(Boolean).join(" | ").trim();
  log(`send failed job=${id} target=${target} error=${error}`);
  await acknowledge(id, false, error || `exit code ${result.code}`);
}

async function acknowledge(id, success, error = "") {
  const response = await fetch(ackUrl, {
    method: "POST",
    headers: buildHeaders(),
    body: JSON.stringify({
      id,
      success,
      error,
      worker_id: workerId,
    }),
  });

  if (!response.ok) {
    const body = await response.text();
    log(`ack failed id=${id} status=${response.status} body=${body}`);
  }
}

function buildHeaders() {
  const headers = {
    "Content-Type": "application/json",
  };

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  return headers;
}

function runCommand(command, args) {
  return new Promise((resolve) => {
    const isWin = process.platform === "win32";
    
    // Manual escaping for Windows shell
    const escapedArgs = isWin 
      ? args.map(arg => `"${String(arg).replace(/"/g, '""')}"`)
      : args;

    const child = spawn(command, escapedArgs, {
      shell: isWin,
      windowsHide: true,
      stdio: ["ignore", "pipe", "pipe"],
    });

    let stdout = "";
    let stderr = "";

    child.stdout.on("data", (chunk) => {
      stdout += chunk.toString();
    });

    child.stderr.on("data", (chunk) => {
      stderr += chunk.toString();
    });

    child.on("close", (code) => {
      resolve({
        code: typeof code === "number" ? code : 1,
        stdout: stdout.trim(),
        stderr: stderr.trim(),
      });
    });

    child.on("error", (error) => {
      resolve({
        code: 1,
        stdout: stdout.trim(),
        stderr: error instanceof Error ? error.message : String(error),
      });
    });
  });
}

function formatMessage(message, buttons) {
  const text = (message || "").trim();
  if (!Array.isArray(buttons) || buttons.length === 0) {
    return text;
  }

  const lines = buttons
    .map((button, index) => {
      const label =
        button?.reply?.title ||
        button?.title ||
        button?.label ||
        "";

      return label ? `[${index + 1}] ${label}` : "";
    })
    .filter(Boolean);

  if (lines.length === 0) {
    return text;
  }

  return [text, lines.join("\n")].filter(Boolean).join("\n\n");
}

function log(message) {
  const line = `[${new Date().toISOString()}] ${message}`;

  try {
    fs.mkdirSync(path.dirname(logFile), { recursive: true });
    fs.appendFileSync(logFile, `${line}\n`, "utf8");
  } catch {
    // Ignore file logging failures and still print to stdout.
  }

  process.stdout.write(`${line}\n`);
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function toPositiveInt(value, fallback) {
  const parsed = Number.parseInt(String(value || ""), 10);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
}

function trimTrailingSlash(value) {
  return value.replace(/\/+$/, "");
}
