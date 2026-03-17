# Loothtool Tax & VAT Audit — Multi-Vendor Marketplace

**Date:** 2026-03-17
**Stack:** WordPress + WooCommerce + Dokan + Block Checkout
**Custom plugins:** loothtool-commissions (split commission engine), loothtool-shipping (Shippo live rates)
**Business model:** Multi-vendor marketplace — luthiers sell handmade tools; some vendors are dealers for larger companies
**Product type:** Physical goods (shipped domestically + internationally)

---

## 1. CURRENT STATE — What Exists Today

### Theme-level tax code: **NONE**

After a full audit of `functions.php`, `single-product.php`, `archive-product.php`, `dokan/store.php`, and `seller-registration-form.php`:

- **Zero tax calculation logic** in the theme
- **Zero VAT number collection** at checkout or vendor registration
- **Zero tax-exempt handling** for B2B buyers
- **No tax display overrides** (prices show however WooCommerce is configured)
- **No geo-location based tax switching**
- **No digital goods tax rules** (if you ever sell digital products)

Everything depends on:
1. WooCommerce → Settings → Tax (admin panel config)
2. Dokan's commission/split settings
3. Whatever tax rates have been manually entered

### Commission plugin audit (`loothtool-commissions`): **TAX GOES TO VENDOR**

The custom commission plugin (`class-order-processor.php`) calculates vendor payouts as:

```php
$vendor_payout = round( $total_vendor_earn + $shipping_total + $tax_total - $processing_fees, 2 );
```

**This means tax collected from buyers is sent to vendors as part of their payout.** There is no admin setting to change this — it is hardcoded. The admin settings only cover platform commission %, commission type, and audit logging.

The commission calculator (`class-commission-calculator.php`) correctly excludes tax from the commission base (commissions are calculated on item subtotal only), but the order processor then adds the full tax amount back into the vendor's payout.

### Shipping plugin audit (`loothtool-shipping`): **OK — defers to WooCommerce**

The shipping plugin calculates live rates via Shippo and does NOT handle tax internally. Tax on shipping (where applicable) is handled by WooCommerce's core tax engine. This is correct behavior.

### Risk level: **CRITICAL**

The combination of issues creates a serious compliance gap:

1. **If WooCommerce tax IS enabled:** Tax money is collected from buyers but sent to vendors via the commission plugin. Unless vendors are independently remitting this tax (unlikely), **nobody is remitting it to tax authorities**.

2. **If WooCommerce tax is NOT enabled:** No tax is being collected at all, and you may owe back taxes in every state where you have nexus.

3. **Under marketplace facilitator laws:** YOU (Loothtool) are liable regardless of whether vendors received the tax money. If audited, you'd owe the tax plus penalties and interest.

---

## 2. TAX OBLIGATIONS — Where Loothtool Likely Owes Tax

### 2a. US Sales Tax (Domestic)

Since *South Dakota v. Wayfair (2018)*, states can require sales tax collection from remote sellers based on **economic nexus** — typically $100K in sales OR 200 transactions in a state.

**The problem for multi-vendor marketplaces:**
- **Marketplace facilitator laws** exist in 45+ US states
- These laws make the **marketplace operator** (you, Loothtool) responsible for collecting and remitting sales tax — NOT the individual vendors
- This applies even if vendors are the ones shipping

```
PSEUDOCODE — US Sales Tax Decision Tree:

FOR each order:
  buyer_state = order.shipping_address.state

  IF loothtool_has_nexus_in(buyer_state):
    tax_rate = get_rate(buyer_state, buyer_county, buyer_city, product_category)
    order.tax = order.subtotal * tax_rate

    // COMPLICATION: Rates vary by:
    // - State (0% to 7.25%)
    // - County (additional 0% to 5.5%)
    // - City (additional 0% to 4%)
    // - Product category (tools may be exempt in some states, taxable in others)
    // - Shipping taxability (varies by state!)

  ELSE:
    order.tax = 0  // But you MUST track revenue per state for nexus thresholds

  // CRITICAL: As a marketplace facilitator, YOU file returns, not vendors
```

