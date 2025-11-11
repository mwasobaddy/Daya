// Simple WCAG contrast checker for selected colors
function hexToRgb(hex) {
  hex = hex.replace('#', '');
  if (hex.length === 3) hex = hex.split('').map((c) => c + c).join('');
  const bigint = parseInt(hex, 16);
  const r = (bigint >> 16) & 255;
  const g = (bigint >> 8) & 255;
  const b = bigint & 255;
  return [r, g, b];
}

function srgbChannel(c) {
  c = c / 255;
  return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
}

function luminance(hex) {
  const [r, g, b] = hexToRgb(hex);
  return 0.2126 * srgbChannel(r) + 0.7152 * srgbChannel(g) + 0.0722 * srgbChannel(b);
}

function contrast(hex1, hex2) {
  const L1 = luminance(hex1);
  const L2 = luminance(hex2);
  const lighter = Math.max(L1, L2);
  const darker = Math.min(L1, L2);
  return +( (lighter + 0.05) / (darker + 0.05) ).toFixed(2);
}

const colors = {
  // light mode
  bg_light: '#F7FAFC',
  surface_light: '#FFFFFF',
  text_light: '#0F172A',
  role_client: '#4F46E5',
  role_dcd: '#0F766E',
  role_da: '#B45309',
  // dark mode
  bg_dark: '#0B1220',
  surface_dark: '#0F1724',
  text_dark: '#E6EEF6',
  role_client_dark: '#4B46EF',
  role_dcd_dark: '#2DD4BF',
  role_da_dark: '#F59E0B'
};

function check() {
  console.log('WCAG contrast checks:\n');

  const checks = [
    { a: 'text_light', b: 'bg_light', desc: 'Primary text on page background (light)' },
    { a: 'text_light', b: 'surface_light', desc: 'Primary text on surface (light)' },
    { a: 'role_client', b: 'text_light', desc: 'Client accent vs text (light) - white text on accent will be checked separately' },
    { a: 'role_client', b: 'bg_light', desc: 'Client accent on page background (light)' },
    { a: 'role_dcd', b: 'bg_light', desc: 'DCD accent on page background (light)' },
    { a: 'role_da', b: 'bg_light', desc: 'DA accent on page background (light)' },

    { a: 'text_dark', b: 'bg_dark', desc: 'Primary text on page background (dark)' },
    { a: 'text_dark', b: 'surface_dark', desc: 'Primary text on surface (dark)' },
    { a: 'role_client_dark', b: 'text_dark', desc: 'Client accent (dark) vs text (dark)' },
    { a: 'role_client_dark', b: 'bg_dark', desc: 'Client accent on page background (dark)' },
    { a: 'role_dcd_dark', b: 'bg_dark', desc: 'DCD accent on page background (dark)' },
    { a: 'role_da_dark', b: 'bg_dark', desc: 'DA accent on page background (dark)' }
  ];

  checks.forEach((c) => {
    const ratio = contrast(colors[c.a], colors[c.b]);
    console.log(`${c.desc}: ${colors[c.a]} vs ${colors[c.b]} => contrast ${ratio}:1 ${ratio>=4.5? '(PASS)': '(FAIL)'} `);
  });

  console.log('\nCTA/button text contrast (white on accents - light)');
  ['role_client', 'role_dcd', 'role_da'].forEach((k) => {
    const r = contrast('#ffffff', colors[k]);
    console.log(`white on ${colors[k]} => ${r}:1 ${r>=4.5? '(PASS)': '(FAIL)'} `);
  });

  console.log('\nCTA/button text contrast (text_dark on light accents - dark mode)');
  [['#0B1220', 'role_client_dark'], ['#0B1220', 'role_dcd_dark'], ['#0B1220', 'role_da_dark']].forEach(([bg, accentKey]) => {
    const r = contrast('#0B1220', colors[accentKey]);
    console.log(`#0B1220 on ${colors[accentKey]} => ${r}:1 ${r>=4.5? '(PASS)': '(FAIL)'} `);
  });
}

check();

// Suggested alternatives to fix failures: test a few darker/lighter variants
console.log('\nTesting candidate adjustments to meet 4.5:1 for CTA text (white on accent) and dark text on light accents');
const candidates = {
  dcd_alts: ['#0D9488', '#0F766E', '#0B8F8A'],
  client_dark_alts: ['#4F46E5', '#4C51BF', '#3F3DD7']
};

function testCandidates() {
  console.log('\nDCD alternatives (white on accent):');
  candidates.dcd_alts.forEach((c) => {
    console.log(`${c} => white on ${c}: ${contrast('#ffffff', c)}:1 ${contrast('#ffffff', c) >= 4.5 ? '(PASS)' : '(FAIL)'}`);
  });

  console.log('\nClient dark alternatives (#0B1220 on accent):');
  candidates.client_dark_alts.forEach((c) => {
    console.log(`${c} => #0B1220 on ${c}: ${contrast('#0B1220', c)}:1 ${contrast('#0B1220', c) >= 4.5 ? '(PASS)' : '(FAIL)'}`);
  });
}

testCandidates();

// Try a broader set for client dark to find a value that meets both:  white on accent >=4.5 and accent on dark bg >=3.0
const client_search = ['#3f3dd7','#4340df','#4743e7','#4b46ef','#4f46e5','#534af0','#5850f8','#5c54ff','#6160ff','#6a69ff'];
console.log('\nSearching client dark candidates for both white-on-accent >=4.5 AND accent-on-dark-bg >=3.0');
for (const c of client_search) {
  const whiteOn = contrast('#ffffff', c);
  const accentOnBg = contrast(c, '#0B1220');
  const pass = whiteOn >= 4.5 && accentOnBg >= 3.0;
  console.log(`${c}: white on ${c} => ${whiteOn}:1, ${c} on #0B1220 => ${accentOnBg}:1 => ${pass ? 'OK' : 'no'}`);
}
