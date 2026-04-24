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
      bodyForAgent: extractBodyForAgent(context),
      messageType: normalizeMessageType(
        normalizeString(context.messageType ?? context.type),
        context,
      ),
      messageId: normalizeString(
        context.messageId ??
          context.id ??
          getNested(context, ["metadata", "messageId"]) ??
          getNested(context, ["metadata", "id"]),
      ),
      mediaUrl: extractMediaUrl(context),
      mediaPath: extractMediaPath(context),
      attachment: extractPrimaryAttachment(context),
      attachments: extractAttachments(context),
      metadata: sanitizeMetadata({
        ...(context.metadata && typeof context.metadata === "object" ? context.metadata : {}),
        attachment: extractPrimaryAttachment(context),
        attachments: extractAttachments(context),
      }),
    },
  };

  if (!payload.context.from || !payload.context.bodyForAgent) {
    debugLog(
      `[skip] payload tidak lengkap. from=${payload.context.from || "-"} content=${payload.context.bodyForAgent || "-"}`,
    );
    return;
  }

  try {
    if (shouldLogMediaDebug(payload.context)) {
      debugLog(`[media-debug] ${JSON.stringify(buildMediaDebugSnapshot(context, payload.context))}`);
    }

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

function normalizeMessageType(value, context = {}) {
  const normalized = value.toLowerCase();

  if (normalized.includes("image")) {
    return "image";
  }

  if (normalized.includes("file") || normalized.includes("document")) {
    return "file";
  }

  const attachmentType = inferAttachmentType(context);
  if (attachmentType === "image") {
    return "image";
  }

  if (attachmentType === "file") {
    return "file";
  }

  return "text";
}

function extractBodyForAgent(context) {
  const directBody = normalizeString(
    context.bodyForAgent ?? context.content ?? context.text ?? context.body,
  );

  if (directBody) {
    return directBody;
  }

  const attachmentType = inferAttachmentType(context);
  if (attachmentType === "image") {
    return "<media:image>";
  }

  if (attachmentType === "file") {
    return "<media:file>";
  }

  return "";
}

function extractMediaUrl(context) {
  const candidates = [
    context.mediaUrl,
    context.fileUrl,
    context.url,
    getNested(context, ["media", "url"]),
    getNested(context, ["media", "mediaUrl"]),
    getNested(context, ["media", "fileUrl"]),
    getNested(context, ["attachment", "url"]),
    getNested(context, ["attachment", "mediaUrl"]),
    getNested(context, ["attachment", "fileUrl"]),
    getNested(context, ["attachment", "downloadUrl"]),
    getNested(context, ["message", "mediaUrl"]),
    getNested(context, ["message", "fileUrl"]),
    getNested(context, ["message", "attachment", "url"]),
    getNested(context, ["message", "attachment", "mediaUrl"]),
    getNested(context, ["message", "attachment", "fileUrl"]),
    getNested(context, ["metadata", "mediaUrl"]),
    getNested(context, ["metadata", "fileUrl"]),
    getNested(context, ["metadata", "attachment", "url"]),
    getNested(context, ["metadata", "attachment", "mediaUrl"]),
    getNested(context, ["metadata", "attachment", "fileUrl"]),
    getNested(context, ["metadata", "attachment", "downloadUrl"]),
    getNested(context, ["attachments", 0, "url"]),
    getNested(context, ["attachments", 0, "mediaUrl"]),
    getNested(context, ["attachments", 0, "fileUrl"]),
    getNested(context, ["attachments", 0, "downloadUrl"]),
    getNested(context, ["message", "attachments", 0, "url"]),
    getNested(context, ["message", "attachments", 0, "mediaUrl"]),
    getNested(context, ["message", "attachments", 0, "fileUrl"]),
    getNested(context, ["message", "attachments", 0, "downloadUrl"]),
    getNested(context, ["metadata", "attachments", 0, "url"]),
    getNested(context, ["metadata", "attachments", 0, "mediaUrl"]),
    getNested(context, ["metadata", "attachments", 0, "fileUrl"]),
    getNested(context, ["metadata", "attachments", 0, "downloadUrl"]),
  ];

  for (const candidate of candidates) {
    const value = normalizeString(candidate);
    if (value) {
      return value;
    }
  }

  return "";
}

function extractMediaPath(context) {
  const candidates = [
    context.mediaPath,
    context.filePath,
    context.localPath,
    context.savedPath,
    getNested(context, ["media", "path"]),
    getNested(context, ["media", "filePath"]),
    getNested(context, ["media", "localPath"]),
    getNested(context, ["attachment", "path"]),
    getNested(context, ["attachment", "filePath"]),
    getNested(context, ["attachment", "localPath"]),
    getNested(context, ["attachment", "savedPath"]),
    getNested(context, ["message", "attachment", "path"]),
    getNested(context, ["message", "attachment", "filePath"]),
    getNested(context, ["message", "attachments", 0, "path"]),
    getNested(context, ["message", "attachments", 0, "filePath"]),
    getNested(context, ["metadata", "attachment", "path"]),
    getNested(context, ["metadata", "attachment", "filePath"]),
    getNested(context, ["metadata", "attachments", 0, "path"]),
    getNested(context, ["metadata", "attachments", 0, "filePath"]),
    getNested(context, ["attachments", 0, "path"]),
    getNested(context, ["attachments", 0, "filePath"]),
  ];

  for (const candidate of candidates) {
    const value = normalizeString(candidate);
    if (value) {
      return value;
    }
  }

  return "";
}

function extractAttachments(context) {
  const rawCandidates = [
    context.attachments,
    getNested(context, ["message", "attachments"]),
    getNested(context, ["metadata", "attachments"]),
  ];

  for (const candidate of rawCandidates) {
    if (Array.isArray(candidate) && candidate.length > 0) {
      return candidate
        .map((item) => sanitizeAttachment(item))
        .filter(Boolean);
    }
  }

  const singleAttachment = extractPrimaryAttachment(context);
  return singleAttachment ? [singleAttachment] : [];
}

function extractPrimaryAttachment(context) {
  const candidates = [
    context.attachment,
    context.media,
    getNested(context, ["message", "attachment"]),
    getNested(context, ["metadata", "attachment"]),
    Array.isArray(context.attachments) ? context.attachments[0] : undefined,
    Array.isArray(getNested(context, ["message", "attachments"]))
      ? getNested(context, ["message", "attachments"])[0]
      : undefined,
    Array.isArray(getNested(context, ["metadata", "attachments"]))
      ? getNested(context, ["metadata", "attachments"])[0]
      : undefined,
  ];

  for (const candidate of candidates) {
    const sanitized = sanitizeAttachment(candidate);
    if (sanitized) {
      return sanitized;
    }
  }

  const mediaUrl = extractMediaUrl(context);
  if (!mediaUrl) {
    return null;
  }

  return {
    url: mediaUrl,
    mediaUrl,
    type: inferAttachmentType(context),
  };
}

function sanitizeAttachment(value) {
  if (!value || typeof value !== "object" || Array.isArray(value)) {
    return null;
  }

  const sanitized = {};
  const allowedKeys = [
    "url",
    "mediaUrl",
    "fileUrl",
    "downloadUrl",
    "path",
    "filePath",
    "localPath",
    "savedPath",
    "tempPath",
    "proxyUrl",
    "mimeType",
    "contentType",
    "type",
    "kind",
    "name",
    "filename",
    "fileName",
    "id",
  ];

  for (const key of allowedKeys) {
    const normalized = value[key];
    if (normalized == null || normalized === "") {
      continue;
    }

    sanitized[key] = normalized;
  }

  return Object.keys(sanitized).length > 0 ? sanitized : null;
}

function inferAttachmentType(context) {
  const directType = normalizeString(context.messageType ?? context.type).toLowerCase();
  if (directType.includes("image")) {
    return "image";
  }
  if (directType.includes("file") || directType.includes("document")) {
    return "file";
  }

  const candidates = [
    context.attachment,
    context.media,
    Array.isArray(context.attachments) ? context.attachments[0] : undefined,
    getNested(context, ["message", "attachment"]),
    Array.isArray(getNested(context, ["message", "attachments"]))
      ? getNested(context, ["message", "attachments"])[0]
      : undefined,
    getNested(context, ["metadata", "attachment"]),
    Array.isArray(getNested(context, ["metadata", "attachments"]))
      ? getNested(context, ["metadata", "attachments"])[0]
      : undefined,
  ];

  for (const candidate of candidates) {
    const kind = normalizeString(
      candidate?.type ??
        candidate?.kind ??
        candidate?.mimeType ??
        candidate?.contentType,
    ).toLowerCase();

    if (kind.includes("image")) {
      return "image";
    }

    if (kind) {
      return "file";
    }
  }

  const mediaUrl = extractMediaUrl(context).toLowerCase();
  if (/\.(png|jpe?g|gif|webp|bmp|svg)(\?|$)/.test(mediaUrl)) {
    return "image";
  }

  if (mediaUrl) {
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
    if (!current || typeof current !== "object") {
      return undefined;
    }

    if (Array.isArray(current)) {
      const index =
        typeof segment === "number" ? segment : Number.parseInt(segment, 10);
      if (!Number.isInteger(index) || index < 0 || index >= current.length) {
        return undefined;
      }
      current = current[index];
      continue;
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

function shouldLogMediaDebug(context) {
  return Boolean(
    context.mediaUrl ||
      context.mediaPath ||
      context.messageType === "image" ||
      context.messageType === "file" ||
      String(context.bodyForAgent || "").startsWith("<media:") ||
      (Array.isArray(context.attachments) && context.attachments.length > 0),
  );
}

function buildMediaDebugSnapshot(rawContext, normalizedContext) {
  return {
    from: normalizedContext.from,
    messageType: normalizedContext.messageType,
    bodyForAgent: normalizedContext.bodyForAgent,
    mediaUrl: normalizedContext.mediaUrl || "",
    mediaPath: normalizedContext.mediaPath || "",
    attachment: normalizedContext.attachment || null,
    attachments: normalizedContext.attachments || [],
    rawAttachment: sanitizeAttachment(rawContext?.attachment),
    rawAttachments: Array.isArray(rawContext?.attachments)
      ? rawContext.attachments.map((item) => sanitizeAttachment(item)).filter(Boolean)
      : [],
    rawMetadataAttachment: sanitizeAttachment(getNested(rawContext, ["metadata", "attachment"])),
    rawMetadataAttachments: Array.isArray(getNested(rawContext, ["metadata", "attachments"]))
      ? getNested(rawContext, ["metadata", "attachments"]).map((item) => sanitizeAttachment(item)).filter(Boolean)
      : [],
  };
}

export default handler;
