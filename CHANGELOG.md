# Changelog

All notable changes to `resend-inbox` will be documented in this file.

## 0.1.1 - 2026-10-09

- `ReceivedEmail::$receivedAt` is in PHP's default timezone. Stored by an ORM in an app with a timezone other than UTC, the time Resend sends in UTC was read back hours later, and new emails could show up as sent in the future and out of order.

## 0.1.0 - 2026-10-09

- First version, extracted from the Omni Line backoffice inbox: webhook parsing with Svix signature verification, conversation threading behind a `MessageLookup` interface, reply building from Markdown with threading headers, auto-reply detection, delivery status ordering and the Resend API calls.
