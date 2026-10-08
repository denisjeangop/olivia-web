// Cloudflare Pages Function: /out
// Variables en Cloudflare (Settings → Variables and Secrets):
//   DEST_URL          → tu enlace real (OnlyFans, Fanvue…). Nunca aparece en la web.
//   TURNSTILE_SECRET  → Secret Key de Turnstile (opcional pero recomendado).
const BOTS = /bot|crawl|spider|slurp|facebookexternalhit|facebot|meta-external|whatsapp|telegram|twitter|discord|slack|linkedin|embedly|preview|headless|python|curl|wget|go-http|java\/|okhttp|axios|node-fetch|scrapy|httpclient|libwww|phantom|selenium|puppeteer|playwright/i;

export async function onRequest({ request, env }) {
  const url = new URL(request.url);
  const ua = request.headers.get('user-agent') || '';
  const cookie = request.headers.get('cookie') || '';
  const noStore = { 'Cache-Control': 'no-store', 'X-Robots-Tag': 'noindex, nofollow' };
  const home = () => new Response(null, { status: 302, headers: { Location: url.origin + '/', ...noStore } });

  if (!env.DEST_URL || !ua || BOTS.test(ua)) return home();
  if (!/(?:^|;\s*)h=[a-z0-9]+/.test(cookie)) return home();          // no pasó por los dos botones

  if (env.TURNSTILE_SECRET) {                                         // verificación anti-bot de Cloudflare
    const token = url.searchParams.get('t');
    if (!token) return home();
    const body = new FormData();
    body.append('secret', env.TURNSTILE_SECRET);
    body.append('response', token);
    body.append('remoteip', request.headers.get('CF-Connecting-IP') || '');
    const r = await fetch('https://challenges.cloudflare.com/turnstile/v0/siteverify', { method: 'POST', body });
    const j = await r.json().catch(() => ({}));
    if (!j.success || (j.hostname && j.hostname !== url.hostname)) return home();
  }

  return new Response(null, {
    status: 302,
    headers: { Location: env.DEST_URL, 'Referrer-Policy': 'no-referrer', 'Set-Cookie': 'h=; Max-Age=0; Path=/', ...noStore },
  });
}
