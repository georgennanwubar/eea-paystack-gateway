# Event Espresso – Paystack Payment Gateway

A WordPress payment-gateway add-on that integrates the **Paystack (Nigeria)** API as an *onsite* payment method for **Event Espresso** event registrations.

**Version:** 1.0.6 · **Stack:** PHP, WordPress, Event Espresso 4.6.0+ · **License:** GPL-2.0-or-later

## What it does

Adds Paystack as a first-class onsite payment method inside Event Espresso's checkout, so attendees pay for event registrations without leaving the site. On payment, the gateway verifies the transaction against the Paystack API and writes the authorization details back to the Event Espresso payment record.

## Key technical details

- **OOP gateway architecture** built on Event Espresso's payment-method framework (`EE_PMT_*`, `EEG_*_Onsite` gateway, settings form objects).
- Server-side transaction **verification over the Paystack REST API** using an authenticated `Authorization: Bearer` request (cURL).
- Public/secret API keys are entered by the site admin via Event Espresso payment-method settings — **no keys are hardcoded** in the plugin.
- Embedded onsite payment form template + supporting JS/CSS assets.

## Installation

1. Requires WordPress with the **Event Espresso 4** plugin active.
2. Copy this folder to `wp-content/plugins/eea-paystack-gateway/` and activate it.
3. In **Event Espresso → Payment Methods**, enable *Paystack* and enter your Paystack **public** and **secret** keys.

## Configuration

Set these in the Event Espresso payment-method settings (never commit real keys):

- `Paystack Public Key` — e.g. `pk_live_xxx`
- `Paystack Secret Key` — e.g. `sk_live_xxx`

## Built by

George Nnanwubar — [george.ng](https://george.ng) · [github.com/georgennanwubar](https://github.com/georgennanwubar) · Manndi Technologies
