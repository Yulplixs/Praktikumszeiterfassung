// Seite "Betreuungszeiten": lädt Stammdaten und Einträge aus der API, filtert, blättert,
// legt Einträge an, bearbeitet und löscht sie und startet den Excel-Export.

const API = '../api/';
const PRO_SEITE = 25;
const $ = (id) => document.getElementById(id);

let eintraege = [];
let zusammenfassung = { teachers: [], totals: {} };
let seite = 1;
let bearbeiteId = null;

const stunden = (wert) =>
  Number(wert).toLocaleString('de-DE', { minimumFractionDigits: 3, maximumFractionDigits: 3 }) + ' h';

const esc = (text) =>
  String(text ?? '').replace(/[&<>"']/g, (z) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[z]);

async function api(pfad, optionen) {
  const antwort = await fetch(API + pfad, optionen);
  const daten = await antwort.json().catch(() => ({}));
  if (!antwort.ok) {
    throw new Error(daten.fehler || `Serverfehler (${antwort.status})`);
  }
  return daten;
}

function optionen(select, liste, wert, text) {
  select.insertAdjacentHTML(
    'beforeend',
    liste.map((e) => `<option value="${esc(e[wert])}">${esc(text(e))}</option>`).join('')
  );
}

// Vergleich ohne Groß-/Kleinschreibung und ohne Akzente: "boh" findet "BÖH", "muller" findet "Müller".
const normalisiere = (text) => text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

// Macht aus einem <select> ein Dropdown mit Suche (ARIA-Combobox). Das <select> bleibt versteckt
// als Wertspeicher, damit Formular, Filter und change-Ereignisse unverändert funktionieren.
// Bedienung: hineinklicken und tippen, Pfeiltasten, Enter wählt, Esc bricht ab.
let comboboxNr = 0;
function macheCombobox(select) {
  const listenId = `combobox-liste-${++comboboxNr}`;
  const huelle = document.createElement('span');
  huelle.className = 'combobox';
  huelle.innerHTML = `
    <input class="eingabe" type="text" role="combobox" autocomplete="off" spellcheck="false"
      aria-autocomplete="list" aria-expanded="false" aria-controls="${listenId}" placeholder="Name oder Kürzel eingeben …" />
    <ul class="combobox-liste" id="${listenId}" role="listbox" hidden></ul>`;
  select.before(huelle);
  select.hidden = true;
  const feld = huelle.querySelector('input');
  const liste = huelle.querySelector('ul');
  let treffer = [];
  let aktiv = -1;

  const zeigeAuswahl = () => { feld.value = select.selectedOptions[0]?.textContent ?? ''; };

  function markiere() {
    [...liste.children].forEach((li, i) => li.classList.toggle('aktiv', i === aktiv));
    if (aktiv >= 0) {
      feld.setAttribute('aria-activedescendant', `${listenId}-${aktiv}`);
      liste.children[aktiv].scrollIntoView({ block: 'nearest' });
    } else {
      feld.removeAttribute('aria-activedescendant');
    }
  }

  function oeffne(begriff) {
    const suche = normalisiere(begriff);
    treffer = [...select.options].filter((o) => normalisiere(o.textContent).includes(suche));
    // Ohne Suchbegriff die aktuelle Auswahl markieren, mit Suchbegriff den ersten Treffer.
    aktiv = suche ? (treffer.length ? 0 : -1) : treffer.findIndex((o) => o.selected);
    liste.innerHTML = treffer.length
      ? treffer.map((o, i) =>
        `<li role="option" id="${listenId}-${i}" data-i="${i}" aria-selected="${o.selected}">${esc(o.textContent)}</li>`).join('')
      : '<li class="combobox-leer">Keine Treffer</li>';
    liste.hidden = false;
    feld.setAttribute('aria-expanded', 'true');
    markiere();
  }

  function schliesse() {
    liste.hidden = true;
    feld.setAttribute('aria-expanded', 'false');
    feld.removeAttribute('aria-activedescendant');
    zeigeAuswahl();
  }

  function waehle(index) {
    const option = treffer[index];
    if (!option) return;
    const vorher = select.value;
    select.value = option.value;
    schliesse();
    if (select.value !== vorher) {
      select.dispatchEvent(new Event('change'));
    }
  }

  feld.addEventListener('focus', () => { feld.select(); oeffne(''); });
  feld.addEventListener('click', () => { if (liste.hidden) oeffne(''); });
  feld.addEventListener('input', () => oeffne(feld.value.trim()));
  feld.addEventListener('blur', schliesse);
  feld.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault();
      if (liste.hidden) return oeffne('');
      if (!treffer.length) return;
      aktiv = (aktiv + (e.key === 'ArrowDown' ? 1 : -1) + treffer.length) % treffer.length;
      markiere();
    } else if (e.key === 'Enter' && !liste.hidden) {
      e.preventDefault(); // offene Liste: Enter wählt aus, statt das Formular abzuschicken
      waehle(aktiv);
    } else if (e.key === 'Escape' && !liste.hidden) {
      e.preventDefault();
      schliesse();
      feld.select(); // Fokus bleibt: erneutes Tippen ersetzt den Text statt anzuhängen
    }
  });
  // mousedown statt click, damit das Feld den Fokus nicht vorher verliert.
  liste.addEventListener('mousedown', (e) => {
    e.preventDefault();
    const eintrag = e.target.closest('li[data-i]');
    if (eintrag) waehle(Number(eintrag.dataset.i));
  });
  select.addEventListener('change', zeigeAuswahl);
  zeigeAuswahl();
}

