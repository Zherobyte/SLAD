---
paths:
  - 'app/Services/**,app/Jobs/**,app/Models/Recordatorio.php,resources/views/components/causas/**'
---

# Causas

## Calendario Google institucional para recordatorios
Los recordatorios se sincronizan al calendario Google institucional configurado, no a calendarios personales. El destinatario siempre recibe invitación; el creador sólo se agrega cuando google_calendar_incluir_creador es verdadero. Las llamadas a Google van por cola y usan credenciales centrales desde config/services.php.
