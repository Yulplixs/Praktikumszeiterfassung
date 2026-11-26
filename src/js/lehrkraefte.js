// Seite "Lehrkräfte": anlegen, bearbeiten, löschen (nur ohne Einträge), Stunden je Halbjahr.
// Hilfsfunktionen ($, api, esc, stunden, normalisiere) kommen aus gemeinsam.js.

let lehrkraefte = [];
let bearbeiteId = null;

const vollerName = (l) => [l.title, l.firstname, l.surname].filter(Boolean).join(' ');

async function ladeLehrkraefte() {
  lehrkraefte = await api(`lehrkraefte.php?school_year_id=${encodeURIComponent($('filter-schuljahr').value)}`);
  zeige();
}

function zeige() {
  const suche = normalisiere($('suche').value.trim());
  const auswahl = lehrkraefte.filter((l) => normalisiere(`${l.code} ${vollerName(l)}`).includes(suche));
  const summe = (feld) => auswahl.reduce((s, l) => s + Number(l[feld]), 0);

  $('tabelle-anzahl').textContent = auswahl.length;
  $('tabelle-zeilen').innerHTML = auswahl.length
    ? auswahl.map((l) => {
      const jahr = Number(l.hours_half_year_1) + Number(l.hours_half_year_2);
      // Löschen nur ohne Einträge in irgendeinem Schuljahr (Fremdschlüssel in supervision_entries).
      const loeschbar = Number(l.entry_count_all) === 0;
      return `
      <tr>
        <td><span class="kuerzel">${esc(l.code)}</span></td>
        <td class="fett">${esc(vollerName(l)) || '<span class="text-gedaempft">Name fehlt</span>'}</td>
        <td class="rechts">${esc(l.entry_count)}</td>
        <td class="rechts">${stunden(l.hours_half_year_1)}</td>
        <td class="rechts">${stunden(l.hours_half_year_2)}</td>
        <td class="rechts fett">${stunden(jahr)}</td>
        <td class="aktion-spalte">
          <button class="icon-button" type="button" data-bearbeiten="${esc(l.id)}" aria-label="Lehrkraft ${esc(l.code)} bearbeiten"><svg class="icon"><use href="#i-pencil"/></svg></button>
          <button class="icon-button" type="button" data-loeschen="${esc(l.id)}" ${loeschbar ? '' : 'disabled'}
            aria-label="Lehrkraft ${esc(l.code)} löschen" title="${loeschbar ? 'Löschen' : 'Hat Einträge, Löschen nicht möglich'}"><svg class="icon"><use href="#i-trash"/></svg></button>
        </td>
      </tr>`;
    }).join('')
    : '<tr><td colspan="7" class="text-gedaempft">Keine Lehrkräfte für diese Suche.</td></tr>';
  $('summe-hj1').textContent = stunden(summe('hours_half_year_1'));
  $('summe-hj2').textContent = stunden(summe('hours_half_year_2'));
  $('summe-jahr').textContent = stunden(summe('hours_half_year_1') + summe('hours_half_year_2'));
}

function setzeModus(lehrkraft) {
  const formular = $('formular');
  bearbeiteId = lehrkraft ? lehrkraft.id : null;
  $('formular-titel').textContent = lehrkraft ? `Lehrkraft ${lehrkraft.code} bearbeiten` : 'Neue Lehrkraft';
  $('formular-knopf').textContent = lehrkraft ? 'Änderungen speichern' : 'Lehrkraft anlegen';
  $('formular-abbrechen').hidden = !lehrkraft;
  for (const feld of ['code', 'title', 'firstname', 'surname']) {
    formular[feld].value = lehrkraft ? lehrkraft[feld] : '';
  }
  if (lehrkraft) {
    formular.scrollIntoView({ behavior: 'smooth', block: 'center' });
    formular.code.focus({ preventScroll: true });
  }
}

async function speichere(ereignis) {
  ereignis.preventDefault();
  const werte = Object.fromEntries(new FormData(ereignis.target));
  const bearbeiten = bearbeiteId !== null;
  $('formular-status').textContent = 'Speichere …';
  try {
    const ergebnis = await sendeJson(
      bearbeiten ? `lehrkraefte.php?id=${bearbeiteId}` : 'lehrkraefte.php', bearbeiten ? 'PUT' : 'POST', werte
    );
    setzeModus(null);
    $('formular-status').textContent = `${bearbeiten ? 'Geändert' : 'Angelegt'}: ${ergebnis.code}`;
    await ladeLehrkraefte();
  } catch (fehler) {
    $('formular-status').textContent = `Fehler: ${fehler.message}`;
  }
}

async function loesche(id) {
  const lehrkraft = lehrkraefte.find((l) => String(l.id) === id);
  if (!confirm(`Lehrkraft ${lehrkraft.code} ${vollerName(lehrkraft)} endgültig löschen?`)) {
    return;
  }
  try {
    await api(`lehrkraefte.php?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
    if (String(bearbeiteId) === id) setzeModus(null);
    $('formular-status').textContent = `Gelöscht: ${lehrkraft.code}`;
    await ladeLehrkraefte();
  } catch (fehler) {
    alert(`Löschen fehlgeschlagen: ${fehler.message}`);
  }
}

function zeigeFehler(fehler) {
  $('tabelle-zeilen').innerHTML = `<tr><td colspan="7">Daten konnten nicht geladen werden: ${esc(fehler.message)}</td></tr>`;
}

$('filter-schuljahr').addEventListener('change', () => ladeLehrkraefte().catch(zeigeFehler));
$('suche').addEventListener('input', zeige);
$('formular').addEventListener('submit', speichere);
$('formular-abbrechen').addEventListener('click', () => {
  setzeModus(null);
  $('formular-status').textContent = 'Noch nicht gespeichert';
});
$('tabelle-zeilen').addEventListener('click', (ereignis) => {
  const knopf = ereignis.target.closest('button');
  if (!knopf) return;
  if (knopf.dataset.bearbeiten) {
    setzeModus(lehrkraefte.find((l) => String(l.id) === knopf.dataset.bearbeiten));
  } else if (knopf.dataset.loeschen) {
    loesche(knopf.dataset.loeschen);
  }
});

ladeSchuljahre($('filter-schuljahr')).then(ladeLehrkraefte).catch(zeigeFehler);