async function ladeStammdaten() {
  const daten = await api('stammdaten.php');
  optionen($('filter-schuljahr'), daten.schuljahre, 'id', (s) => `Schuljahr ${s.name}`);
  optionen($('filter-lehrkraft'), daten.lehrkraefte, 'id', (l) => `${l.code} – ${l.name}`);
  optionen($('feld-klasse'), daten.klassen, 'code', (k) => `${k.code} – ${k.name}`);
  optionen($('feld-lehrkraft'), daten.lehrkraefte, 'id', (l) => `${l.code} – ${l.name}`);
  macheCombobox($('filter-lehrkraft'));
  macheCombobox($('feld-lehrkraft'));
}

async function ladeEintraege() {
  const daten = await api(`eintraege.php?school_year_id=${encodeURIComponent($('filter-schuljahr').value)}`);
  eintraege = daten.eintraege;
  zusammenfassung = daten.zusammenfassung;
  zeige();
}

function zeige() {
  const halbjahr = $('filter-halbjahr').value;
  const lehrkraft = $('filter-lehrkraft').value;
  const auswahl = eintraege.filter(
    (e) => (!halbjahr || String(e.half_year) === halbjahr) && (!lehrkraft || String(e.teacher_id) === lehrkraft)
  );
  const summe = auswahl.reduce((s, e) => s + Number(e.hours_half_year), 0);

  $('kz-stunden').textContent = stunden(summe);
  $('kz-eintraege').textContent = auswahl.length;
  $('kz-klassen').textContent = `in ${new Set(auswahl.map((e) => e.class_id)).size} Klassen`;
  $('kz-lehrkraefte').textContent = new Set(auswahl.map((e) => e.teacher_id)).size;

  // Seite begrenzen, z. B. wenn nach dem Löschen die letzte Seite leer wäre.
  const seiten = Math.max(1, Math.ceil(auswahl.length / PRO_SEITE));
  seite = Math.min(seite, seiten);
  const start = (seite - 1) * PRO_SEITE;
  const sichtbar = auswahl.slice(start, start + PRO_SEITE);

  $('tabelle-anzahl').textContent = auswahl.length;
  $('tabelle-summe').textContent = stunden(summe);
  $('tabelle-zeilen').innerHTML = sichtbar.length
    ? sichtbar.map((e) => `
      <tr>
        <td>HJ ${esc(e.half_year)}</td>
        <td class="fett">${esc(e.class_code)}</td>
        <td><span class="kuerzel" title="${esc(e.teacher_name)}">${esc(e.teacher_code)}</span></td>
        <td class="rechts">${esc(e.student_count)}</td>
        <td class="rechts">${Number(e.hours_per_week).toLocaleString('de-DE')}</td>
        <td class="rechts fett">${stunden(e.hours_half_year)}</td>
        <td class="aktion-spalte">
          <button class="icon-button" type="button" data-bearbeiten="${esc(e.id)}" aria-label="Eintrag ${esc(e.class_code)} ${esc(e.teacher_code)} bearbeiten"><svg class="icon"><use href="#i-pencil"/></svg></button>
          <button class="icon-button" type="button" data-loeschen="${esc(e.id)}" aria-label="Eintrag ${esc(e.class_code)} ${esc(e.teacher_code)} löschen"><svg class="icon"><use href="#i-trash"/></svg></button>
        </td>
      </tr>`).join('')
    : '<tr><td colspan="7" class="text-gedaempft">Keine Einträge für diese Auswahl.</td></tr>';

  $('seite-bereich').textContent = auswahl.length
    ? `${start + 1}–${start + sichtbar.length} von ${auswahl.length} Einträgen`
    : '';
  $('seite-info').textContent = `Seite ${seite} von ${seiten}`;
  $('seite-zurueck').disabled = seite <= 1;
  $('seite-weiter').disabled = seite >= seiten;

  const lehrkraefte = zusammenfassung.teachers.filter((t) => !lehrkraft || String(t.teacher_id) === lehrkraft);
  const max = Math.max(...lehrkraefte.map((t) => t.hours_school_year), 0) || 1;
  $('lehrkraefte-liste').innerHTML = lehrkraefte.map((t) => `
    <div class="lehrkraft">
      <div class="lehrkraft-kopf">
        <span class="kuerzel kuerzel-gross">${esc(t.teacher_code)}</span>
        <div class="lehrkraft-name">
          <span class="text-klein">${stunden(t.hours_half_year_1)} · ${stunden(t.hours_half_year_2)}</span>
        </div>
        <span class="lehrkraft-stunden">${stunden(t.hours_school_year)}</span>
      </div>
      <div class="balken"><div class="balken-wert" style="width: ${(t.hours_school_year / max) * 100}%"></div></div>
    </div>`).join('');
  $('gesamtsumme').textContent = stunden(zusammenfassung.totals.hours_school_year ?? 0);
}

