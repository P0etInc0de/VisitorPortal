let sourceText;
let sequenceRunning = false;

const terminalStyle = 'color: #00ff66; background: #050805; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 13px; line-height: 1.35; padding: 2px 4px;';
const terminalStrongStyle = 'color: #b7ffce; background: #050805; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 13px; font-weight: 700; line-height: 1.35; padding: 2px 4px;';
const terminalWarnStyle = 'color: #ff4d4d; background: #050805; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 13px; font-weight: 700; line-height: 1.35; padding: 2px 4px;';
const sourceStyle = 'color: #00ff66; background: #050805; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 10px; line-height: 1; white-space: pre; padding: 4px;';

const bootLines = [
    '[NET] Resolving internal display mesh...',
    '[AUTH] Sampling CSRF token entropy...',
    '[DB] Opening read-only visitor ledger cursor...',
    '[LW] Inspecting hydration frame sequence...',
    '[UI] Mapping Alpine component graph...',
    '[MON] Synchronizing monitor heartbeat ACK...',
    '[CACHE] Priming route fingerprint table...',
    '[MAIL] Verifying notification queue checksum...',
    '[BADGE] Warming print-spooler handshake...',
    '[TIME] Measuring scheduler drift window...',
    '[I18N] Normalizing German compound nouns...',
    '[OPS] Negotiating maintenance window with coffee subnet...',
    '[SEC] Rotating imaginary salt bucket...',
    '[IO] Reversing QR badge polarity...',
    '[TRACE] Compacting plausible deniability buffer...',
    '[TRACE] Throttling suspicious toaster packets...',
];

const progressLines = [
    '[SCAN] Route cache signature accepted.',
    '[AUTH] Session guard responded with plausible confidence.',
    '[DB] Recurrence index sampled; no frogs detected.',
    '[LW] Snapshot checksum aligned.',
    '[MON] Display channel latency within theatrical limits.',
    '[BADGE] Badge entropy stable enough for government work.',
    '[CACHE] Stale hydration residue cleared.',
    '[SEC] Permission matrix folded into origami.',
    '[NET] Packet tunnel painted black.',
    '[OPS] Reception handshake patched without waking anyone.',
    '[IO] Bit bucket capacity nominal.',
    '[TRACE] Coffee subnet still refusing ping, proceeding anyway.',
];

const sleep = (milliseconds) => new Promise((resolve) => {
    window.setTimeout(resolve, milliseconds);
});

const randomFrom = (values) => values[Math.floor(Math.random() * values.length)];

const writeRandomLine = (values) => {
    writeLine(randomFrom(values));
};

const loadSource = async () => {
    if (sourceText === undefined) {
        sourceText = (await import('./glyph-map.txt?raw')).default;
    }

    return sourceText;
};

const writeSource = async (style = 'font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; line-height: 1; white-space: pre;') => {
    const text = await loadSource();

    console.log(`%c${text}`, style);
};

const writeLine = (message, style = terminalStyle) => {
    console.log(`%c${message}`, style);
};

const writeProgress = async () => {
    let progress = 0;

    while (progress < 100) {
        progress = Math.min(100, progress + Math.floor(Math.random() * 9) + 5);

        const width = 28;
        const filled = Math.round((progress / 100) * width);
        const bar = '█'.repeat(filled) + '░'.repeat(width - filled);

        writeLine(`[STREAM]  ${bar} ${String(progress).padStart(3, ' ')}%`);
        await sleep(180 + Math.floor(Math.random() * 260));

        if (progress > 0 && progress < 100 && Math.random() > 0.58) {
            writeRandomLine(progressLines);
            await sleep(120 + Math.floor(Math.random() * 180));
        }
    }
};

const writeEasterEggDisclaimer = async () => {
    console.clear();
    writeLine('CONGRATULATIONS', terminalStrongStyle);
    writeLine('You discovered the VisitorPortal Easter egg.', terminalStyle);
    writeLine('This is only styled console text output and does not grant access or perform security-sensitive actions.', terminalStyle);
    writeLine('Have a nice day, fellow developer.', terminalStyle);
    await sleep(4000);
    console.clear();
};

const openFallbackDocument = () => {
    window.location.replace(`/__diag/${Date.now().toString(36)}`);
};

const runSequence = async () => {
    if (sequenceRunning) {
        writeLine('[LOCKED] Active session already in progress.', terminalWarnStyle);

        return 'Session already active.';
    }

    sequenceRunning = true;

    try {
        await writeEasterEggDisclaimer();
        writeLine('[SYSTEM] CRITICAL VULNERABILITY DETECTED', terminalWarnStyle);
        await sleep(350);
        writeLine('[SYSTEM] Initiating adaptive bypass sequence...', terminalStrongStyle);
        await sleep(450);

        for (let index = 0; index < 7; index += 1) {
            writeRandomLine(bootLines);
            await sleep(180 + Math.floor(Math.random() * 420));
        }

        await writeProgress();
        await sleep(350);
        writeLine('[SUCCESS] ACCESS GRANTED. STREAM OPEN.', terminalStrongStyle);
        await sleep(3000 + Math.floor(Math.random() * 2001));
        await writeSource(sourceStyle);
        await sleep(250);
        writeLine('Mainframe compromised. Goodbye.', 'color: #6f7d72; background: #050805; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-style: italic; padding: 2px 4px;');
        await sleep(5000);
        openFallbackDocument();

        return 'Connection established.';
    } finally {
        sequenceRunning = false;
    }
};

export function installRuntimeChannel() {
    const channel = Symbol.for('bp:20260508173356635');

    if (Object.prototype.hasOwnProperty.call(window, channel)) {
        return;
    }

    Object.defineProperty(window, channel, {
        value: writeSource,
        enumerable: false,
        configurable: false,
        writable: false,
    });

    Object.defineProperty(window, 'hackTheMainframe', {
        value: runSequence,
        enumerable: false,
        configurable: false,
        writable: false,
    });
}
