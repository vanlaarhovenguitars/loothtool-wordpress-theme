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

---

## 8. LEGAL FRAMEWORK REFERENCE — Marketplace Tax & VAT Laws

This section documents the specific laws, directives, and frameworks that create Loothtool's tax obligations as a multi-vendor marketplace.

### 8a. EU Deemed Supplier Rules (Article 14a, VAT Directive)

**Legal basis:** Council Directive (EU) 2017/2455 (December 2017) and Council Directive (EU) 2019/1995 (November 2019) inserted **Article 14a** into the principal **VAT Directive 2006/112/EC**. Effective **1 July 2021**.

**When does a marketplace become the "deemed supplier"?**

Two trigger scenarios:

1. **Article 14a(1) — Imported low-value goods:**
   - Marketplace facilitates distance sales of goods imported from outside the EU
   - Consignment value **≤ EUR 150**
   - Applies regardless of seller establishment (EU or non-EU)
   - Applies to B2C primarily

2. **Article 14a(2) — Intra-EU supplies by non-EU sellers:**
   - Non-EU-established seller makes B2C supplies of goods already within the EU via the marketplace
   - **No value threshold** — applies to goods of any value
   - Only applies to **B2C** (sales to non-taxable persons)

**The two-supply fiction:** When Article 14a applies, the single sale is split into:
1. A **B2B supply** from the underlying seller to the marketplace (exempt from VAT under Article 136a)
2. A **B2C supply** from the marketplace to the end consumer (marketplace collects and remits VAT)

VAT becomes chargeable at the time payment is accepted (Article 66a).

```
LOOTHTOOL APPLICABILITY:

IF loothtool_vendor.established_in == "non-EU"
  AND buyer.country IN eu_countries
  AND buyer.is_business == false:
    → Article 14a(2) applies
    → Loothtool is the DEEMED SUPPLIER
    → Must collect VAT at buyer's country rate
    → Must report via OSS

IF loothtool_vendor.ships_from == "outside EU"
  AND consignment_value <= EUR 150:
    → Article 14a(1) applies
    → Loothtool is the DEEMED SUPPLIER
    → Must collect VAT via IOSS

IF loothtool_vendor.established_in == "EU"
  AND goods_located_in == "EU":
    → Article 14a does NOT apply
    → Vendor handles own VAT (standard WooCommerce tax rules)
    → Loothtool still has OSS obligations for cross-border EU sales
```

**What qualifies as "facilitating" a supply?** A marketplace qualifies if it:
- Sets terms/conditions for the sale
- Processes or enables customer payments
- Handles ordering/delivery

It does NOT qualify if it merely:
- Processes payments
- Lists or advertises goods
- Redirects customers without further involvement

**Loothtool clearly qualifies** — we set terms, process payments, and manage ordering.

### 8b. ViDA Reforms (VAT in the Digital Age)

**Status:** Adopted by EU Council on **11 March 2025**, published 25 March 2025, entered into force **14 April 2025**.

Key changes relevant to Loothtool:

| Change | Date | Impact |
|--------|------|--------|
| Extension to accommodation/transport platforms | 1 July 2028 (voluntary) / 1 Jan 2030 (mandatory) | Not directly relevant (physical goods) |
| Proposed extension to EU sellers | **Dropped** | Loothtool not affected — EU vendor exemption stays |
| Proposed removal of EUR 150 threshold | Under discussion | Would make ALL imported goods subject to deemed supplier rules |
| B2B extension | Under discussion | Deemed supplier may extend to B2B marketplace transactions |
| Single VAT Registration | 1 July 2028 | Simplifies compliance — one registration covers all EU |
| Mandatory digital reporting (intra-EU B2B) | 1 July 2030 | New reporting requirements |

**What to watch:** If the EUR 150 threshold is removed, every import facilitated by Loothtool (regardless of value) would trigger deemed supplier status. Plan for this.