function neuFiltern() {
  seite = 1;
  zeige();
}

function setzeModus(eintrag) {
  const formular = $('formular');
  bearbeiteId = eintrag ? eintrag.id : null;
  $('formular-titel').textContent = eintrag ? 'Eintrag bearbeiten' : 'Neuer Eintrag';
  $('formular-knopf').textContent = eintrag ? 'Änderungen speichern' : 'Eintrag speichern';
  $('formular-abbrechen').hidden = !eintrag;
  if (eintrag) {
    formular.class_code.value = eintrag.class_code;
    formular.teacher_id.value = eintrag.teacher_id;
    formular.teacher_id.dispatchEvent(new Event('change')); // Text der Lehrkraft-Combobox nachziehen
    formular.student_count.value = eintrag.student_count;
    formular.half_year.value = eintrag.half_year;
    $('formular-status').textContent = `Bearbeite ${eintrag.class_code} · ${eintrag.teacher_code}`;
    formular.scrollIntoView({ behavior: 'smooth', block: 'center' });
    formular.class_code.focus({ preventScroll: true });
  } else {
    formular.student_count.value = '';
  }
}

async function speichere(ereignis) {
  ereignis.preventDefault();
  const status = $('formular-status');
  const werte = Object.fromEntries(new FormData(ereignis.target));
  const bearbeiten = bearbeiteId !== null;
  status.textContent = 'Speichere …';
  try {
    const eintrag = await api(bearbeiten ? `eintraege.php?id=${bearbeiteId}` : 'eintraege.php', {
      method: bearbeiten ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ...werte, school_year_id: $('filter-schuljahr').value }),
    });
    setzeModus(null);
    status.textContent = `${bearbeiten ? 'Geändert' : 'Gespeichert'}: ${stunden(eintrag.hours_half_year)}`;
    await ladeEintraege();
  } catch (fehler) {
    status.textContent = `Fehler: ${fehler.message}`;
  }
}

