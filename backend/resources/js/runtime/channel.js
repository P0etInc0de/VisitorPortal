let sourceText;
let sequenceRunning = false;

const terminalStyle = 'color: #00ff66; background: #050805; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 13px; line-height: 1.35; padding: 2px 4px;';
const terminalStrongStyle = 'color: #b7ffce; background: #050805; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 13px; font-weight: 700; line-height: 1.35; padding: 2px 4px;';
const terminalWarnStyle = 'color: #ff4d4d; background: #050805; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 13px; font-weight: 700; line-height: 1.35; padding: 2px 4px;';
const sourceStyle = 'color: #00ff66; background: #050805; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 10px; line-height: 1; white-space: pre; padding: 4px;';

const lines = [
    'Negotiating uplink...',
    'Decrypting visitor ledger...',
    'Re-indexing access matrix...',
    'Bypassing coffee machine firewall...',
    'Reticulating splines...',
    'Patching reception handshake...',
    'Resolving badge entropy...',
    'Compiling plausible deniability...',
    'Optimizing flux capacitor...',
    'Synchronizing monitor beacons...',
    'Rotating checksum salt...',
    'Escalating snack privileges...',
];

const sleep = (milliseconds) => new Promise((resolve) => {
    window.setTimeout(resolve, milliseconds);
});

const randomFrom = (values) => values[Math.floor(Math.random() * values.length)];

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
        progress = Math.min(100, progress + Math.floor(Math.random() * 11) + 4);

        const width = 28;
        const filled = Math.round((progress / 100) * width);
        const bar = '█'.repeat(filled) + '░'.repeat(width - filled);

        writeLine(`[RUNNING] ${bar} ${String(progress).padStart(3, ' ')}%`);
        await sleep(180 + Math.floor(Math.random() * 260));

        if (progress > 0 && progress < 100 && Math.random() > 0.62) {
            writeLine(`[TRACE] ${randomFrom(lines)}`);
            await sleep(120 + Math.floor(Math.random() * 180));
        }
    }
};

const runSequence = async () => {
    if (sequenceRunning) {
        writeLine('[LOCKED] Active session already in progress.', terminalWarnStyle);

        return 'Session already active.';
    }

    sequenceRunning = true;

    try {
        console.clear();
        writeLine('[SYSTEM] CRITICAL VULNERABILITY DETECTED', terminalWarnStyle);
        await sleep(350);
        writeLine('[SYSTEM] Initiating bypass sequence...', terminalStrongStyle);
        await sleep(450);

        for (let index = 0; index < 5; index += 1) {
            writeLine(`[TRACE] ${randomFrom(lines)}`);
            await sleep(220 + Math.floor(Math.random() * 380));
        }

        await writeProgress();
        await sleep(350);
        writeLine('[SUCCESS] ACCESS GRANTED. STREAM OPEN.', terminalStrongStyle);
        await sleep(700);
        await writeSource(sourceStyle);
        await sleep(250);
        writeLine('Mainframe compromised. Goodbye.', 'color: #6f7d72; background: #050805; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-style: italic; padding: 2px 4px;');

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

    Object.defineProperty(window, 'HackTheMainfraim', {
        value: runSequence,
        enumerable: false,
        configurable: false,
        writable: false,
    });
}
