# Loothtool Marketplace — Financial Flow Overview

**Prepared for:** Ian — Loothgroup
**Date:** March 17, 2026
**From:** A Loothtool Vendor

---

## Hey Ian,

I wanted to put together a clear picture of how money moves through the Loothtool platform — what's working, what's broken, and a real opportunity for growth. I brought in some technical help to audit the system, and this is what we found.

There are three things to cover:
1. **A tax bug that needs fixing ASAP** (money is going to the wrong place)
2. **How the money should actually flow for US orders** (the fix)
3. **How we can open up international sales** (the opportunity)

---

## 1. How Money Flows Today (There's a Problem)

Here's what happens right now when a customer buys a $100 tool on Loothtool:

```
                         CURRENT FLOW (BROKEN)
                         =====================

    CUSTOMER pays $120.00 at checkout
    [$100 product + $12 shipping + $8 sales tax (8%)]
            |
            v
    +------------------+
    |   WooCommerce    |  Processes payment via payment gateway
    |    Checkout      |  (Stripe/PayPal takes ~$3.78 processing fee)
    +------------------+
            |
            v
    +------------------+
    |   Commission     |  Calculates 15% platform commission on $100
    |    Plugin        |  Commission = $15.00
    +------------------+
            |
            |   HERE'S THE PROBLEM
            |   ==================
            v
    +------------------+
    |                  |  Vendor gets:
    |     VENDOR       |    $85.00 (product minus 15% commission)
    |                  |  + $12.00 (shipping)
    |                  |  + $8.00  (TAX)        <-- THIS IS WRONG
    |                  |  - $3.78  (processing)
    |                  |  ─────────────────
    |                  |  = $101.22 payout
    +------------------+

    +------------------+
    |                  |
    |    PLATFORM      |  Gets: $15.00 (commission only)
    |   (Loothtool)    |  Has: $0 for tax remittance
    |                  |
    +------------------+

    +------------------+
    |                  |
    |   TAX AUTHORITY  |  Gets: $0.00     <-- NOBODY IS PAYING THIS
    |   (State/IRS)    |  Owed: $8.00
    |                  |
    +------------------+
```

### What's going wrong

The commission plugin (`loothtool-commissions`) has this line hardcoded:

```
vendor payout = vendor earnings + shipping + TAX - processing fees
```

