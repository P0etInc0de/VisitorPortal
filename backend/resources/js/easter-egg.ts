import glyphMap from './runtime/glyph-map.txt?raw';

type BadgebertContext = 'reception' | 'monitor' | 'admin' | 'badge' | 'default';

const sessionPrefix = 'visitorportal.badgebert';
const memoryValues = new Map<string, string>();
const navigationCooldownMilliseconds = 90_000;
const sessionMessageLimit = 18;

const badgebertStyle = 'color: #f97316; font-family: ui-rounded, system-ui, sans-serif; font-size: 13px; font-weight: 800;';
const messageStyle = 'color: #475569; font-family: ui-rounded, system-ui, sans-serif; font-size: 13px;';
const glyphStyle = 'color: #f97316; background: transparent; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 10px; line-height: 1; white-space: pre;';

const messages: Record<BadgebertContext, readonly string[]> = {
    reception: [
        'Badgebert am Empfang: Erst suchen, dann einchecken, dann triumphieren.',
        'Check-in erfolgreich. Menschliche Anwesenheit wurde serverseitig bestätigt.',
        'Der Besucher ist da. Der Host weiß hoffentlich bald Bescheid.',
        'Bitte keine Gäste im Status planned zurücklassen.',
        'Walk-in erkannt. Spontanität wurde akzeptiert.',
        'Ein Besucher betritt das System. Würfle auf Empfangskompetenz.',
        'Badgebert sagt: Wer eincheckt, sollte auch irgendwann auschecken.',
        'Dieser Check-in wurde nicht in Excel verletzt.',
        'Empfangsmodus aktiv. Bitte freundlich bleiben, auch bei Gruppenbesuchen.',
        'Gruppenbesuch erkannt. Pivot-Tabelle angeschnallt.',
    ],
    monitor: [
        'Badgebert begrüßt: Heute auf dem Monitor, morgen in der Datenbankhistorie.',
        'Monitor aktiv. Bitte lächeln, Sie werden begrüßt.',
        'PowerPoint wurde erfolgreich durch einen Scheduler ersetzt.',
        'Willkommensfolie generiert. Manuelle Folienpflege weint leise.',
        'Monitorseiten aktualisiert. Der Empfang wirkt jetzt vorbereitet.',
        'Falls niemand begrüßt wird, ist vielleicht einfach niemand geplant.',
        'Badgebert sagt: Öffentliche Anzeige, private Vorsicht.',
        'Slide geladen. Bitte nicht mit der Maus winken.',
    ],
    admin: [
        'Gatekeeper Badgebert: Sichtbarkeit ist nett, Policy ist besser.',
        'Nur weil ein Button weg ist, ist es noch keine Sicherheit.',
        'Berechtigung geprüft. Badgebert hebt eine Augenbraue.',
        'Adminbereich betreten. Bitte keine Rollen nach Gefühl vergeben.',
        'Spatie sagt ja. Filament nickt.',
        'Policy aktiv. Direktaufrufe werden nicht persönlich genommen.',
        '403 ist auch nur ein Nein mit Statuscode.',
        'Badgebert sagt: Hardcodierte Rollen schmecken nach technischem Schuldenkonto.',
        'Gatekeeper Günther hätte das auch geprüft, aber Badgebert war schneller.',
    ],
    badge: [
        'Badgebert stempelt: Ausweis wird nur für echte Teilnehmer erzeugt.',
        'PDF wird gerendert. Chromium zieht sich kurz einen Anzug an.',
        'Gotenberg arbeitet. Bitte nicht am Badge rütteln.',
        'Kreditkartenformat geladen. Das Papier ist nervös.',
        'Badge gedruckt. Der Besucher ist jetzt offiziell sichtbar.',
        'Dieser Ausweis wurde mit mehr Sorgfalt erzeugt als manche Präsentationsfolie.',
        'Badgebert sagt: 85,6 mm × 53,98 mm sind kein Zufall.',
        'PDF-Ausweis bereit. Bitte nicht als Eintrittskarte für alles verwenden.',
    ],
    default: [
        'Badgebert sagt: Check-in ist kein Gefühl, sondern ein Status.',
        'Badgebert prüft kurz, ob alle Besucher noch im richtigen Pivot hängen.',
        'Bitte keine Besucher direkt in der Datenbank begrüßen.',
        'Diese Konsole wird von einem sehr kleinen Empfangsmitarbeiter bewacht.',
        'Besuch erkannt. Bitte langsam an der Rezeption vorbeiscrollen.',
        'Wenn du das hier liest, bist du entweder Entwickler oder sehr neugierig.',
        'Badgebert hat den Status geprüft. Irgendwas ist immer planned.',
        'Kein Badge, kein Business.',
        'Bitte bleiben Sie im markierten Lifecycle.',
        'VisitorPortal geladen. Der Empfang ist jetzt digital verwirrt.',
        'Livewire sagt Hallo. JavaScript wollte nur kurz helfen.',
        'Diese Anwendung enthält Spuren von Laravel, Kaffee und späten Pull Requests.',
        'Sir Checkalot hat die Tests gesehen und nickt vorsichtig zufrieden.',
        '120 Tests später: Badgebert glaubt wieder an Veränderung.',
        'Sir Badgebert hat die Tests gesehen und nickt vorsichtig zufrieden.',
        'Feature-Test bestanden. Ein kleiner Bug ist irgendwo beleidigt.',
        'CI läuft. Bitte keine Hoffnung committen.',
        'GitHub Actions prüft, Badgebert urteilt.',
        'Regression verhindert. Jannik spürt eine Erschütterung in der Testbasis.',
        'Dieser Fehler wurde schon einmal besiegt. Wahrscheinlich.',
        'Seeder geladen. Die Demo-Besucher sind wieder da.',
        'Factory sagt: Dieser Besucher ist zufällig, aber gültig.',
        'Pint war hier. Es sieht jetzt zumindest formatiert aus.',
    ],
};