### 8c. UK Deemed Reseller Rules (HMRC)

**Effective:** 1 January 2021 (predates EU rules by 6 months).

| Scenario | Threshold | Who collects VAT? |
|----------|-----------|-------------------|
| Goods in UK, non-UK seller | **No threshold** (any value) | Marketplace |
| Goods outside UK, value ≤ GBP 135 | GBP 135 | Marketplace charges at point of sale |
| Goods outside UK, value > GBP 135 | GBP 135 | Import VAT at customs (buyer pays) |
| UK-based seller | N/A | Seller (marketplace NOT currently responsible) |

**Joint and several liability:** HMRC can hold marketplaces jointly and severally liable for unpaid VAT if the platform knew or should have known that an overseas seller was required to register but hadn't.

**Platform data reporting (from January 2024):** Digital platforms must share detailed sales data with HMRC — seller names, addresses, dates of birth, tax IDs. First reporting deadline was 31 January 2025 for 2024 transactions.

**Proposed extension:** Amazon has lobbied to extend deemed reseller to **all** sellers (including UK-established). If adopted, this would affect Loothtool's UK-based vendors. Independent analysis estimates GBP 3.2 billion in annual marketplace sales by VAT-evading sellers; estimated revenue capture GBP 700 million/year.

```
LOOTHTOOL UK OBLIGATIONS:

FOR each order WHERE buyer.country == 'GB':
  IF vendor.established_outside_uk AND goods_in_uk:
    → Loothtool is deemed reseller for ALL values
    → Collect 20% VAT at point of sale

  IF vendor.established_outside_uk AND goods_outside_uk:
    IF order.value <= GBP 135:
      → Loothtool collects VAT at point of sale
    ELSE:
      → Import VAT at customs (buyer responsibility)
      → Loothtool must provide customs declarations

  IF vendor.established_in_uk:
    → Vendor handles own VAT (for now)
    → Monitor proposed extension to all sellers
```

### 8d. US Marketplace Facilitator Laws

**Legal origin:** Supreme Court decision in *South Dakota v. Wayfair, Inc.* (2018) — states can require sales tax collection from businesses without physical presence.

**Current status:** All states with a sales tax have enacted marketplace facilitator laws.

**Common threshold:** $100,000 in sales OR 200 transactions per state per year (varies by state).

**Key state variations:**

| State | Notes |
|-------|-------|
| Alabama | Choice between 8% simplified sellers use tax OR reporting/notification |
| Alaska | No state sales tax; local municipalities collect via ARSSTC ($100K/200 threshold) |
| Colorado | Home-rule cities develop own marketplace facilitator laws separately |
| Florida | Must actually collect payment from customer to qualify as facilitator |
| DE, MT, NH, OR | No sales tax |

**Seller obligations remain:** Vendors are still responsible for collecting/remitting on sales made OUTSIDE the marketplace (their own website, trade shows, physical stores). Some states require sellers to register independently if they exceed economic nexus thresholds on their own.

```
LOOTHTOOL US OBLIGATIONS:

Loothtool is a marketplace facilitator in ALL states with MF laws.

FOR each state:
  IF loothtool_annual_sales(state) > nexus_threshold(state):
    → Must register in that state
    → Must collect sales tax on ALL orders shipped to that state
    → Must file returns and remit tax to that state
    → Vendors are relieved of collection duty for marketplace sales

  Track nexus thresholds PER STATE — some are $100K, some are $500K
  Track by CALENDAR YEAR or TRAILING 12 MONTHS (varies by state)
```

### 8e. Canada GST/HST/PST

Canada requires non-resident vendors and marketplace operators to register for GST/HST if annual revenue from Canadian consumers exceeds **CAD 30,000** over a 12-month period. Marketplace operators collecting and remitting GST/HST relieve underlying sellers of that obligation (similar to US marketplace facilitator model).

---

## 9. HOW MAJOR PLATFORMS HANDLE DEEMED SUPPLIER

