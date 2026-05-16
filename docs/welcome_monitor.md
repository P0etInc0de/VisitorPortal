# Welcome Monitor

## Workflow manual mode:
- Open the editor
- Create a new slide
- Today's visits and visitors are displayed
- Visitors can be added to the slide
- Save the slide
- The welcome monitor updates automatically via livewire

## Workflow automatic mode:
- Open the editor
- Toggle  `Seiten automatisch generieren`
- Press `Speichern`
- The auto generating task will be executed via the queue worker
- Wait 5 seconds until the preview and slides have updated (via livewire)
- The auto generation is scheduled to run every 5 minutes and creates slides for every visit from now +/- 30 minutes
- Auto generation only uses visits from the monitor's own site.
- Confidential visits are always hidden from welcome monitors.
- The monitor controls the visitor display mode for generated slides; manual slides can override it per slide.
- Auto generation is disabled by default for privacy reasons. New monitors show the generic fallback slide until an operator intentionally enables generated visitor slides.
- The default visitor display mode remains title + first-name initial + last name and only takes effect when auto generation is enabled.