**States with NO sales tax:** AK, DE, MT, NH, OR
**States where shipping IS taxable:** AR, CT, DC, GA, HI, IL, KS, KY, MI, MN, MS, NE, NJ, NM, NY, NC, ND, OH, PA, SD, TN, TX, VT, WA, WI, WV

### 2b. EU VAT (International — if selling to EU)

If ANY customers are in the EU:

```
PSEUDOCODE — EU VAT Decision:

FOR each order WHERE buyer.country IN eu_countries:

  IF buyer.is_business AND buyer.vat_number IS VALID:
    // B2B: Reverse charge applies — no VAT collected
    order.vat = 0
    order.note = "Reverse charge — buyer VAT: {vat_number}"
    // MUST validate VAT number via EU VIES API

  ELSE:
    // B2C: Charge VAT at buyer's country rate
    // Since July 2021: OSS (One-Stop Shop) scheme
    order.vat_rate = get_eu_vat_rate(buyer.country, product_type)
    order.vat = order.subtotal * order.vat_rate

    // Standard rates range: 17% (Luxembourg) to 27% (Hungary)
    // Some countries have reduced rates for certain goods

  // CRITICAL: Must show VAT-inclusive prices to EU consumers (legal requirement)
  // CRITICAL: Must issue invoices with VAT breakdown
  // CRITICAL: Must register for OSS in one EU member state OR register in each country
```

### 2c. UK VAT (Post-Brexit)

```
PSEUDOCODE — UK VAT:

FOR each order WHERE buyer.country == 'GB':

  IF order.value <= £135:
    // Seller must register for UK VAT and charge at point of sale
    order.vat = order.subtotal * 0.20  // 20% standard rate

  ELSE IF order.value > £135:
    // Import VAT charged at customs (buyer pays)
    // But you MUST provide accurate customs declarations
    order.vat = 0
    order.customs_declaration_required = true
```

### 2d. Canada GST/HST/PST

```
PSEUDOCODE — Canada:

FOR each order WHERE buyer.country == 'CA':
  province = buyer.province

  // Federal GST: 5% (all provinces)
  // HST: 13-15% (replaces GST+PST in ON, NB, NS, NL, PE)
  // PST: Varies (BC 7%, SK 6%, MB 7%, QC 9.975%)

  IF marketplace_revenue_in_canada > $30,000_CAD:
    // Must register and collect GST/HST
    order.tax = calculate_canadian_tax(province, product_type)
```

---

## 3. THE MULTI-VENDOR COMPLICATION (Dokan-Specific)

This is where your setup gets risky:

### 3a. Who Is the "Seller of Record"?

```
CRITICAL QUESTION:

Option A: Loothtool is the marketplace facilitator
  → YOU collect tax, YOU file returns, YOU are liable
  → Vendors receive their split MINUS your commission
  → Tax collected goes to YOU, not vendors
  → YOU remit to each tax authority

Option B: Each vendor is an independent seller
  → EACH vendor must collect/remit their own tax
  → You're just a "platform" — but most states don't see it this way anymore
  → Marketplace facilitator laws override this in 45+ US states

REALITY: For US sales, Option A applies in almost every state.
For international sales, it depends on the country.
```

### 3b. Dokan Tax Flow (Current Default Behavior)

```
PSEUDOCODE — How Dokan handles tax today:

order.subtotal = $100
order.tax = $8 (calculated by WooCommerce based on tax settings)
order.total = $108

// Dokan commission split:
dokan_commission = 10%  // example

IF dokan_tax_setting == "tax_to_admin":
  vendor_earnings = ($100 * 0.90)           = $90
  admin_earnings  = ($100 * 0.10) + $8 tax  = $18
  // Admin (you) is responsible for remitting the $8

ELSE IF dokan_tax_setting == "tax_to_seller":
  vendor_earnings = ($100 * 0.90) + $8 tax  = $98
  admin_earnings  = ($100 * 0.10)           = $10
  // DANGER: Vendor receives tax but may not remit it
  // YOU are still liable as marketplace facilitator!

ELSE IF dokan_tax_setting == "tax_split":
  // Tax is split proportionally (worst option — no one knows who owes what)
```

