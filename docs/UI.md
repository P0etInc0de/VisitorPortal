## Übersetzungen vorbereiten

Sichtbare UI-Texte direkt mit Laravels Übersetzungsfunktion `__()` kapseln.

Beispiel:

```blade
<h1>{{ __('Besucherportal') }}</h1>
<p>{{ __('Anmeldung für Mitarbeitende') }}</p>
<label>{{ __('E-Mail') }}</label>
```

Alle sichtbaren Texte sollten in dieser Form eingebunden werden.

## Theme / Darstellung

Die Oberfläche nutzt theme-basierte Klassen wie `bg-base-*`, `text-base-content` und `border-base-*` statt fest verdrahteter Farben wie `bg-white` oder `text-slate-900`.  
Dadurch bleibt die Darstellung konsistenter und ist besser auf Theme-Anpassungen vorbereitet.

Aktuelle Theme-Präferenzen:

* `light`
* `dark`
* `true-black`
* `system`

Bei `system` speichert der Client den effektiv genutzten Systemwert zusätzlich im Cookie `theme_effective`. Die Middleware nutzt diesen Wert für das initiale `data-theme`.

Das frühe Theme-Bootscript in den Layouts setzt vor dem sichtbaren Rendern `data-theme`, die `dark`-Klasse, `color-scheme` und einen initialen Hintergrund über `root.style.backgroundColor`. Das verhindert helle Zwischenhintergründe, bevor die vollständigen CSS-Assets geladen sind.

## SPA-artige Navigation

Die User-Oberfläche nutzt an vielen Stellen Livewire `wire:navigate` bzw. `wire:navigate.hover`, damit Seitenwechsel schneller und flüssiger wirken.

Hinweise:

* `wire:navigate.hover` ist für häufig genutzte Hauptnavigation sinnvoll.
* Bei zu vielen Vorab-Requests oder störenden Abbrüchen kann gezielt auf `wire:navigate` reduziert werden.
* Nicht pauschal entfernen, solange die Navigation stabil und schnell wirkt.
