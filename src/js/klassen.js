// Seite "Klassen": Faktor und Wochen je Klasse anzeigen und bearbeiten, Stunden je Halbjahr.
// Hilfsfunktionen ($, api, esc, stunden, zahl, normalisiere) kommen aus gemeinsam.js.

let klassen = [];
let bearbeiteId = null;

async function ladeKlassen() {
  klassen = await api(`klassen.php?school_year_id=${encodeURIComponent($('filter-schuljahr').value)}`);
  zeige();
}

function zeige() {
  const suche = normalisiere($('suche').value.trim());
  const auswahl = klassen.filter((k) => normalisiere(`${k.code} ${k.name}`).includes(suche));
  // Ohne Klassenwerte im Schuljahr (kein class_rules-Eintrag) liefert die API null.
  const wert = (w) => (w === null ? '–' : zahl(w));

  $('tabelle-anzahl').textContent = auswahl.length;
  $('tabelle-zeilen').innerHTML = auswahl.length
    ? auswahl.map((k) => `
      <tr>
        <td class="fett">${esc(k.code)}</td>
        <td>${esc(k.name)}</td>
        <td class="rechts">${wert(k.supervision_factor)}</td>
        <td class="rechts">${wert(k.weeks_total)}</td>
        <td class="rechts">${wert(k.weeks_half_year_1)}</td>
        <td class="rechts">${wert(k.weeks_half_year_2)}</td>
        <td class="rechts">${esc(k.entry_count)}</td>
        <td class="rechts">${stunden(k.hours_half_year_1)}</td>
        <td class="rechts">${stunden(k.hours_half_year_2)}</td>
        <td class="aktion-spalte">
          <button class="icon-button" type="button" data-bearbeiten="${esc(k.id)}" aria-label="Klasse ${esc(k.code)} bearbeiten"><svg class="icon"><use href="#i-pencil"/></svg></button>
        </td>
      </tr>`).join('')
    : '<tr><td colspan="10" class="text-gedaempft">Keine Klassen für diese Suche.</td></tr>';
  $('summe-hj1').textContent = stunden(auswahl.reduce((s, k) => s + Number(k.hours_half_year_1), 0));
  $('summe-hj2').textContent = stunden(auswahl.reduce((s, k) => s + Number(k.hours_half_year_2), 0));
}

function bearbeite(klasse) {
  const formular = $('formular');
  bearbeiteId = klasse ? klasse.id : null;
  formular.hidden = !klasse;
  if (!klasse) return;
  $('formular-titel').textContent = `Klassenwerte ${klasse.code} bearbeiten`;
  $('formular-status').textContent = klasse.name;
  for (const feld of ['supervision_factor', 'weeks_total', 'weeks_half_year_1', 'weeks_half_year_2']) {
    formular[feld].value = klasse[feld] === null ? 0 : Number(klasse[feld]);
  }
  formular.scrollIntoView({ behavior: 'smooth', block: 'center' });
  formular.supervision_factor.focus({ preventScroll: true });
}

async function speichere(ereignis) {
  ereignis.preventDefault();
  const werte = Object.fromEntries(new FormData(ereignis.target));
  $('formular-status').textContent = 'Speichere …';
  try {
    await sendeJson(
      `klassen.php?class_id=${bearbeiteId}&school_year_id=${encodeURIComponent($('filter-schuljahr').value)}`, 'PUT', werte
    );
    bearbeite(null);
    await ladeKlassen();
  } catch (fehler) {
    $('formular-status').textContent = `Fehler: ${fehler.message}`;
  }
}

function zeigeFehler(fehler) {
  $('tabelle-zeilen').innerHTML = `<tr><td colspan="10">Daten konnten nicht geladen werden: ${esc(fehler.message)}</td></tr>`;
}

$('filter-schuljahr').addEventListener('change', () => {
  bearbeite(null);
  ladeKlassen().catch(zeigeFehler);
});
$('suche').addEventListener('input', zeige);
$('formular').addEventListener('submit', speichere);
$('formular-abbrechen').addEventListener('click', () => bearbeite(null));
$('tabelle-zeilen').addEventListener('click', (ereignis) => {
  const knopf = ereignis.target.closest('button[data-bearbeiten]');
  if (knopf) bearbeite(klassen.find((k) => String(k.id) === knopf.dataset.bearbeiten));
});

ladeSchuljahre($('filter-schuljahr')).then(ladeKlassen).catch(zeigeFehler);