### Amazon
- Acts as deemed supplier for B2C sales by non-EU sellers to EU consumers
- Sale split: seller deemed to sell to Amazon in warehouse (VAT-exempt B2B), Amazon sells to consumer (B2C, VAT collected)
- Sellers must still declare exempt sale on VAT returns and remain registered
- In US: collects and remits sales tax in all states with marketplace facilitator laws

### Etsy
- Collects/remits VAT on EU imports ≤ EUR 150 and UK imports ≤ GBP 135
- Higher-value imports: buyer pays VAT/customs at delivery
- Collects VAT on all digital items where it is deemed supplier
- In US: collects/remits state sales tax under marketplace facilitator laws
- Sellers remain responsible for orders above thresholds

### Key Takeaway for Loothtool
These platforms built bespoke tax systems costing millions. Loothtool's path is:
1. Use WooCommerce tax engine + automated tax service (TaxJar/Avalara) for rate calculation
2. Fix the commission plugin to retain tax at platform level
3. Configure Dokan's tax recipient correctly
4. Add vendor establishment tracking for deemed supplier determination

---

## 10. WOOCOMMERCE MARKETPLACE TAX GAP ANALYSIS

### What Exists Today (Plugin Ecosystem)

| Plugin | What It Does | What It Doesn't Do |
|--------|-------------|-------------------|
| **European VAT Compliance Assistant** (WP Overnight) | Place-of-supply rules, OSS/IOSS reporting, EUR 150/GBP 135 thresholds | Does NOT implement deemed supplier two-supply fiction |
| **EU VAT Number** (WooCommerce) | Validates VAT numbers at checkout, removes VAT for B2B | Single-vendor only — no marketplace awareness |
| **IOSS EU VAT for WooCommerce** | Import One Stop Shop reporting for low-value imports | No deemed supplier logic |
| **Dokan Tax Fee Recipient** | Routes tax to admin or vendor | No seller establishment tracking, no split invoicing |

### What Loothtool Would Need for Full Compliance

```
REQUIREMENTS — Deemed Supplier Implementation:

1. SELLER ESTABLISHMENT TRACKING
   → Add field to Dokan vendor profile: "Country of establishment"
   → Distinguish EU-established, UK-established, non-EU/UK
   → This determines whether Article 14a applies per-order

2. AUTOMATIC DEEMED SUPPLIER LOGIC
   → Per-order-item determination:
      IF article_14a_applies(vendor, buyer, goods_location):
        → Platform collects VAT on B2C leg
        → Generate exempt B2B documentation for seller→platform leg
      ELSE:
        → Standard WooCommerce tax handling

3. OSS/IOSS REGISTRATION
   → Platform itself registers for OSS/IOSS (not individual sellers)
   → Platform files consolidated returns

4. SPLIT INVOICING
   → Single order may contain items from multiple vendors
   → Some items trigger deemed supplier, others don't
   → Each requires different invoice treatment

5. RECORD KEEPING
   → 10-year retention of detailed records
   → Available electronically on request to tax authorities
```

**Reality check:** Full deemed supplier implementation is a significant custom development effort. For Loothtool's current scale, the pragmatic approach is:

1. **Fix the commission plugin** (immediate — stops tax leaking to vendors)
2. **Install TaxJar/Avalara** (this week — accurate rate calculation)
3. **Add vendor country field to Dokan** (short term — enables future deemed supplier logic)
4. **Build deemed supplier logic only when EU/UK sales volume warrants it** (medium term)

---

## 11. LEGAL REFERENCES

