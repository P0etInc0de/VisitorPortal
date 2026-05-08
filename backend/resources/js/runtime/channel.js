let sourceText;

const loadSource = async () => {
    if (sourceText === undefined) {
        sourceText = (await import('./glyph-map.txt?raw')).default;
    }

    return sourceText;
};

const writeSource = async () => {
    const text = await loadSource();

    console.log(
        `%c${text}`,
        'font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; line-height: 1; white-space: pre;',
    );
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
}
