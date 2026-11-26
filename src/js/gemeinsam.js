// Hilfsfunktionen für alle Seiten. Wird vor dem seitenspezifischen Skript geladen.

const API = '../api/';
const $ = (id) => document.getElementById(id);

const stunden = (wert) =>
  Number(wert).toLocaleString('de-DE', { minimumFractionDigits: 3, maximumFractionDigits: 3 }) + ' h';

// Faktor und Wochen: bis zu 4 Nachkommastellen wie in der DB, ohne überflüssige Nullen.
const zahl = (wert) => Number(wert).toLocaleString('de-DE', { maximumFractionDigits: 4 });

const esc = (text) =>
  String(text ?? '').replace(/[&<>"']/g, (z) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[z]);

// Vergleich ohne Groß-/Kleinschreibung und ohne Akzente: "boh" findet "BÖH", "muller" findet "Müller".
const normalisiere = (text) => text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

async function api(pfad, optionen) {
  const antwort = await fetch(API + pfad, optionen);
  const daten = await antwort.json().catch(() => ({}));
  if (!antwort.ok) {
    throw new Error(daten.fehler || `Serverfehler (${antwort.status})`);
  }
  return daten;
}

const sendeJson = (pfad, methode, daten) =>
  api(pfad, { method: methode, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(daten) });

async function ladeSchuljahre(select) {
  const daten = await api('stammdaten.php');
  select.innerHTML = daten.schuljahre
    .map((s) => `<option value="${esc(s.id)}">Schuljahr ${esc(s.name)}</option>`).join('');
  return daten;
}