async function loesche(id) {
  const eintrag = eintraege.find((e) => String(e.id) === id);
  const text = `Eintrag ${eintrag.class_code} · ${eintrag.teacher_code} · HJ ${eintrag.half_year} (${stunden(eintrag.hours_half_year)}) endgültig löschen?`;
  if (!confirm(text)) {
    return;
  }
  try {
    await api(`eintraege.php?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
    if (String(bearbeiteId) === id) {
      setzeModus(null);
    }
    $('formular-status').textContent = `Gelöscht: ${eintrag.class_code} · ${eintrag.teacher_code}`;
    await ladeEintraege();
  } catch (fehler) {
    alert(`Löschen fehlgeschlagen: ${fehler.message}`);
  }
}

function zeigeFehler(fehler) {
  $('tabelle-zeilen').innerHTML = `<tr><td colspan="7">Daten konnten nicht geladen werden: ${esc(fehler.message)}</td></tr>`;
}

$('filter-schuljahr').addEventListener('change', () => {
  seite = 1;
  ladeEintraege().catch(zeigeFehler);
});
$('filter-halbjahr').addEventListener('change', neuFiltern);
$('filter-lehrkraft').addEventListener('change', neuFiltern);
$('seite-zurueck').addEventListener('click', () => { seite--; zeige(); });
$('seite-weiter').addEventListener('click', () => { seite++; zeige(); });
$('formular').addEventListener('submit', speichere);
$('formular-abbrechen').addEventListener('click', () => {
  setzeModus(null);
  $('formular-status').textContent = 'Noch nicht gespeichert';
});
// Export folgt dem Lehrkraft-Filter: "Alle Lehrkräfte" = Gesamtexport, sonst nur die gewählte Lehrkraft.
function exportBeschriftung() {
  const auswahl = $('filter-lehrkraft').selectedOptions[0];
  $('export-text').textContent = auswahl && auswahl.value
    ? `Export ${auswahl.textContent.split(' – ')[0]}`
    : 'Gesamtexport';
}
$('filter-lehrkraft').addEventListener('change', exportBeschriftung);
$('export').addEventListener('click', () => {
  const parameter = new URLSearchParams({ school_year_id: $('filter-schuljahr').value });
  if ($('filter-lehrkraft').value) {
    parameter.set('teacher_id', $('filter-lehrkraft').value);
  }
  window.location.href = `${API}export.php?${parameter}`;
});
exportBeschriftung();

// Import: ersetzt nach Bestätigung alle Einträge des Schuljahres durch die Datei.
function zeigeImport(titel, details) {
  $('import-ergebnis').hidden = false;
  $('import-titel').textContent = titel;
  $('import-fehler').innerHTML = details.map((d) => `<li>${esc(d)}</li>`).join('');
  $('import-fehler').hidden = !details.length;
}
$('import').addEventListener('click', () => $('import-datei').click());
$('import-datei').addEventListener('change', async () => {
  const datei = $('import-datei').files[0];
  $('import-datei').value = ''; // dieselbe Datei soll nach einer Korrektur erneut wählbar sein
  if (!datei) return;
  const schuljahr = $('filter-schuljahr').selectedOptions[0].textContent;
  if (!confirm(`„${datei.name}“ importieren?\n\nAlle ${eintraege.length} Einträge im ${schuljahr} werden gelöscht und durch den Inhalt der Datei ersetzt. Das lässt sich nicht rückgängig machen.`)) {
    return;
  }
  const daten = new FormData();
  daten.append('datei', datei);
  daten.append('school_year_id', $('filter-schuljahr').value);
  zeigeImport('Importiere …', []);
  try {
    const antwort = await fetch(`${API}import.php`, { method: 'POST', body: daten });
    const ergebnis = await antwort.json().catch(() => ({}));
    if (!antwort.ok) {
      zeigeImport(ergebnis.fehler || `Serverfehler (${antwort.status})`, ergebnis.details || []);
      return;
    }
    zeigeImport(`Import erfolgreich: ${ergebnis.imported} Einträge übernommen, ${ergebnis.deleted} bisherige ersetzt.`, []);
    seite = 1;
    await ladeEintraege();
  } catch (fehler) {
    zeigeImport(`Import fehlgeschlagen: ${fehler.message}`, []);
  }
});
$('tabelle-zeilen').addEventListener('click', (ereignis) => {
  const knopf = ereignis.target.closest('button');
  if (!knopf) return;
  if (knopf.dataset.bearbeiten) {
    setzeModus(eintraege.find((e) => String(e.id) === knopf.dataset.bearbeiten));
  } else if (knopf.dataset.loeschen) {
    loesche(knopf.dataset.loeschen);
  }
});

ladeStammdaten().then(ladeEintraege).catch(zeigeFehler);
