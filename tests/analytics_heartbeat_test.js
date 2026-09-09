const fs = require('fs');
const assert = require('assert');
const embed = fs.readFileSync('app/Views/themes/pirate/embed.php', 'utf8');
const dashboard = fs.readFileSync('app/Views/admin/dashboard/index.php', 'utf8');
assert(!embed.includes('/traffic/embed') && !embed.includes('pingLiveTraffic'));
assert(!dashboard.includes('refreshLiveTraffic') && !dashboard.includes('/dashboard/live-traffic'));
assert(embed.includes('footer_custom_codes ()'), 'External analytics insertion must remain available');
console.log('PASS: no player heartbeat or dashboard traffic polling; custom analytics hook preserved.');

const vm = require('vm');
for (const match of dashboard.matchAll(/<script>([\s\S]*?)<\/script>/g)) {
  new vm.Script(match[1].replace(/<\?=[\s\S]*?\?>/g, 'null'));
}