const getSessionValue = (key: string): string | null => {
    try {
        return window.sessionStorage.getItem(key) ?? memoryValues.get(key) ?? null;
    } catch {
        return memoryValues.get(key) ?? null;
    }
};

const setSessionValue = (key: string, value: string): void => {
    memoryValues.set(key, value);

    try {
        window.sessionStorage.setItem(key, value);
    } catch {
        // The in-memory value still works for browsers that block sessionStorage.
    }
};

const hasSessionFlag = (key: string): boolean => {
    return getSessionValue(key) === '1';
};

const setSessionFlag = (key: string): void => {
    setSessionValue(key, '1');
};

const getSessionNumber = (key: string): number => {
    const value = Number(getSessionValue(key));

    return Number.isFinite(value) ? value : 0;
};

const pick = <Value>(values: readonly Value[]): Value => values[Math.floor(Math.random() * values.length)];

const getUsedMessages = (context: BadgebertContext): string[] => {
    return (getSessionValue(`${sessionPrefix}.message.used.${context}`) ?? '')
        .split('\n')
        .filter(Boolean);
};

const pickMessage = (context: BadgebertContext): string => {
    const values = messages[context];
    const usedMessages = getUsedMessages(context);
    const availableMessages = values.filter((message) => !usedMessages.includes(message));
    const pool = availableMessages.length > 0 ? availableMessages : values;
    const lastMessage = getSessionValue(`${sessionPrefix}.message.lastText`);

    for (let attempt = 0; attempt < 5; attempt += 1) {
        const message = pick(pool);

        if (pool.length === 1 || message !== lastMessage) {
            return message;
        }
    }

    return pick(pool);
};

const detectContext = (): BadgebertContext => {
    const path = window.location.pathname.toLowerCase();

    if (path.includes('/badge')) {
        return 'badge';
    }

    if (path.includes('/reception')) {
        return 'reception';
    }

    if (path.includes('/monitors') || path.includes('/monitor')) {
        return 'monitor';
    }

    if (path.includes('/admin')) {
        return 'admin';
    }

    return 'default';
};

const canWriteBadgebertLine = (context: BadgebertContext, immediate: boolean): boolean => {
    const count = getSessionNumber(`${sessionPrefix}.message.count`);

    if (count >= sessionMessageLimit) {
        return false;
    }

    if (immediate) {
        return true;
    }

    const lastContext = getSessionValue(`${sessionPrefix}.message.lastContext`);
    const lastAt = getSessionNumber(`${sessionPrefix}.message.lastAt`);

    return context !== lastContext || Date.now() - lastAt >= navigationCooldownMilliseconds;
};

const rememberBadgebertLine = (context: BadgebertContext, message: string): void => {
    const usedMessages = getUsedMessages(context);
    const nextUsedMessages = usedMessages.length >= messages[context].length
        ? [message]
        : [...usedMessages, message];

    setSessionValue(`${sessionPrefix}.message.count`, String(getSessionNumber(`${sessionPrefix}.message.count`) + 1));
    setSessionValue(`${sessionPrefix}.message.lastAt`, String(Date.now()));
    setSessionValue(`${sessionPrefix}.message.lastContext`, context);
    setSessionValue(`${sessionPrefix}.message.lastText`, message);
    setSessionValue(`${sessionPrefix}.message.used.${context}`, nextUsedMessages.join('\n'));
};

const writeMessage = (message: string): void => {
    if (/^(Badgebert|Sir |Gatekeeper)/.test(message)) {
        console.log(`%c${message}`, messageStyle);

        return;
    }

    console.log('%cBadgebert%c %s', badgebertStyle, messageStyle, message);
};

const writeGlyphMap = (): void => {
    const key = `${sessionPrefix}.glyph.seen`;

    if (hasSessionFlag(key)) {
        return;
    }

    console.log(`%c${glyphMap}`, glyphStyle);
    setSessionFlag(key);
};

const writeBadgebertLine = (immediate = false): void => {
    const context = detectContext();

    if (!canWriteBadgebertLine(context, immediate)) {
        return;
    }

    const message = pickMessage(context);

    writeMessage(message);
    rememberBadgebertLine(context, message);
};

writeGlyphMap();
writeBadgebertLine(true);

document.addEventListener('livewire:navigated', () => {
    writeBadgebertLine();
});