| Reference | Description |
|-----------|-------------|
| **VAT Directive 2006/112/EC, Article 14a** | Deemed supplier provision for marketplaces |
| **VAT Directive 2006/112/EC, Article 136a** | VAT exemption for deemed B2B supply (seller → platform) |
| **VAT Directive 2006/112/EC, Article 66a** | VAT chargeability at time of payment acceptance |
| **Council Directive (EU) 2017/2455** | Introduced deemed supplier rules |
| **Council Directive (EU) 2019/1995** | Refined the rules |
| **ViDA package** (adopted 11 March 2025) | Extends deemed supplier to accommodation/transport; future reforms |
| **South Dakota v. Wayfair, Inc.** (2018) | US Supreme Court — states can require remote seller tax collection |
| **HMRC guidance** (updated 20 June 2025) | UK marketplace VAT obligations for overseas sellers |

### Source URLs

**EU legislation:**
- Council Directive 2017/2455: https://eur-lex.europa.eu/eli/dir/2017/2455/oj/eng
- Council Directive 2019/1995: https://eur-lex.europa.eu/eli/dir/2019/1995/oj/eng
- VAT Directive consolidated text: https://eur-lex.europa.eu/legal-content/EN/TXT/PDF/?uri=CELEX:02006L0112-20220701
- EC Explanatory Notes on VAT e-Commerce: https://vat-one-stop-shop.ec.europa.eu/system/files/2021-07/vatecommerceexplanatory_notes_28102020_en.pdf
- ViDA adoption announcement: https://taxation-customs.ec.europa.eu/news/adoption-vat-digital-age-package-2025-03-11_en

**UK:**
- HMRC — VAT and overseas goods via online marketplaces: https://www.gov.uk/guidance/vat-and-overseas-goods-sold-to-customers-in-the-uk-using-online-marketplaces
- HMRC — Overseas businesses using online marketplaces: https://www.gov.uk/guidance/vat-overseas-businesses-using-an-online-marketplace-to-sell-goods-in-the-uk
- ICAEW — Extending deemed reseller rules: https://www.icaew.com/insights/tax-news/2025/nov-2025/extending-deemed-reseller-rules-to-combat-vat-fraud-can-it-work

**US:**
- Avalara — State-by-state marketplace facilitator laws: https://www.avalara.com/us/en/learn/guides/state-by-state-guide-to-marketplace-facilitator-laws.html
- TaxJar — Marketplace facilitator laws explained: https://www.taxjar.com/sales-tax/marketplace-facilitator-laws
- Stripe — Marketplace tax obligations guide: https://stripe.com/guides/understanding-the-tax-obligations-of-marketplaces-in-the-us

**Platform-specific:**
- Etsy customs/VAT collection: https://help.etsy.com/hc/en-us/articles/360000337247-Custom-Fees-and-Physical-VAT-Collection
- Amazon as deemed reseller (Minefield Navigator): https://minefieldnavigator.com/en/knowledge-base/amazon-as-deemed-reseller
- Non-EU merchants VAT registration under deemed supplier: https://www.essentiaglobalservices.com/why-non-eu-merchants-selling-on-online-marketplaces-under-deemed-supplier-model-are-still-required-to-vat-register-in-eu/

**WooCommerce/Dokan:**
- WP Overnight EU VAT Compliance: https://wpovernight.com/downloads/woocommerce-eu-vat-compliance/
- WooCommerce IOSS EU VAT Plugin: https://woocommerce.com/products/ioss-eu-vat-for-woocommerce/
- Dokan marketplace tax guidance: https://wedevs.com/blog/92782/know-your-marketplace-tax/

**Analysis & commentary:**
- Sovos — Marketplace facilitator tax collection in the EU: https://sovos.com/blog/vat/marketplace-facilitator-tax-collection-responsibilities-in-the-european-union/
- PwC — ViDA overview: https://www.pwc.lu/en/newsletter/2025/vat-in-the-digital-age-vida.html
- eClear — ViDA guide for marketplaces: https://eclear.com/vida-for-marketplaces-and-platforms/
- Taxmatic — VAT for online marketplaces guide: https://www.taxmatic.com/wp-content/uploads/2024/08/VAT-for-Online-Marketplaces-and-Platforms-Taxmatic-Guide.pdf
