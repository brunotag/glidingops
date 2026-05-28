# Fees Reference

**Source:** `tmp/Fees-10-Nov-2025.pdf` — Schedule of Fees effective 10 November 2025
**Note:** All prices include GST. Some fees are not refundable. Fees are subject to change.

---

## Membership

| Category | Club Subscription | GNZ Affiliation & Comms Levy | Total |
|---|---|---|---|
| Full Flying | $1,200 | $150 | $1,350 |
| Youth | $165 | $55 | $220 |
| Additional Family Member | $100 | $150 | $250 |
| Associate | $100 | - | $100 |
| Short-term Flying Membership | $150 per month | - | |

### Visiting NZ Pilots

| Category | Club Subscription | GNZ Affiliation & Comms Levy | Total |
|---|---|---|---|
| Up to 30 days flying | No charge | Paid by Primary Club | - |
| After 30 days flying | $150 per month | Paid by Primary Club | - |
| After 60 days flying | $1,200 | Paid by Primary Club | $1,200 |

### Visiting Foreign Pilots

| Category | Club Subscription | GNZ Affiliation & Comms Levy | Total |
|---|---|---|---|
| P2 - Up to 6 flights | No charge | Does not apply | - |
| PIC/P2 - After 6 flights as P2 or any flight as PIC, within 60 days | $150 per month | $150 for 1 year | $300 |
| PIC/P2 - after 60 days | $1,200 for 1 year | $150 for 1 year | $1,350 |

### Membership Notes

- Full Flying and Youth subscriptions charged in four quarterly instalments.
- Other annual club subscriptions fully charged in July.
- GNZ affiliation fee billed to members in November.
- Club rules apply to all membership categories.

---

## Gliders

| Type | Full Flying Members | Youth Members |
|---|---|---|
| Club Gliders (GGR, GPJ, GMB) | $2.25 per minute | $1.50 per minute |
| Club Glider (GNB) | $2.25 per minute | $2.25 per minute |
| Club Glider (GNB) - Syndicate members | $0.60 per minute | |
| DG 1000s Off Site Events | $270.00 per day (2 hrs) | $270.00 per day |

---

## Launching

| Type | Full Flying Members | Youth Members |
|---|---|---|
| Glider winch launches by members - 1st launch of the day | $39 | $39 |
| Glider winch launches by members - Relaunches same day | $25 | $25 |
| Glider launches by non-members | $50 | $50 |
| Power plane or self-launch glider* | $25 per launch | $25 per launch |
| Aerotow launch from Papawai** | $25 per launch | $25 per launch |

### Launching Notes

- *If paid Power Aircraft Landing Fee there is no fee for Launching.
- **Aerotow launch fee excludes tow-plane charge (additional).

---

## Packages

| Type | Adults (over 25) | Youth (25 or under) |
|---|---|---|
| Greytown Taster | $190 | $110 |
| Introduction to Flight - One Day Course | $390 | $195 |

---

## Campground & Storage

| Item | Member's Price | Non-Member's Price |
|---|---|---|
| Tent | $10 per night | $15 per night |
| Club Caravan | $15 per night | $20 per night |
| Caravan Plot - Daily/Casual | $10 per night | $15 per night |
| Caravan Plot - Extra Person | $10 per night | $15 per night |
| Caravan Plot - Semi Permanent | $300 per 3 months (incl 10 overnight member stays) | |
| Caravan Plot - Permanent | $600 per year (incl 20 overnight stays for member) | |
| Glider Trailer Parking - Permanent | $220 per year | |
| Glider Trailer Parking - Casual | $50 per month | |
| Shipping Container Plot | $250 per year | |
| Camper / Vehicle Storage | $150 per year (cannot be used for accommodation) | |

---

## Merchandise

