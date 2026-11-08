# Whop for Moodle

[![CI](https://github.com/devlms/moodle-paygw_whop/actions/workflows/ci.yml/badge.svg)](https://github.com/devlms/moodle-paygw_whop/actions/workflows/ci.yml)
[![Maturity](https://img.shields.io/badge/maturity-alpha-orange)](version.php)

A Moodle payment gateway for [Whop](https://whop.com). Learners pay course fees with cards, Apple Pay, Google Pay, PayPal and the other methods Whop offers in their country. Whop is the merchant of record: it handles the payment, the receipt and, depending on your tax settings, the sales tax.

| Feature | Status |
| --- | --- |
| One-time payments for anything in Moodle that takes payments, such as enrolment on payment | Done |
| Sandbox and live environments | Done |
| Every payment read back from Whop's API before access is given; webhooks checked with their signature (Standard Webhooks) and timestamp | Done |
| Each payment delivered once, whether the webhook or the return page arrives first | Done |
| Prices in USD, EUR, GBP and 13 more currencies | Done |
| Works on its own, and as a provider of the [DevLMS payment hub](https://github.com/devlms/moodle-paygw_payhub) | Done |
| English, French and Arabic | Done |
| Subscriptions | Planned |

## Set up

1. Install the plugin in `payment/gateway/whop` and finish the upgrade.
2. Site administration > Plugins > Payment gateways: enable Whop.
3. In your Whop dashboard (start in the sandbox), copy a company API key and your company ID (`biz_...`). Under Developer, create a webhook for `https://your-site/payment/gateway/whop/webhook.php` with the payment.succeeded and payment.failed events, and copy its signing secret (`ws_...`).
4. Site administration > Payments > Payment accounts: open an account, then Whop. Choose Sandbox, paste the API key, company ID and webhook secret, and enable it.
5. Price a course in one of the supported currencies and choose that payment account.

Test with the card 4242 4242 4242 4242 in the sandbox, then switch the account to Live with your live keys.

## How it works

The learner chooses Whop in Moodle's payment window and pays on Whop's checkout page. When Whop's webhook arrives, or the learner comes back, the plugin reads the payment from Whop's API. Only a payment Whop reports as paid, for this checkout and for at least the price, enrols the learner.

## Requirements

Moodle 4.5 to 5.3. A Whop account in a [supported payout country](https://docs.whop.com).

## Licence

GPL v3 or later. Built by [DevLMS](https://devlms.com), who develop Moodle plugins. [Get a quote](https://devlms.com/#quote).
