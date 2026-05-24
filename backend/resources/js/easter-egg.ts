import glyphMap from './runtime/glyph-map.txt?raw';

type BadgebertContext = 'reception' | 'monitor' | 'admin' | 'badge' | 'default';

const sessionPrefix = 'visitorportal.badgebert';
const memoryFlags = new Set<string>();

const badgebertStyle = 'color: #f97316; font-family: ui-rounded, system-ui, sans-serif; font-size: 13px; font-weight: 800;';
const messageStyle = 'color: #475569; font-family: ui-rounded, system-ui, sans-serif; font-size: 13px;';
const glyphStyle = 'color: #f97316; background: transparent; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 10px; line-height: 1; white-space: pre;';

const messages: Record<BadgebertContext, readonly string[]> = {
    reception: [
        'Badgebert polished the welcome bell and left a tiny biscuit nearby.',
        'Badgebert says the reception desk has excellent snack potential.',
    ],
    monitor: [
        'Badgebert waves from the welcome display with both paws.',
        'Badgebert adjusted the imaginary bow tie for the next greeting.',
    ],
    admin: [
        'Badgebert found the settings shelf neatly labeled.',
        'Badgebert approves of tidy switches and clearly named buttons.',
    ],
    badge: [
        'Badgebert packed a spare lanyard for a very important badge.',
        'Badgebert thinks every good badge deserves a confident nod.',
    ],
    default: [
        'Badgebert found a quiet corner in the console.',
        'Badgebert brought a harmless console postcard.',
    ],
};

const hasSessionFlag = (key: string): boolean => {
    try {
        return window.sessionStorage.getItem(key) === '1';
    } catch {
        return memoryFlags.has(key);
    }
};

const setSessionFlag = (key: string): void => {
    memoryFlags.add(key);

    try {
        window.sessionStorage.setItem(key, '1');
    } catch {
        // The in-memory flag still prevents repeats during this page lifetime.
    }
};

const pick = <Value>(values: readonly Value[]): Value => values[Math.floor(Math.random() * values.length)];

const detectContext = (): BadgebertContext => {
    const path = window.location.pathname.toLowerCase();

    if (path.includes('/reception')) {
        return 'reception';
    }

    if (path.includes('/monitors') || path.includes('/monitor')) {
        return 'monitor';
    }

    if (path.includes('/admin')) {
        return 'admin';
    }

    if (path.includes('/badge')) {
        return 'badge';
    }

    return 'default';
};

const writeGlyphMap = (): void => {
    const key = `${sessionPrefix}.glyph.seen`;

    if (hasSessionFlag(key)) {
        return;
    }

    console.log(`%c${glyphMap}`, glyphStyle);
    setSessionFlag(key);
};

const writeBadgebertLine = (): void => {
    const context = detectContext();
    const key = `${sessionPrefix}.seen.${context}`;

    if (hasSessionFlag(key)) {
        return;
    }

    console.log('%cBadgebert%c %s', badgebertStyle, messageStyle, pick(messages[context]));
    setSessionFlag(key);
};

writeGlyphMap();
writeBadgebertLine();