| Item | Price |
|---|---|
| T-Shirt (out of stock) | $45.00 |
| Polo Shirt (out of stock) | $45.00 |
| Long Sleeve Shirt (out of stock) | $130.00 |
| Jacket | $95.00 |
| Bucket Hat | $25.00 |
| Baseball Cap | $40.00 |
| Introductory Packs (Logbook, Training Program, etc) | $70.00 |

---

## Other Fees

| Item | Price |
|---|---|
| GNZ Glider Ownership Levy | $125 per glider |
| WWGC Power Aircraft Landing Fee | $25 per landing |

### Insurance Excess

In the event of an accident causing damage to a club glider, the PIC will be expected to contribute towards the insurance excess payable by the Club.

### Wheel-up Landing

- **No damage:** $100 penalty payable to the Club.
- **Damage occurred:** PIC liable for cost of repairs at a level decided by the Committee.

---

## Membership Definitions

| Type | Age | Privileges | Conditions |
|---|---|---|---|
| Full Flying | 26 or over | Full | Must be 26+ on joining |
| Youth | 25 and under | Full | Must be under 26 on joining and under 26 on 31 Oct each year thereafter |
| Additional Family Member (Flying) | 14 or over | Full | Resides permanently with a Full Flying member who is parent, partner or sibling |
| Visiting NZ Pilots (Short Term) | 14 or over | Full | Must be GNZ member of another NZ club; cleared by CFI; Short-Term after 30 days; Full fee after 60 days |
| Visiting Foreign Pilots | - | - | P2: no conditions up to 6 flights in 6 months. PIC: GNZ Visiting Foreign Pilot Registration form required |
| Associate | All ages | Full except flying | Required for Winch Drivers / Tow Pilots who are not Full Flying Members |

---

## Campground & Storage Definitions

| Item | Definition |
|---|---|
| Member | Any WWGC member (excludes other clubs). Children 10 or under free. |
| Club's facilities | Kitchen, showers, wi-fi, washing machines |
| Casual stays | Max 20 days per calendar year. Additional charge may apply beyond 20 consecutive days. |
| Tent | Private tent on site. Charged per person per night. Includes club facilities. Casual only. |
| Club Caravan | Club caravan bed per person per night. No exclusive use. Includes club facilities. Casual only. |
| Caravan Plot - Semi-Permanent | Private caravan up to 3 months. Quarterly fee in advance. Owner + immediate family: free club facilities up to 10 nights/year. Non-immediate family pay extra person charge. |
| Caravan Plot - Permanent | Private caravan all year. Annual fee. Owner + immediate family: free club facilities up to 20 nights/year. Longer stays negotiable. |
| Camper / Vehicle Storage | Private camper/vehicle parked all year. Does NOT include club facilities. |

---

## Implications for Billing Code

This fee schedule is the **ground truth** for what members should be charged. The current billing code (`accountrules.php`, `CalcTowCharge`, `CalcGliderCharge`, etc.) needs to be audited against these rates.

### Key Questions for Future Work

1. **Glider rates** - $2.25/min for most club gliders, $1.50/min for Youth (except GNB). Does the code handle per-aircraft and per-member-class rates correctly?
2. **Winch launches** - $39 first, $25 relaunch. Youth pay the same. Is this reflected?
3. **GNB Syndicate** - $0.60/min special rate. Is there a mechanism for syndicate member pricing?
4. **Membership tiers** - Quarterly vs annual billing. Does the system support the payment schedule?
5. **GNZ levy** - Separate line item billed in November. Is this tracked separately?
6. **Launch fees** - $25 for power/self-launch and aerotow from Papawai. These differ from standard winch pricing.
7. **Youth cutoff** - Age 25 and under on 31 Oct each year. Is this check implemented anywhere?

### What Needs Building

- A billing engine that applies the correct rate based on: member class, aircraft, launch type, and date
- Per-aircraft rate overrides (GNB syndicate)
- Quarterly instalment tracking for subscriptions
- Separate GNZ affiliation fee billing cycle (November)