**RECOMMENDATION:** Set Dokan to **"tax_to_admin"** — you collect all tax, you remit all tax. This is the only defensible position under marketplace facilitator laws.

### 3b-CRITICAL. loothtool-commissions Plugin OVERRIDES Dokan's Tax Setting

**Even if you set Dokan to "tax_to_admin", your custom commission plugin ignores this.**

The `class-order-processor.php` hardcodes:
```php
$vendor_payout = round( $total_vendor_earn + $shipping_total + $tax_total - $processing_fees, 2 );
```

This always adds `$tax_total` to the vendor payout. The plugin has no awareness of Dokan's tax fee recipient setting.

```
PSEUDOCODE — What the commission plugin does vs. what it SHOULD do:

CURRENT (BROKEN):
  order.subtotal = $100
  order.tax = $8
  order.shipping = $12
  platform_commission = 10%

  vendor_item_earnings = $100 * 0.90 = $90
  vendor_payout = $90 + $12 (shipping) + $8 (tax) - $3 (processing) = $107
  platform_earnings = $100 * 0.10 = $10
  // TAX GOES TO VENDOR — platform has $0 for tax remittance!

CORRECT (NEEDS FIX):
  // Option A: Tax stays with platform (marketplace facilitator model)
  tax_recipient = get_option('lt_comm_tax_recipient', 'admin')

  IF tax_recipient == 'admin':
    vendor_payout = $90 + $12 (shipping) - $3 (processing) = $99
    platform_earnings = $10 + $8 (tax held for remittance) = $18
    // Platform remits $8 to tax authority

  ELSE IF tax_recipient == 'vendor':
    vendor_payout = $90 + $12 + $8 - $3 = $107
    // Vendor is responsible for remitting (risky but some want this)

  // Option B: Also make shipping recipient configurable
  // (In marketplace facilitator states, shipping tax goes to platform too)
```

**FIX REQUIRED in `loothtool-commissions`:**
1. Add `tax_recipient` setting to `class-admin-settings.php` (admin / vendor options)
2. Modify `class-order-processor.php` to check this setting
3. If `tax_recipient == 'admin'`, exclude `$tax_total` from vendor payout
4. Store tax retention in order meta for audit trail (`_lt_comm_tax_retained_by`)
5. Default should be `'admin'` for marketplace facilitator compliance

### 3c. Vendor-as-Dealer Problem

Some of your vendors are **dealers for larger companies** (not making their own tools). This adds complexity:

```
PSEUDOCODE — Dealer vs Maker Tax Implications:

FOR each vendor:
  IF vendor.type == "dealer":
    // They likely already charge sales tax on their own site
    // On YOUR marketplace, YOU are the facilitator — YOU still must collect
    // This could mean the end consumer is NOT double-taxed because:
    //   - The dealer sells to you/your platform at wholesale (no tax)
    //   - You sell to end consumer (you collect tax)
    // BUT if the dealer dropships directly, the tax nexus question gets messy

  IF vendor.type == "maker":
    // Simpler — they make it, you facilitate the sale, you collect tax
```

---

## 4. WHAT NEEDS TO BE BUILT / CONFIGURED

### 4a. Minimum Viable Tax Compliance (Priority Order)

