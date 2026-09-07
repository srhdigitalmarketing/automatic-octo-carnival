const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const template = fs.readFileSync('app/Views/themes/pirate/embed.php', 'utf8');
const start = template.indexOf('    var analyticsPingPending = false;');
const end = template.indexOf('    pingLiveTraffic();', start);
const source = template.slice(start, end);
let resolve;
let requests = 0;
const context = {document: {visibilityState: 'visible'}, shouldRecordDaily: true,
  sendAnalytics: () => { requests++; return new Promise(r => {resolve = r;}); }};
vm.createContext(context);
vm.runInContext(source, context);
(async () => {
  context.pingLiveTraffic(); context.pingLiveTraffic();
  assert.strictEqual(requests, 1, 'concurrent heartbeat must be suppressed');
  resolve({ok: true, json: async () => ({ok: true})});
  await new Promise(r => setImmediate(r));
  assert.strictEqual(context.shouldRecordDaily, false, 'acknowledged impression must not repeat');
  context.document.visibilityState = 'hidden'; context.pingLiveTraffic();
  assert.strictEqual(requests, 1, 'hidden player must not heartbeat');
  context.document.visibilityState = 'visible'; context.pingLiveTraffic();
  resolve({ok: false}); await new Promise(r => setImmediate(r));
  assert.strictEqual(context.analyticsPingPending, false, 'failed response must release pending guard');
  console.log('PASS: heartbeat concurrency, impression acknowledgment, visibility and failed responses.');
})().catch(e => { console.error(e); process.exitCode = 1; });
