# Fertility booking questions

The public booking form shows two optional, closed-choice questions only when the selected service has the stable slug `service-2` (the seeded fertility service): previous pregnancy and pregnancy-related operation. Each question offers yes, no, or “prefer not to answer.” Skipping is represented as `null` or an omitted field.

The booking endpoint accepts `previous_pregnancy` and `pregnancy_related_surgery` as nullable `yes`/`no` values for `service-2`. For every other service these fields are ignored, including malformed values. Older callers can omit both fields. Answers are kept only in the browser during the wizard and in the generated WhatsApp handoff message; they are excluded from booking persistence and application logs. Do not add them to analytics, patient records, booking records, or request logging.

These are intake questions for appointment routing only. The interface states that answering is optional and answers accompany the WhatsApp message; a skip choice is available.