```
STEP 1: WooCommerce Tax Settings (Admin Panel — no code needed)
  → Enable tax calculations
  → Set "Prices entered with tax" = No (for US) or Yes (for EU-facing)
  → Set "Display prices in shop" = Excluding tax (US) or Including tax (EU)
  → Set "Display prices during cart/checkout" = match above
  → Enable tax based on "Customer shipping address"

STEP 2: Tax Rate Automation (STRONGLY recommended)
  → Install a tax calculation service:
     - TaxJar (WooCommerce integration, $19+/mo)
     - Avalara AvaTax (enterprise-grade, $$)
     - WooCommerce Tax (free, powered by Jetpack — basic, US-only)
  → These auto-calculate rates by address in real-time
  → They handle jurisdiction lookups (state + county + city + district)
  → Some auto-file returns for you

STEP 3: Dokan Configuration
  → Set tax fee recipient = "Admin" (you collect all tax)
  → Verify commission calculations exclude tax from the split base
  → Test: create order, verify vendor sees earnings WITHOUT tax included

STEP 4: EU VAT (if selling internationally)
  → Install a VAT plugin:
     - "EU/UK VAT for WooCommerce" or similar
     - Adds VAT number field at checkout
     - Validates via VIES API
     - Applies reverse charge for valid B2B
  → Register for OSS (One-Stop Shop) in one EU country
  → OR use IOSS for imports under €150

STEP 5: Tax-Exempt Buyers
  → Some buyers (schools, non-profits, resellers) may be tax-exempt
  → Need: exemption certificate upload at checkout or account level
  → TaxJar and Avalara handle this natively

STEP 6: Invoicing
  → Every order needs a tax-compliant invoice showing:
     - Tax rate applied
     - Tax amount
     - Your tax registration number
     - Buyer's VAT number (if B2B)
  → WooCommerce PDF Invoices plugin or similar
```

### 4b. Theme-Level Code That SHOULD Exist

```php
PSEUDOCODE — Things to add to functions.php or a dedicated tax module:

// 1. Force tax display consistency in product cards
//    (Your templates use $product->get_price_html() which respects WC settings,
//     but you should verify this renders correctly with tax enabled)

// 2. Add VAT number field to Dokan seller registration
//    (Vendors who are businesses should provide their VAT/tax ID)
//    → Add to seller-registration-form.php

// 3. Show tax summary on vendor dashboard
//    (Vendors should see how much tax was collected on their sales,
//     even though YOU are remitting it — transparency prevents disputes)

// 4. Geo-locate and show correct price format
//    IF buyer_in_EU: show prices INCLUDING VAT with "incl. VAT" label
//    ELSE: show prices EXCLUDING tax
//    → WooCommerce has built-in geo-location, but needs configuration

// 5. Cart/checkout tax line item clarity
//    → Block checkout already shows tax lines if WC tax is enabled
//    → But verify it says "Sales Tax" for US and "VAT" for EU buyers
```

---

## 5. SHOPIFY COMPARISON — Is It Safer?

### What Shopify Does Better

| Feature | WooCommerce + Dokan (You) | Shopify |
|---------|--------------------------|---------|
| **US Sales Tax** | Manual setup OR paid plugin (TaxJar/Avalara) | **Built-in automatic tax calculation** (free, all US jurisdictions) |
| **Tax rate accuracy** | Depends on your plugin/data | **Rooftop-level accuracy** via partnerships with tax providers |
| **Nexus tracking** | You must track manually or use TaxJar | **Shopify Tax tracks your nexus automatically** ($0.35/order after 100K) |
| **Tax filing/remittance** | You file manually or pay TaxJar to auto-file | **No auto-filing** — same as WooCommerce, you still file yourself |
| **EU VAT** | Plugin needed (free or paid) | **Built-in for EU stores** — auto-applies country rates |
| **VAT MOSS/OSS** | Plugin + manual registration | **Built-in for EU sellers** |
| **Multi-vendor** | Dokan handles splits | **No native multi-vendor** — need an app (Sufio, Multi-Vendor Marketplace) which adds $49-$99/mo and has LESS marketplace facilitator support than Dokan |
| **Marketplace facilitator compliance** | Dokan's "tax_to_admin" setting handles this | Shopify multi-vendor apps are **weaker** here — most don't handle tax remittance responsibility clearly |
| **International duties/import tax** | Plugin needed | **Shopify Markets** — built-in duty/import tax calculation |
| **Tax-exempt certificates** | TaxJar/Avalara | Avalara integration available |
| **Cost** | Hosting + plugins (~$50-150/mo) | Shopify plan ($79-399/mo) + multi-vendor app ($49-99/mo) + Shopify Tax ($0.35/order) |

