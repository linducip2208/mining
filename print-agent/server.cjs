/*
 * Mining ERP Print Agent
 * Windows 10/11, Node.js 18+.
 * Hardware access is intentionally local: Laravel never talks to Bluetooth/USB directly.
 */
const http = require('node:http');
const crypto = require('node:crypto');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { execFileSync } = require('node:child_process');

const configPath = process.env.MINING_AGENT_CONFIG || path.join(__dirname, 'config.json');
const config = JSON.parse(fs.readFileSync(configPath, 'utf8'));
const queuePath = path.join(__dirname, 'print-queue.json');
const queue = fs.existsSync(queuePath) ? JSON.parse(fs.readFileSync(queuePath, 'utf8')) : {};
const tempDir = path.join(os.tmpdir(), 'mining-erp-print-agent');
fs.mkdirSync(tempDir, { recursive: true });

function persist() { fs.writeFileSync(queuePath, JSON.stringify(queue, null, 2)); }
function json(res, status, body) { res.writeHead(status, { 'Content-Type': 'application/json; charset=utf-8', 'Access-Control-Allow-Origin': '*' }); res.end(JSON.stringify(body)); }
function loopback(req) { return req.socket.remoteAddress === '127.0.0.1' || req.socket.remoteAddress === '::1' || req.socket.remoteAddress === '::ffff:127.0.0.1'; }
function originAllowed(req) { const origin = req.headers.origin; return !origin || (config.allowedOrigins || []).includes(origin); }
function signatureValid(req, raw, method) {
  const timestamp = req.headers['x-mining-agent-timestamp'];
  const signature = req.headers['x-mining-agent-signature'];
  if (!timestamp || !signature || Math.abs(Math.floor(Date.now() / 1000) - Number(timestamp)) > 90) return false;
  const expected = crypto.createHmac('sha256', String(config.pairingToken)).update(`${timestamp}.${method}.${raw}`).digest('hex');
  const provided = Buffer.from(String(signature));
  return provided.length === expected.length && crypto.timingSafeEqual(Buffer.from(expected), provided);
}
function readBody(req) {
  return new Promise((resolve, reject) => {
    let body = ''; let bytes = 0;
    req.on('data', chunk => { bytes += chunk.length; if (bytes > 8 * 1024 * 1024) req.destroy(new Error('payload too large')); else body += chunk; });
    req.on('end', () => resolve(body)); req.on('error', reject);
  });
}
function printers() {
  try {
    const script = "Get-Printer | Select-Object Name,PrinterStatus,WorkOffline | ConvertTo-Json -Compress";
    const output = execFileSync('powershell.exe', ['-NoProfile', '-NonInteractive', '-Command', script], { encoding: 'utf8', timeout: 5000, windowsHide: true }).trim();
    if (!output) return [];
    const parsed = JSON.parse(output); return (Array.isArray(parsed) ? parsed : [parsed]).map(p => ({ name: String(p.Name), status: String(p.PrinterStatus || 'Unknown'), offline: Boolean(p.WorkOffline) }));
  } catch (_) { return []; }
}
function safePrinter(name) { return printers().some(p => p.name === name); }
function htmlToText(value) { return value.replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '').replace(/<script[^>]*>[\s\S]*?<\/script>/gi, '').replace(/<br\s*\/?>(\r?\n)?/gi, '\n').replace(/<\/p>/gi, '\n').replace(/<[^>]+>/g, '').replace(/&nbsp;/g, ' ').replace(/&amp;/g, '&').trim(); }
function printToWindows(printer, content, format) {
  const file = path.join(tempDir, `${crypto.randomUUID()}.${format === 'html' ? 'txt' : 'bin'}`);
  const text = format === 'html' ? htmlToText(content) : content;
  fs.writeFileSync(file, text, format === 'html' || format === 'text' ? 'utf8' : 'base64');
  // Out-Printer uses the Windows spooler and works for paired Bluetooth, USB, LAN,
  // and installed system printers. Printer names are supplied as process arguments,
  // never interpolated into a command string.
  const script = '$p=$args[0]; $f=$args[1]; Get-Content -LiteralPath $f -Raw | Out-Printer -Name $p';
  execFileSync('powershell.exe', ['-NoProfile', '-NonInteractive', '-Command', script, printer, file], { timeout: 15000, windowsHide: true });
  fs.rmSync(file, { force: true });
}
function printRawToWindows(printer, buffer) {
  const base64 = Buffer.from(buffer, 'latin1').toString('base64');
  const script = `
Add-Type -TypeDefinition @'
using System; using System.Runtime.InteropServices;
public static class MiningRawPrinter {
  [StructLayout(LayoutKind.Sequential, CharSet=CharSet.Unicode)] public class DocInfo { public string pDocName; public string pOutputFile; public string pDataType; }
  [DllImport("winspool.drv", SetLastError=true, CharSet=CharSet.Unicode)] public static extern bool OpenPrinter(string name, out IntPtr handle, IntPtr defaults);
  [DllImport("winspool.drv", SetLastError=true)] public static extern bool ClosePrinter(IntPtr handle);
  [DllImport("winspool.drv", SetLastError=true)] public static extern int StartDocPrinter(IntPtr handle, int level, [In] DocInfo info);
  [DllImport("winspool.drv", SetLastError=true)] public static extern bool EndDocPrinter(IntPtr handle);
  [DllImport("winspool.drv", SetLastError=true)] public static extern bool StartPagePrinter(IntPtr handle);
  [DllImport("winspool.drv", SetLastError=true)] public static extern bool EndPagePrinter(IntPtr handle);
  [DllImport("winspool.drv", SetLastError=true)] public static extern bool WritePrinter(IntPtr handle, byte[] data, int count, out int written);
}
'@
$h=[IntPtr]::Zero; if(-not [MiningRawPrinter]::OpenPrinter($args[0],[ref]$h,[IntPtr]::Zero)){throw 'Printer tidak dapat dibuka'}
try { $d=New-Object MiningRawPrinter+DocInfo; $d.pDocName='Mining ERP ESC/POS'; $d.pDataType='RAW'; [void][MiningRawPrinter]::StartDocPrinter($h,1,$d); [void][MiningRawPrinter]::StartPagePrinter($h); $b=[Convert]::FromBase64String($args[1]); $w=0; if(-not [MiningRawPrinter]::WritePrinter($h,$b,$b.Length,[ref]$w)){throw 'RAW spooler gagal'}; [void][MiningRawPrinter]::EndPagePrinter($h); [void][MiningRawPrinter]::EndDocPrinter($h) } finally { [void][MiningRawPrinter]::ClosePrinter($h) }
`;
  execFileSync('powershell.exe', ['-NoProfile', '-NonInteractive', '-Command', script, printer, base64], { timeout: 15000, windowsHide: true });
}
async function callback(job, status, errorMessage) {
  if (!job.callback_url || !/^https:\/\//i.test(job.callback_url)) return;
  const body = JSON.stringify({ status, error_message: errorMessage || null }); const timestamp = String(Math.floor(Date.now() / 1000));
  const signature = crypto.createHmac('sha256', String(config.pairingToken)).update(`${timestamp}.POST.${body}`).digest('hex');
  try { await fetch(job.callback_url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Mining-Agent-Timestamp': timestamp, 'X-Mining-Agent-Signature': signature }, body }); } catch (_) { /* local queue remains the source of retry */ }
}

const server = http.createServer(async (req, res) => {
  if (!loopback(req) || !originAllowed(req)) return json(res, 403, { error: 'Local Print Agent hanya menerima request localhost dari origin terdaftar.' });
  if (req.method === 'OPTIONS') { res.writeHead(204, { 'Access-Control-Allow-Origin': '*', 'Access-Control-Allow-Headers': 'Content-Type,X-Mining-Agent-Timestamp,X-Mining-Agent-Signature', 'Access-Control-Allow-Methods': 'GET,POST,OPTIONS' }); return res.end(); }
  if (req.method === 'GET' && req.url === '/health') return json(res, 200, { ok: true, service: 'Mining ERP Print Agent', version: '1.0.0', platform: process.platform });
  const raw = await readBody(req).catch(() => '');
  if (!signatureValid(req, raw, req.method)) return json(res, 401, { error: 'Signature print agent tidak valid atau sudah kedaluwarsa.' });
  if (req.method === 'GET' && req.url === '/printers') return json(res, 200, printers());
  if (req.method !== 'POST' || !['/print', '/print/raw', '/test'].includes(req.url)) return json(res, 404, { error: 'Endpoint tidak ditemukan.' });
  let job; try { job = JSON.parse(raw || '{}'); } catch (_) { return json(res, 400, { error: 'Payload tidak valid.' }); }
  if (!job.job_uuid || !/^[0-9a-f-]{20,}$/i.test(job.job_uuid)) return json(res, 422, { error: 'Job UUID wajib diisi.' });
  if (queue[job.job_uuid]?.status === 'PRINTED') return json(res, 200, { ok: true, duplicate: true, status: 'PRINTED' });
  if (!job.printer_name || !safePrinter(String(job.printer_name))) return json(res, 409, { error: 'Printer belum terdeteksi di Windows.' });
  if (urlIsRaw(req.url) && (!job.raw_base64 || job.raw_base64.length > 1024 * 1024)) return json(res, 422, { error: 'Payload ESC/POS tidak valid.' });
  queue[job.job_uuid] = { status: 'SENDING', printer: job.printer_name, updated_at: new Date().toISOString() }; persist(); await callback(job, 'SENDING');
  try {
    for (let copy = 0; copy < Math.min(3, Math.max(1, Number(job.copies || 1))); copy++) {
      if (urlIsRaw(req.url)) {
        let raw = Buffer.from(job.raw_base64, 'base64');
        if (job.auto_cut) raw = Buffer.concat([raw, Buffer.from([0x1d, 0x56, 0x00])]);
        printRawToWindows(String(job.printer_name), raw.toString('latin1'));
      } else {
        printToWindows(String(job.printer_name), Buffer.from(job.content_base64 || '', 'base64').toString('utf8'), job.format || 'html');
      }
    }
    queue[job.job_uuid] = { ...queue[job.job_uuid], status: 'PRINTED', updated_at: new Date().toISOString() }; persist(); await callback(job, 'PRINTED'); return json(res, 200, { ok: true, status: 'PRINTED' });
  } catch (error) { queue[job.job_uuid] = { ...queue[job.job_uuid], status: 'FAILED', error_message: 'Windows spooler menolak print job.', updated_at: new Date().toISOString() }; persist(); await callback(job, 'FAILED', 'Windows spooler menolak print job.'); return json(res, 502, { error: 'Windows spooler menolak print job.' }); }
});
function urlIsRaw(url) { return url === '/print/raw'; }
server.listen(Number(config.port || 17845), '127.0.0.1', () => console.log(`Mining ERP Print Agent listening on 127.0.0.1:${config.port || 17845}`));
