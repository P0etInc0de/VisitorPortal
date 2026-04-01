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

Die Login-Seite nutzt aktuell theme-basierte Klassen wie `bg-base-*`, `text-base-content` und `border-base-*` statt fest verdrahteter Farben wie `bg-white` oder `text-slate-900`.  
Auch daisyUI-Komponenten wie `btn`, `card`, `input`, `textarea`, `select` und `badge`.
daisyUI wurde vor allem dort eingesetzt, wo standardisierte UI-Elemente sinnvoll und konsistent wiederverwendet werden können, z. B. bei Buttons, Formularfeldern, Cards, Badges.
Ziel war nicht, alles zwanghaft mit daisyUI umzusetzen, sondern daisyUI bewusst für wiederkehrende Standard-Elemente zu nutzen

Dadurch bleibt die Darstellung konsistenter und ist besser auf spätere Theme-Anpassungen vorbereitet.

Eine weitergehende Theme-Logik oder ein vollständiges Darkmode-Konzept ist derzeit noch nicht umgesetzt und kann später ergänzt werden.