### Verdict: Is Shopify Safer?

**For a SINGLE-vendor store: YES, Shopify is objectively safer and easier for tax.**
- Built-in tax calculation is excellent
- Less configuration needed
- Shopify Tax + Shopify Markets covers most scenarios

**For YOUR multi-vendor marketplace: NO, Shopify is NOT clearly safer.**
Here's why:

1. **Shopify has no native multi-vendor support.** You'd need a third-party app (Multi Vendor Marketplace by Webkul, or similar), and these apps have LESS mature tax handling than Dokan.

2. **Marketplace facilitator compliance** is actually easier to configure correctly in Dokan (with the "tax_to_admin" setting) than in most Shopify multi-vendor apps.

3. **Cost would be significantly higher** — Shopify Advanced ($399/mo) + multi-vendor app ($99/mo) + Shopify Tax fees = $500+/mo vs your current ~$50-100/mo.

4. **Migration risk** — moving an established multi-vendor marketplace is a massive project with potential for data loss, SEO damage, and vendor churn.

### Where Shopify DOES Win

- **Automatic US tax rates** out of the box (WooCommerce needs TaxJar/Avalara)
- **Shopify Markets** for international duty calculation (WooCommerce needs plugins)
- **PCI compliance** is Shopify's responsibility, not yours
- **Less server/security maintenance** burden

---

## 6. RECOMMENDED ACTION PLAN

### URGENT — Fix the Commission Plugin (Before ANY orders process)
0. **FIX `loothtool-commissions` to stop sending tax to vendors.** This is the #1 priority. Add a `tax_recipient` setting and default it to `'admin'`. Without this fix, every order sends tax money to vendors where it likely never gets remitted.

### Immediate (This Week)
1. **Verify WooCommerce tax is enabled** — Settings → Tax → Enable tax rates and calculations
2. **Set Dokan tax recipient to "Admin"** — Dokan → Settings → Selling Options → Tax Fee Recipient
3. **Set loothtool-commissions tax recipient to "Admin"** — once the fix from step 0 is deployed
4. **Install WooCommerce Tax** (free) for basic US tax rates, or TaxJar ($19/mo) for full automation

### Short Term (This Month)
4. **Audit your nexus** — In which states do you (or your vendors) have physical presence or have exceeded $100K/200 transactions?
5. **Register for sales tax** in states where you have nexus
6. **Install an invoicing plugin** — WooCommerce PDF Invoices & Packing Slips (free)

### Medium Term (If Selling Internationally)
7. **Add EU VAT plugin** with VIES validation
8. **Register for OSS** if EU sales exceed €10,000/year
9. **Add VAT number field** to Dokan vendor registration form
10. **Configure geo-located price display** (incl. VAT for EU, excl. tax for US)

### Long Term
11. **Consider TaxJar or Avalara** for auto-filing returns
12. **Build vendor tax dashboard** showing collected tax per vendor (transparency)
13. **Add tax-exempt certificate handling** if you get B2B/wholesale buyers

---

## 7. BOTTOM LINE

**Your biggest risk right now is not WooCommerce vs Shopify — it's having tax collection potentially misconfigured or disabled entirely.**

A properly configured WooCommerce + Dokan + TaxJar setup is **equally compliant** to Shopify for your use case, and **better suited** to multi-vendor because Dokan's marketplace facilitator model is mature.

The key things that could get you hit with fees/penalties:
- **Not collecting sales tax** in states where you have nexus
- **Not filing returns** in states where you're registered
- **Incorrect tax rates** (wrong jurisdiction, wrong product category)
- **Not collecting VAT** on EU sales (if applicable)
- **Tax money going to vendors** instead of being remitted by you (wrong Dokan setting)

**Don't switch to Shopify for tax reasons.** Instead, invest the $19-99/mo in a proper tax automation plugin and make sure Dokan's tax recipient is set to "Admin."
