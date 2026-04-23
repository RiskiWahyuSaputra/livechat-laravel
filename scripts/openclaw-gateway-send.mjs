import fs from "node:fs";
import path from "node:path";
import { createRequire } from "node:module";
import { pathToFileURL } from "node:url";

const args = parseArgs(process.argv.slice(2));
const timeoutMs = toPositiveInt(process.env.OPENCLAW_GATEWAY_SEND_TIMEOUT_MS, 15000);
const gatewayUrl = (process.env.OPENCLAW_GATEWAY_URL || "ws://127.0.0.1:18789").trim();
const origin = (process.env.OPENCLAW_GATEWAY_ORIGIN || deriveHttpOrigin(gatewayUrl)).trim();
const token = (process.env.OPENCLAW_GATEWAY_TOKEN || "").trim();
const channel = (args.channel || "whatsapp").trim();
const method = (args.method || "send").trim();
const target = (args.target || "").trim();
const message = (args.message || "").trim();
const mediaUrl = (args.media || "").trim();
const buttons = (args.buttons || "").trim();
const messageId = (args["message-id"] || "").trim();
const accountId = (args.account || "").trim();
const threadId = (args["thread-id"] || "").trim();

if (!target) {
  fail("Missing required --target value.");
}

if (method === "send" && !message && !mediaUrl) {
  fail("Either --message or --media is required.");
}

const openClawRoot = resolveOpenClawRoot();
const requireFromOpenClaw = createRequire(path.join(openClawRoot, "package.json"));
const WebSocket = requireFromOpenClaw("ws");

const ws = new WebSocket(gatewayUrl, { origin });
const pending = new Map();
let finished = false;

const cleanup = (code = 0) => {
  if (finished) {
    return;
  }

  finished = true;
  clearTimeout(timeoutHandle);

  for (const entry of pending.values()) {
    entry.reject(new Error("Gateway sender stopped before request completed."));
  }

  pending.clear();

  try {
    ws.close();
  } catch {
    // Ignore close errors on shutdown.
  }

  process.exit(code);
};

const timeoutHandle = setTimeout(() => {
  fail(`Gateway sender timed out after ${timeoutMs}ms.`);
}, timeoutMs);

ws.on("open", () => {
  debugLog(`connected to ${gatewayUrl}`);
});

ws.on("message", async (raw) => {
  try {
    const frame = JSON.parse(raw.toString());

    if (frame.type === "event" && frame.event === "connect.challenge") {
      await handleConnectChallenge(frame);
      return;
    }

    if (frame.type === "res") {
      const entry = pending.get(frame.id);
      if (!entry) {
        return;
      }

      pending.delete(frame.id);

      if (frame.ok) {
        await entry.resolve(frame.payload);
      } else {
        entry.reject(new Error(frame.error?.message || "Gateway request failed."));
      }
    }
  } catch (error) {
    fail(error instanceof Error ? error.message : String(error));
  }
});

ws.on("error", (error) => {
  fail(error instanceof Error ? error.message : String(error));
});

ws.on("close", (code, reason) => {
  if (finished) {
    return;
  }

  const reasonText = reason?.toString() || "no reason";
  fail(`Gateway closed (${code}): ${reasonText}`);
});

async function handleConnectChallenge(frame) {
  debugLog(`received connect challenge nonce=${frame.payload?.nonce || "-"}`);

  await request("connect", {
    minProtocol: 3,
    maxProtocol: 3,
    client: {
      id: "openclaw-control-ui",
      version: "laravel-bridge",
      platform: process.platform,
      mode: "webchat",
    },
    role: "operator",
    scopes: ["operator.admin"],
    ...(token ? { auth: { token } } : {}),
  });

  let params = {
    channel,
    to: target,
  };

  if (method === "message.send" || method === "send") {
    params.idempotencyKey = buildIdempotencyKey();
    
    let finalMessage = message;
    let buttonObj = null;

    if (buttons) {
      try {
        buttonObj = JSON.parse(buttons);
      } catch (e) {
        debugLog(`Failed to parse buttons JSON: ${e.message}`);
      }
    }

    if (buttonObj && Array.isArray(buttonObj)) {
      // The gateway requires /message to be a string and rejects a 'buttons' property at the root.
      // We will provide the text directly in /message and append buttons as text.
      params.message = finalMessage;
      
      if (params.message) {
        params.message += "\n";
        buttonObj.forEach((btn, idx) => {
          const label = btn.reply?.title || btn.title || "Option";
          params.message += `\n[${idx + 1}] ${label}`;
        });
      }
    } else {
      if (finalMessage) params.message = finalMessage;
    }

    if (mediaUrl) params.mediaUrl = mediaUrl;
    if (accountId) params.accountId = accountId;
    if (threadId) params.threadId = threadId;
  } else if (method === "message.read" || method === "whatsapp.read") {
    // Disable unknown method to avoid errors in gateway log
    debugLog(`Method ${method} is ignored as it is not supported by this gateway.`);
    cleanup(0);
    return;
  } else {
    // Generic params for other methods
    params = { ...params, ...args };
    delete params.method;
  }

  const result = await request(method, params);
  process.stdout.write(`${JSON.stringify(result)}\n`);
  cleanup(0);
}

function request(method, params) {
  return new Promise((resolve, reject) => {
    const id = buildRequestId();
    pending.set(id, { resolve, reject });
    ws.send(
      JSON.stringify({
        type: "req",
        id,
        method,
        params,
      }),
    );
  });
}

function parseArgs(values) {
  const result = {};

  for (let index = 0; index < values.length; index += 1) {
    const current = values[index];

    if (!current.startsWith("--")) {
      continue;
    }

    const key = current.slice(2);
    const next = values[index + 1];

    if (!next || next.startsWith("--")) {
      result[key] = "true";
      continue;
    }

    result[key] = next;
    index += 1;
  }

  return result;
}

function resolveOpenClawRoot() {
  const explicitRoot = (process.env.OPENCLAW_PACKAGE_ROOT || "").trim();
  if (explicitRoot) {
    return explicitRoot;
  }

  const scriptPath = (process.env.OPENCLAW_SCRIPT_PATH || "").trim();
  if (scriptPath) {
    return path.dirname(scriptPath);
  }

  fail("OpenClaw package path is missing. Set OPENCLAW_SCRIPT_PATH or OPENCLAW_PACKAGE_ROOT.");
}

function deriveHttpOrigin(wsUrl) {
  try {
    const parsed = new URL(wsUrl);
    const protocol = parsed.protocol === "wss:" ? "https:" : "http:";
    return `${protocol}//${parsed.host}`;
  } catch {
    return "http://127.0.0.1:18789";
  }
}

function buildRequestId() {
  return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

function buildIdempotencyKey() {
  return `laravel-${Date.now()}-${Math.random().toString(16).slice(2, 10)}`;
}

function toPositiveInt(value, fallback) {
  const parsed = Number.parseInt(String(value || ""), 10);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
}

function debugLog(message) {
  if (process.env.OPENCLAW_GATEWAY_SEND_DEBUG !== "1") {
    return;
  }

  const logFile = path.join(process.cwd(), "storage", "logs", "openclaw-gateway-send.log");

  try {
    fs.mkdirSync(path.dirname(logFile), { recursive: true });
    fs.appendFileSync(logFile, `[${new Date().toISOString()}] ${message}\n`, "utf8");
  } catch {
    // Ignore debug logging failures.
  }
}

function fail(message) {
  const error = message instanceof Error ? message.message : String(message);
  process.stderr.write(`${error}\n`);
  cleanup(1);
}
