import fs from "node:fs";
import os from "node:os";
import path from "node:path";

const DEFAULT_WEBHOOK_PATH = "/api/webhook/openclaw/whatsapp";
const DEFAULT_WEBHOOK_URL = `http://127.0.0.1:8000${DEFAULT_WEBHOOK_PATH}`;
const DEFAULT_LOG_DIR = path.join(os.homedir(), ".openclaw", "logs");
const DEFAULT_LOG_FILE = path.join(DEFAULT_LOG_DIR, "laravel-whatsapp-bridge.log");

const handler = async (event) => {
  if (event?.type !== "message" || event?.action !== "received") {
    return;
  }

  const context = event?.context ?? {};
  const channelId = normalizeString(
    context.channelId ?? context.channel ?? context.sourceChannel,
  ).toLowerCase();

  if (channelId !== "whatsapp") {
    return;
  }

  const fromMe = normalizeBoolean(
    context.fromMe ?? getNested(context, ["metadata", "fromMe"]),
  );

  if (fromMe) {
    return;
  }

  const workspaceDir = normalizeString(context.workspaceDir) || process.cwd();
  const env = loadWorkspaceEnv(workspaceDir);
  const webhookUrl = resolveWebhookUrl(env);
  const bridgeToken = resolveBridgeToken(env);

  if (!webhookUrl) {
    debugLog(`[skip] webhook URL tidak ditemukan. workspace=${workspaceDir}`);
    return;
  }

  const payload = {
    context: {
      channelId: "whatsapp",
      from: normalizeSender(context),
      senderName: normalizeString(
        getNested(context, ["metadata", "senderName"]) ??
          getNested(context, ["metadata", "pushName"]) ??
          context.senderName,
      ),
      bodyForAgent: normalizeString(
        context.bodyForAgent ?? context.content ?? context.text ?? context.body,
      ),
      messageType: normalizeMessageType(
        normalizeString(context.messageType ?? context.type),
      ),
      messageId: normalizeString(
        context.messageId ??
          context.id ??
          getNested(context, ["metadata", "messageId"]) ??
          getNested(context, ["metadata", "id"]),
      ),
      mediaUrl: normalizeString(
        context.mediaUrl ??
          getNested(context, ["metadata", "mediaUrl"]) ??
          getNested(context, ["metadata", "attachment", "url"]),
      ),
      metadata: sanitizeMetadata(context.metadata),
    },
  };

  if (!payload.context.from || !payload.context.bodyForAgent) {
    debugLog(
      `[skip] payload tidak lengkap. from=${payload.context.from || "-"} content=${payload.context.bodyForAgent || "-"}`,
    );
    return;
  }

  try {
    debugLog(
      `[forward] from=${payload.context.from} type=${payload.context.messageType} url=${webhookUrl}`,
    );
    const response = await fetch(webhookUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        ...(bridgeToken ? { Authorization: `Bearer ${bridgeToken}` } : {}),
      },
      body: JSON.stringify(payload),
    });

    if (!response.ok) {
      const responseText = await response.text();
      debugLog(`[error] webhook gagal ${response.status}: ${responseText}`);
      return;
    }

    debugLog(`[ok] webhook diterima Laravel untuk ${payload.context.from}`);
  } catch (error) {
    const message = error instanceof Error ? error.message : String(error);
    debugLog(`[exception] ${message}`);
  }
};

function loadWorkspaceEnv(workspaceDir) {
  const envPath = path.join(workspaceDir, ".env");
  if (!fs.existsSync(envPath)) {
    return {};
  }

  const content = fs.readFileSync(envPath, "utf8");
  const result = {};

  for (const rawLine of content.split(/\r?\n/)) {
    const line = rawLine.trim();
    if (!line || line.startsWith("#")) {
      continue;
    }

    const separatorIndex = line.indexOf("=");
    if (separatorIndex < 0) {
      continue;
    }

    const key = line.slice(0, separatorIndex).trim();
    let value = line.slice(separatorIndex + 1).trim();

    if (
      (value.startsWith('"') && value.endsWith('"')) ||
      (value.startsWith("'") && value.endsWith("'"))
    ) {
      value = value.slice(1, -1);
    }

    result[key] = value;
  }

  return result;
}

function resolveWebhookUrl(env) {
  const explicitFromProcess = process.env.OPENCLAW_LARAVEL_WEBHOOK_URL?.trim();
  if (explicitFromProcess) {
    return explicitFromProcess;
  }

  const explicit = env.OPENCLAW_LARAVEL_WEBHOOK_URL?.trim();
  if (explicit) {
    return explicit;
  }

  const appUrlFromProcess = process.env.APP_URL?.trim();
  if (appUrlFromProcess) {
    return `${appUrlFromProcess.replace(/\/+$/, "")}${DEFAULT_WEBHOOK_PATH}`;
  }

  const appUrl = env.APP_URL?.trim();
  if (!appUrl) {
    return DEFAULT_WEBHOOK_URL;
  }

  return `${appUrl.replace(/\/+$/, "")}${DEFAULT_WEBHOOK_PATH}`;
}

function resolveBridgeToken(env) {
  return (
    process.env.OPENCLAW_LARAVEL_BRIDGE_TOKEN?.trim() ||
    process.env.OPENCLAW_BRIDGE_TOKEN?.trim() ||
    env.OPENCLAW_LARAVEL_BRIDGE_TOKEN?.trim() ||
    env.OPENCLAW_BRIDGE_TOKEN?.trim() ||
    ""
  );
}

function normalizeSender(context) {
  const sender = normalizeString(
    context.from ??
      context.sender ??
      context.remoteJid ??
      getNested(context, ["metadata", "senderId"]) ??
      getNested(context, ["metadata", "remoteJid"]),
  );

  return sender.replace(/@.+$/, "");
}

function normalizeMessageType(value) {
  const normalized = value.toLowerCase();

  if (!normalized) {
    return "text";
  }

  if (normalized.includes("image")) {
    return "image";
  }

  if (normalized.includes("file") || normalized.includes("document")) {
    return "file";
  }

  return "text";
}

function normalizeString(value) {
  if (typeof value === "string") {
    return value.trim();
  }

  if (value == null) {
    return "";
  }

  return String(value).trim();
}

function normalizeBoolean(value) {
  if (typeof value === "boolean") {
    return value;
  }

  const normalized = normalizeString(value).toLowerCase();
  return ["1", "true", "yes"].includes(normalized);
}

function getNested(source, pathSegments) {
  let current = source;

  for (const segment of pathSegments) {
    if (!current || typeof current !== "object" || Array.isArray(current)) {
      return undefined;
    }

    current = current[segment];
  }

  return current;
}

function sanitizeMetadata(value) {
  if (!value || typeof value !== "object" || Array.isArray(value)) {
    return {};
  }

  return value;
}

function debugLog(message) {
  try {
    fs.mkdirSync(DEFAULT_LOG_DIR, { recursive: true });
    fs.appendFileSync(
      DEFAULT_LOG_FILE,
      `[${new Date().toISOString()}] ${message}\n`,
      "utf8",
    );
  } catch {
    // Ignore hook debug log failures.
  }
}

export default handler;