It sends the **full tax amount to the vendor**. The platform keeps $0 for tax. Unless every vendor is independently filing and remitting sales tax to the state (spoiler: they probably aren't), **nobody is paying the tax authorities**.

### Why this is a legal risk

**Marketplace facilitator laws** exist in **45+ US states**. These laws say the **marketplace** (Loothtool) is responsible for collecting and remitting sales tax — not the individual vendors. Even though the vendor received the tax money, if there's an audit, **Loothtool owes it back plus penalties and interest**.

```
    WHO THE STATE COMES AFTER:

    +-----------+     +-----------+     +-----------+
    |  Vendor   |     | Loothtool |     |  Customer |
    |           |     |           |     |           |
    |  Has the  |     |  Liable   |     |  Already  |
    |  tax $$$  |     |  for it   |     |  paid it  |
    +-----------+     +-----------+     +-----------+
         ^                  ^
         |                  |
    Got the money      Gets the bill
    by accident        by law
```

---

## 2. How Money SHOULD Flow — Domestic (The Fix)

Here's the same $100 order with the tax flowing correctly:

```
                         CORRECTED FLOW (DOMESTIC)
                         =========================

    CUSTOMER pays $120.00 at checkout
    [$100 product + $12 shipping + $8 sales tax (8%)]
            |
            v
    +------------------+
    |   WooCommerce    |  Payment gateway processes $120.00
    |    Checkout      |  Processing fee: ~$3.78 (2.9% + $0.30)
    +------------------+
            |
            v
    +------------------+
    |   Commission     |  Commission calculated on product price ONLY
    |    Plugin        |  (tax and shipping excluded from commission base)
    +------------------+
            |
            +-----------------------------+
            |                             |
            v                             v
    +------------------+         +------------------+
    |                  |         |                  |
    |     VENDOR       |         |    PLATFORM      |
    |                  |         |   (Loothtool)    |
    |  $85.00 product  |         |                  |
    |  $12.00 shipping |         |  $15.00 commish  |
    |  -$3.78 process  |         |  + $8.00 TAX     |
    |  ───────────────  |         |  ───────────────  |
    |  = $93.22 payout |         |  = $23.00 held   |
    |                  |         |                  |
    +------------------+         +--------+---------+
                                          |
                                          | Platform remits tax
                                          v
                                 +------------------+
                                 |                  |
                                 |  TAX AUTHORITY   |
                                 |                  |
                                 |  Receives $8.00  |
                                 |                  |
                                 +------------------+
```

### The dollar breakdown (per $100 order)

```
    +----------------------------------------------------------------+
    |                    CORRECTED MONEY SPLIT                        |
    +----------------------------------------------------------------+
    |                                                                |
    |  Customer pays:                              $120.00           |
    |    Product                   $100.00                           |
    |    Shipping                   $12.00                           |
    |    Sales tax (8%)              $8.00                           |
    |                                                                |
    +----------------------------------------------------------------+
    |                                                                |
    |  Vendor receives:                             $93.22           |
    |    Product revenue           $100.00                           |
    |    Minus 15% commission      -$15.00                           |
    |    Plus shipping              $12.00                           |
    |    Minus processing fee       -$3.78                           |
    |                              ────────                          |
    |                               $93.22                           |
    |                                                                |
    +----------------------------------------------------------------+
    |                                                                |
    |  Platform keeps:                              $15.00           |
    |    Commission (15% of $100)   $15.00                           |
    |                                                                |
    +----------------------------------------------------------------+
    |                                                                |
    |  Platform holds & remits:                      $8.00           |
    |    Sales tax → state/local     $8.00                           |
    |    (This is NOT platform revenue — it's                        |
    |     collected on behalf of the government)                     |
    |                                                                |
    +----------------------------------------------------------------+
    |                                                                |
    |  Payment processor takes:                      $3.78           |
    |    Stripe/PayPal (2.9% + $0.30 on $120)                       |
    |                                                                |
    +----------------------------------------------------------------+
    |                                                                |
    |  CHECK: $93.22 + $15.00 + $8.00 + $3.78 = $120.00  ✓         |
    |                                                                |
    +----------------------------------------------------------------+
```

### What needs to change

| # | Change | Where | Effort |
|---|--------|-------|--------|
| 1 | Fix commission plugin to stop sending tax to vendors | `loothtool-commissions` plugin code | Code fix (free) |
| 2 | Set Dokan "Tax Fee Recipient" to Admin | Dokan → Settings → Selling Options | Admin toggle (free) |
| 3 | Add automated tax rate calculation | Install TaxJar ($99/mo) or WooCommerce Tax (free) | Plugin install |
| 4 | Register for sales tax in nexus states | State websites | Paperwork |

---

## 3. How Money WOULD Flow — International (The Opportunity)

If a customer in the **UK** buys the same $100 tool, here's what the flow looks like:

```
                    INTERNATIONAL FLOW (UK EXAMPLE)
                    ===============================

    UK CUSTOMER pays $158.50 at checkout
    [$100 product + $25 intl shipping + $8 US tax + $20 UK VAT + $5.50 customs duty]
            |
            v
    +------------------+
    |   WooCommerce    |  Easyship calculates shipping + duties + VAT
    |   + Easyship     |  at checkout — customer sees FULL cost upfront
    |    Checkout      |  (DDP = Delivered Duty Paid — no surprise fees)
    +------------------+
            |
            +----------+----------+-----------+
            |          |          |           |
            v          v          v           v
    +----------+ +----------+ +----------+ +----------+
    |          | |          | |          | |          |
    |  VENDOR  | | PLATFORM | |  TAXES   | | SHIPPING |
    |          | |          | |          | |          |
    |  $85.00  | |  $15.00  | | $8 US tx | |  $25.00  |
    |  product | | commish  | | $20 VAT  | |  carrier |
    |  -$4.90  | |          | | $5.50    | |  postage |
    |  process | |          | | duty     | |          |
    | ──────── | |          | |          | |          |
    |  $80.10  | |  $15.00  | | $33.50   | |  $25.00  |
    |          | |          | |          | |          |
    +----------+ +----------+ +----+-----+ +----------+
                                   |
                     +-------------+-------------+
                     |             |             |
                     v             v             v
              +-----------+ +-----------+ +-----------+
              |  US State | |  UK HMRC  | | UK Border |
              |           | |           | |  Agency   |
              | Gets $8   | | Gets $20  | | Gets $5.50|
              | sales tax | | UK VAT    | | duty      |
              +-----------+ +-----------+ +-----------+
```

### International cost breakdown

```
    +----------------------------------------------------------------+
    |            INTERNATIONAL ORDER BREAKDOWN (UK)                   |
    +----------------------------------------------------------------+
    |                                                                |
    |  Product price:                              $100.00           |
    |                                                                |
    |  + International shipping (DHL/USPS):         $25.00           |
    |    (weight, size, and destination dependent)                   |
    |                                                                |
    |  + US sales tax (8%):                          $8.00           |
    |    (same as domestic — you still collect this)                 |
    |                                                                |
    |  + UK VAT (20% on product + shipping):        $25.00           |
    |    (calculated by Easyship at checkout)                        |
    |                                                                |
    |  + UK customs duty (~3-5% on tools):           $5.00           |
    |    (based on HS code / product classification)                 |
    |                                                                |
    |  ─────────────────────────────────────────────────             |
    |  CUSTOMER TOTAL:                             $163.00           |
    |                                                                |
    +----------------------------------------------------------------+
    |                                                                |
    |  VENDOR RECEIVES:                                              |
    |    $100 - $15 commission - $4.93 processing = $80.07           |
    |    (Same margin as domestic — international                    |
    |     costs don't come out of vendor's cut)                     |
    |                                                                |
    +----------------------------------------------------------------+
    |                                                                |
    |  PLATFORM RECEIVES:                                            |
    |    $15.00 commission                                           |
    |    (Same margin as domestic — duties/VAT are                   |
    |     pass-through, not your expense)                            |
    |                                                                |
    +----------------------------------------------------------------+
    |                                                                |
    |  KEY INSIGHT: International costs are added ON TOP.            |
    |  The vendor and platform make the SAME money.                  |
    |  The customer pays more, but gets the product delivered        |
    |  with ZERO surprise fees at their door.                        |
    |                                                                |
    +----------------------------------------------------------------+
```

### What about EU orders? (Coming July 2026)

```
    +----------------------------------------------------------------+
    |            EU ORDER EXAMPLE (GERMANY)                          |
    +----------------------------------------------------------------+
    |                                                                |
    |  Product:                                    $100.00           |
    |  International shipping:                      $28.00           |
    |  US sales tax (8%):                            $8.00           |
    |  German VAT (19%):                            $24.32           |
    |  EU customs duty (new July 2026):              $3.00*          |
    |  ─────────────────────────────────────                         |
    |  CUSTOMER TOTAL:                             $163.32           |
    |                                                                |
    |  * NEW: EU is abolishing the EUR 150 duty-free threshold       |
    |    on July 1, 2026. A flat EUR 3 per item duty applies.        |
    |    After 2028 this becomes full standard tariff rates.         |
    |                                                                |
    +----------------------------------------------------------------+
```

### What about other countries?

```
    +----------------------------------------------------------------+
    |          VAT / GST RATES BY DESTINATION                        |
    +----------------------------------------------------------------+
    |                                                                |
    |  Country/Region        VAT/GST Rate    On a $100 product       |
    |  ─────────────────     ────────────    ──────────────────      |
    |  United Kingdom           20%              $20.00              |
    |  Germany                  19%              $19.00              |
    |  France                   20%              $20.00              |
    |  Italy                    22%              $22.00              |
    |  Spain                    21%              $21.00              |
    |  Netherlands              21%              $21.00              |
    |  Sweden                   25%              $25.00              |
    |  Canada                 5-15%           $5-$15.00              |
    |  Australia                10%              $10.00              |
    |  Japan                    10%              $10.00              |
    |  Switzerland             8.1%              $8.10               |
    |                                                                |
    |  These are ALL paid by the customer at checkout.               |
    |  The platform and vendor margins stay the same.                |
    |                                                                |
    +----------------------------------------------------------------+
```

---

## 4. What It Costs to Make This Happen

```
    +================================================================+
    |                     COST SUMMARY                                |
    +================================================================+
    |                                                                |
    |  PRIORITY 1: FIX THE TAX BUG (URGENT)                         |
    |  ──────────────────────────────────────                        |
    |  Commission plugin code fix         FREE  (code change)       |
    |  Dokan tax setting toggle           FREE  (admin setting)     |
    |                                                                |
    |  PRIORITY 2: AUTOMATE US TAX RATES                            |
    |  ──────────────────────────────────────                        |
    |  Option A: WooCommerce Tax          FREE  (basic US rates)    |
    |  Option B: TaxJar                   $99/mo (full automation   |
    |                                            + auto-filing)     |
    |  Option C: Avalara                  $$$$   (enterprise, for   |
    |                                            if you scale big)  |
    |                                                                |
    |  PRIORITY 3: ADD INTERNATIONAL SHIPPING                        |
    |  ──────────────────────────────────────                        |
    |  Easyship plugin                    FREE  (WooCommerce plugin)|
    |  Easyship plan                      $29/mo (Plus — 500 ship) |
    |                                     $69/mo (Premier)          |
    |                                     $99/mo (Scale)            |
    |                                                                |
    |  What Easyship handles:                                        |
    |    ✓ Live carrier rates (550+ carriers worldwide)              |
    |    ✓ Duty + tax calculation at checkout                        |
    |    ✓ Customs documentation (auto-generated)                    |
    |    ✓ DDP (customer pays everything upfront)                    |
    |    ✓ Shipping label printing                                   |
    |    ✓ Ships to 220+ countries                                   |
    |                                                                |
    |  PRIORITY 4: EU COMPLIANCE (by July 2026)                      |
    |  ──────────────────────────────────────                        |
    |  IOSS registration                  FREE  (EU application)    |
    |  EU VAT plugin for WooCommerce      FREE  (several available) |
    |                                                                |
    +================================================================+
    |                                                                |
    |  MONTHLY COST SUMMARY                                          |
    |  ─────────────────────                                         |
    |                                                                |
    |  Domestic fix only:          $0 - $99/mo                       |
    |  (Free if using WooCommerce Tax, $99 for TaxJar)              |
    |                                                                |
    |  Domestic + International:   $29 - $198/mo                     |
    |  (Easyship + optional TaxJar)                                  |
    |                                                                |
    |  For context — one international sale of a $100 tool           |
    |  generates $15 in commission. Two international orders         |
    |  a month pays for Easyship.                                    |
    |                                                                |
    +================================================================+
```

---

## 5. Revenue Opportunity

```
    +================================================================+
    |              WHERE THE CUSTOMERS ARE                            |
    +================================================================+
    |                                                                |
    |  Guitar/luthier tools is a GLOBAL niche.                       |
    |  Right now, Loothtool only serves US customers.                |
    |                                                                |
    |  Global guitar market by region:                               |
    |                                                                |
    |  North America  ████████████████████████████  ~35%  (current) |
    |  Europe         ████████████████████████       ~30%  (blocked)|
    |  Asia Pacific   ████████████████               ~20%  (blocked)|
    |  Rest of World  ████████                       ~15%  (blocked)|
    |                                                                |
    |  By serving US only, we're reaching ~35% of the market.       |
    |  Adding international shipping opens the other ~65%.           |
    |                                                                |
    +================================================================+
    |                                                                |
    |  CONSERVATIVE REVENUE PROJECTION                               |
    |  ────────────────────────────────                              |
    |                                                                |
    |                     Current         With International         |
    |                     (US only)       (US + Global)              |
    |  ──────────────     ──────────      ──────────────             |
    |  Market reach       ~35%            ~100%                      |
    |  Monthly orders     [current]       +20-40% (conservative)    |
    |  Avg commission     $15/order       $15/order (same)          |
    |  New monthly cost   $0              +$29-99/mo                |
    |                                                                |
    |  Even a modest 10 extra international orders/month at          |
    |  $15 commission = $150/mo in new revenue against               |
    |  $29/mo in Easyship costs.                                     |
    |                                                                |
    |  ROI: positive from month one.                                 |
    |                                                                |
    +================================================================+
```

---

## 6. Recommended Action Plan

```
    +================================================================+
    |              ACTION ITEMS — PRIORITIZED                         |
    +================================================================+

    🔴 URGENT (This Week)
    ─────────────────────
    1. Fix the commission plugin
       - Stop sending tax money to vendors
       - Add a "Tax Recipient" setting (default: Admin)
       - This is a code change — no cost, just needs to be deployed

    2. Set Dokan's Tax Fee Recipient to "Admin"
       - Dokan → Settings → Selling Options → Tax Fee Recipient
       - One toggle in the admin panel

    3. Verify WooCommerce tax is enabled and configured
       - WooCommerce → Settings → Tax
       - Enable "Tax rates and calculations"

    🟡 IMPORTANT (This Month)
    ─────────────────────────
    4. Install tax rate automation
       - WooCommerce Tax (free) for basic US rates, OR
       - TaxJar ($99/mo) for full automation + nexus tracking

    5. Audit nexus exposure
       - Which states has Loothtool exceeded $100K or 200 transactions?
       - Register for sales tax in those states

    6. Set up tax-compliant invoicing
       - Install WooCommerce PDF Invoices (free plugin)

    🟢 OPPORTUNITY (Next 1-2 Months)
    ─────────────────────────────────
    7. Install Easyship for international shipping
       - WooCommerce plugin (free) + Easyship plan ($29/mo)
       - Gives us: live rates, duty/tax at checkout, customs docs,
         DDP labels, 550+ carriers, 220+ countries

    8. Enable DDP (Delivered Duty Paid) at checkout
       - Customers pay all duties/taxes upfront
       - Zero surprise fees at delivery = fewer returns/complaints

    🔵 FUTURE (Before July 2026)
    ─────────────────────────────
    9. Register for EU IOSS (Import One-Stop Shop)
       - Required for the new EU duty rules starting July 1, 2026
       - Free to register, simplifies VAT collection for EU sales

    10. Add EU VAT plugin
        - Handles VAT number validation for B2B buyers
        - Applies correct VAT rate per EU country
```

---

## 7. The Bottom Line

```
    +================================================================+
    |                    SUMMARY FOR IAN                              |
    +================================================================+
    |                                                                |
    |  1. THERE'S A BUG: Tax money is going to vendors instead      |
    |     of being held by the platform. Under marketplace           |
    |     facilitator law, Loothtool is liable. This needs           |
    |     fixing before anything else. Cost to fix: $0.              |
    |                                                                |
    |  2. DOMESTIC FIX: Change two settings + one code fix.          |
    |     Add TaxJar for $99/mo if you want automated rates          |
    |     and filing. Or start free with WooCommerce Tax.            |
    |                                                                |
    |  3. INTERNATIONAL OPPORTUNITY: For $29/mo (Easyship),          |
    |     Loothtool can sell to 220+ countries. The platform         |
    |     commission stays the same. International costs are         |
    |     paid by the customer at checkout. ROI is positive          |
    |     from the first month with just 2 orders.                   |
    |                                                                |
    |  4. NO PLATFORM MIGRATION NEEDED: We don't need to move       |
    |     to Shopify. Our current WooCommerce + Dokan setup          |
    |     is better suited for a multi-vendor marketplace.           |
    |     We just need to plug the tax gap and add Easyship.         |
    |                                                                |
    |  Total investment to go from "broken domestic only"             |
    |  to "compliant domestic + international":                      |
    |                                                                |
    |     Code fix:           $0                                     |
    |     Tax automation:     $0-99/mo                               |
    |     International:      $29/mo                                 |
    |     ─────────────────────────                                  |
    |     Total:              $29-128/mo                              |
    |                                                                |
    +================================================================+
```

---

*This document was prepared based on a technical audit of the Loothtool platform codebase, including the commission plugin, shipping plugin, WooCommerce configuration, and Dokan marketplace settings. All dollar examples use 15% commission rate and 8% sales tax for illustration. Actual rates vary by jurisdiction and product category.*
