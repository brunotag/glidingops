#!/usr/bin/env python3
"""
Weather-Flight Correlation Analysis

Correlates daily flight counts from the GlidingOps database with historical
weather data from Open-Meteo for Greytown, Wairarapa.

Usage:
    python weather-correlation.py [--port 33060] [--start 2016-06-01]

Output goes to _analytics-scripts/output/ (CSVs + PNGs).
"""

import argparse
import json
import os
import re
import sys
import warnings
from datetime import date, datetime, timedelta

warnings.filterwarnings("ignore", message="pandas only supports SQLAlchemy")
from pathlib import Path

import matplotlib
matplotlib.use("Agg")
import matplotlib.dates as mdates
import matplotlib.pyplot as plt
import numpy as np
import pandas as pd
import pymysql
import requests

# ---------------------------------------------------------------------------
# Config
# ---------------------------------------------------------------------------

SCRIPT_DIR = Path(__file__).resolve().parent
PROJECT_DIR = SCRIPT_DIR.parent
CONFIG_FILE = PROJECT_DIR / "config" / "database.php"
OUTPUT_DIR = SCRIPT_DIR / "output"
CACHE_FILE = OUTPUT_DIR / "weather_cache.json"
START_DATE = "2016-06-01"

# Greytown, Wairarapa coordinates
LAT, LON = -41.08, 175.47

# Weather thresholds
PRECIP_THRESHOLD_MM = 0.5
WIND_THRESHOLD_KMH = 55.56

# Open-Meteo fields
DAILY_PARAMS = [
    "precipitation_sum",
    "rain_sum",
    "wind_speed_10m_max",
    "wind_gusts_10m_max",
    "cloud_cover_mean",
]

# Seaborn-style color palette
PALETTE = ["#4363d8", "#e6194b", "#3cb44b", "#f58231", "#911eb4", "#42d4f4"]


# ---------------------------------------------------------------------------
# DB helpers
# ---------------------------------------------------------------------------

def parse_php_config(path):
    text = path.read_text(encoding="utf-8")
    m = re.search(r"'gliding'\s*=>\s*\[(.*?)\]", text, re.DOTALL)
    if not m:
        raise RuntimeError("Could not find 'gliding' DB config in " + str(path))
    kv = {}
    for k, v in re.findall(r"'(username|password|hostname|dbname)'\s*=>\s*'([^']*)'", m[1]):
        kv[k] = v
    return kv


def load_db_config():
    if not CONFIG_FILE.exists():
        print(f"[!] Config file not found: {CONFIG_FILE}")
        print("    Falling back to defaults (homestead/secret on localhost).")
        return {"hostname": "localhost", "username": "homestead", "password": "secret", "dbname": "gliding"}
    cfg = parse_php_config(CONFIG_FILE)
    print(f"[*] Read DB config from {CONFIG_FILE}")
    return cfg


# ---------------------------------------------------------------------------
# Weather fetch (Open-Meteo Historical API)
# ---------------------------------------------------------------------------

def already_cached_weather():
    if CACHE_FILE.exists():
        data = json.loads(CACHE_FILE.read_text(encoding="utf-8"))
        print(f"[*] Loaded cached weather ({len(data)} days)")
        return data
    return None


def fetch_weather_from_api(start_str, end_str):
    url = "https://archive-api.open-meteo.com/v1/archive"
    params = {
        "latitude": LAT,
        "longitude": LON,
        "start_date": start_str,
        "end_date": end_str,
        "daily": ",".join(DAILY_PARAMS),
        "timezone": "Pacific/Auckland",
    }
    print(f"[*] Fetching weather from Open-Meteo ({start_str} to {end_str})...")
    resp = requests.get(url, params=params, timeout=60)
    resp.raise_for_status()
    data = resp.json()
    daily = data.get("daily", {})
    dates = daily.get("time", [])
    result = {}
    for i, d in enumerate(dates):
        result[d] = {k: (daily[k][i] if daily.get(k) and i < len(daily[k]) else None) for k in DAILY_PARAMS}
    print(f"[*] Received {len(result)} days of weather data")
    return result


# ---------------------------------------------------------------------------
# DB query
# ---------------------------------------------------------------------------

def query_flights_from_db(cfg, port):
    print(f"[*] Connecting to MySQL at {cfg['hostname']}:{port} ...")
    conn = pymysql.connect(
        host=cfg["hostname"],
        port=port,
        user=cfg["username"],
        password=cfg["password"],
        database=cfg["dbname"],
        charset="utf8mb4",
    )
    sql = """
        SELECT localdate, pic, p2, towpilot
        FROM flights
        WHERE org = 1 AND deleted = 0 AND type = 1
        ORDER BY localdate
    """
    raw = pd.read_sql(sql, conn)
    conn.close()

    rows = []
    for ld, grp in raw.groupby("localdate"):
        members = set()
        for col in ["pic", "p2", "towpilot"]:
            for val in grp[col].dropna().unique():
                if val > 0:
                    members.add(int(val))
        rows.append({"localdate": str(ld), "flight_count": len(grp), "distinct_people": len(members)})
    df = pd.DataFrame(rows)
    print(f"[*] Queried {len(df)} days with flight data")
    return df


# ---------------------------------------------------------------------------
# Merge & analyse
# ---------------------------------------------------------------------------

def _parse_date(s):
    if isinstance(s, date):
        return s
    s = str(s).replace("-", "")
    return date(int(s[:4]), int(s[4:6]), int(s[6:8]))


def merge_and_analyze(flights_df, weather_dict, start_date=None):
    rows = []
    start = _parse_date(start_date or START_DATE)
    end = date.today()
    day = start
    while day <= end:
        ds = day.isoformat()
        yyyymmdd = day.strftime("%Y%m%d")
        cnt = 0
        people = 0
        match = flights_df[flights_df["localdate"] == yyyymmdd]
        if not match.empty:
            cnt = int(match.iloc[0]["flight_count"])
            people = int(match.iloc[0]["distinct_people"])
        w = weather_dict.get(ds, {})
        precip = w.get("precipitation_sum") or 0
        rain = w.get("rain_sum") or 0
        wind = w.get("wind_speed_10m_max") or 0
        gust = w.get("wind_gusts_10m_max") or 0
        cloud = w.get("cloud_cover_mean")
        wet_day = precip > PRECIP_THRESHOLD_MM
        windy_day = wind > WIND_THRESHOLD_KMH
        flyable = not (wet_day or windy_day)
        missed = flyable and cnt == 0
        season = day.year if day.month >= 6 else day.year - 1
        rows.append({
            "date": ds,
            "localdate": int(yyyymmdd),
            "year": day.year,
            "season": season,
            "month": day.month,
            "dayofweek": day.weekday(),
            "is_weekend": day.weekday() >= 5,
            "flight_count": cnt,
            "distinct_people": people,
            "had_flights": cnt > 0,
            "precipitation_mm": round(precip, 1),
            "rain_mm": round(rain, 1),
            "wind_max_kmh": round(wind, 1),
            "wind_gust_max_kmh": round(gust, 1),
            "cloud_cover_mean_pct": round(cloud, 1) if cloud is not None else None,
            "wet_day": wet_day,
            "windy_day": windy_day,
            "flyable": flyable,
            "missed_opportunity": missed,
        })
        day += timedelta(days=1)
    df = pd.DataFrame(rows)
    print(f"[*] Merged dataset: {len(df)} days, {df['flight_count'].sum():.0f} total flights")
    return df


# ---------------------------------------------------------------------------
# Export
# ---------------------------------------------------------------------------

def export_csvs(df, prefix=""):
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

    out1 = OUTPUT_DIR / f"{prefix}merged_data.csv"
    df.to_csv(out1, index=False)
    print(f"[+] {out1}")

    monthly = df.groupby(["year", "month"]).agg(
        flight_count=("flight_count", "sum"),
        flyable_days=("flyable", "sum"),
        missed_days=("missed_opportunity", "sum"),
        avg_precip=("precipitation_mm", "mean"),
        avg_wind=("wind_max_kmh", "mean"),
    ).reset_index()
    monthly["month"] = monthly["month"].apply(lambda m: f"{m:02d}")
    monthly["period"] = monthly["year"].astype(str) + "-" + monthly["month"]
    out2 = OUTPUT_DIR / f"{prefix}monthly_summary.csv"
    monthly.to_csv(out2, index=False)
    print(f"[+] {out2}")

    dow = df.groupby("dayofweek").agg(
        avg_flights=("flight_count", "mean"),
        flyable_pct=("flyable", "mean"),
        avg_precip=("precipitation_mm", "mean"),
        avg_wind=("wind_max_kmh", "mean"),
        total_days=("date", "count"),
    ).reset_index()
    labels = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"]
    dow["day_name"] = dow["dayofweek"].apply(lambda x: labels[x])
    out3 = OUTPUT_DIR / f"{prefix}dayofweek_summary.csv"
    dow.to_csv(out3, index=False)
    print(f"[+] {out3}")

    return monthly, dow


# ---------------------------------------------------------------------------
# Charts
# ---------------------------------------------------------------------------

def _save(fig, name, prefix=""):
    path = OUTPUT_DIR / f"{prefix}{name}"
    fig.savefig(path, dpi=150, bbox_inches="tight")
    plt.close(fig)
    print(f"[+] {path}")


def chart_monthly_flights_vs_flyable(df, monthly, prefix=""):
    fig, ax = plt.subplots(figsize=(14, 5))
    period = monthly["period"].values
    x = np.arange(len(period))
    w = 0.35
    ax.bar(x - w / 2, monthly["flight_count"].values, w, label="Flights", color=PALETTE[0])
    ax2 = ax.twinx()
    ax2.bar(x + w / 2, monthly["flyable_days"].values, w, label="Flyable days", color=PALETTE[1], alpha=0.7)

    skip = max(1, len(period) // 12)
    ax.set_xticks(x[::skip])
    ax.set_xticklabels(period[::skip], rotation=45, ha="right", fontsize=7)
    ax.set_ylabel("Flights", color=PALETTE[0])
    ax2.set_ylabel("Flyable days", color=PALETTE[1])
    fig.suptitle("Monthly flights vs flyable-weather days", fontsize=13)
    lines1, labs1 = ax.get_legend_handles_labels()
    lines2, labs2 = ax2.get_legend_handles_labels()
    ax.legend(lines1 + lines2, labs1 + labs2, loc="upper left")
    fig.tight_layout()
    _save(fig, "monthly_flights_vs_flyable_days.png", prefix)


def chart_dayofweek_weather_impact(df, dow, prefix=""):
    fig, ax1 = plt.subplots(figsize=(8, 5))
    labels = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"]
    x = np.arange(7)
    w = 0.35
    ax1.bar(x - w / 2, dow["avg_flights"].values, w, label="Avg flights", color=PALETTE[0])
    ax1.set_xticks(x)
    ax1.set_xticklabels(labels)
    ax1.set_ylabel("Avg flights", color=PALETTE[0])

    ax2 = ax1.twinx()
    pct = dow["flyable_pct"].values * 100
    ax2.scatter(x + w / 2, pct, color=PALETTE[1], s=60, zorder=5, label="% flyable")
    ax2.plot(x + w / 2, pct, color=PALETTE[1], alpha=0.4, linestyle="--")
    ax2.set_ylabel("% flyable days", color=PALETTE[1])

    fig.suptitle("Average flights by day of week vs weather", fontsize=13)
    lines1, labs1 = ax1.get_legend_handles_labels()
    lines2, labs2 = ax2.get_legend_handles_labels()
    ax1.legend(lines1 + lines2, labs1 + labs2, loc="upper left")
    fig.tight_layout()
    _save(fig, "dayofweek_weather_impact.png", prefix)


def chart_flights_vs_precipitation_and_wind(df, prefix=""):
    fig, axes = plt.subplots(1, 2, figsize=(12, 5))
    ax1, ax2 = axes

    # Precipitation vs flights
    ax1.scatter(df["precipitation_mm"], df["flight_count"], c=df["wind_max_kmh"],
                cmap="YlOrRd", alpha=0.5, s=8, edgecolors="none")
    ax1.set_xlabel("Precipitation (mm)")
    ax1.set_ylabel("Flight count")
    ax1.axvline(PRECIP_THRESHOLD_MM, color="blue", linestyle="--", alpha=0.5, label=f"threshold ({PRECIP_THRESHOLD_MM}mm)")
    ax1.legend(fontsize=8)
    cbar = plt.colorbar(ax1.collections[0], ax=ax1, label="Wind max km/h")

    # Wind vs flights
    ax2.scatter(df["wind_max_kmh"], df["flight_count"], c=df["precipitation_mm"],
                cmap="Blues", alpha=0.5, s=8, edgecolors="none")
    ax2.set_xlabel("Wind max (km/h)")
    ax2.set_ylabel("Flight count")
    ax2.axvline(WIND_THRESHOLD_KMH, color="red", linestyle="--", alpha=0.5, label="threshold (30 kt)")
    ax2.legend(fontsize=8)
    plt.colorbar(ax2.collections[0], ax=ax2, label="Precip mm")

    fig.suptitle("Flights vs precipitation and wind (coloured by the other)", fontsize=13)
    fig.tight_layout()
    _save(fig, "flights_vs_precipitation_and_wind.png", prefix)


def chart_weekend_missed_days(df, prefix=""):
    we = df[df["is_weekend"]].copy()
    we["ym"] = we["year"].astype(str) + "-" + we["month"].apply(lambda m: f"{m:02d}")
    grouped = we.groupby("ym").agg(
        total_weekend_days=("date", "count"),
        missed=("missed_opportunity", "sum"),
    ).reset_index()

    fig, ax = plt.subplots(figsize=(14, 5))
    x = np.arange(len(grouped))
    w = 0.35
    ax.bar(x - w / 2, grouped["total_weekend_days"].values, w, label="Weekend days", color=PALETTE[2], alpha=0.5)
    ax.bar(x + w / 2, grouped["missed"].values, w, label="Missed (flyable, no flights)", color=PALETTE[1])
    skip = max(1, len(grouped) // 12)
    ax.set_xticks(x[::skip])
    ax.set_xticklabels(grouped["ym"].values[::skip], rotation=45, ha="right", fontsize=7)
    ax.set_ylabel("Days")
    ax.legend()
    fig.suptitle("Weekend missed days by month (flyable weather but no flying)", fontsize=13)
    fig.tight_layout()
    _save(fig, "missed_days_by_month.png", prefix)


def chart_year_heatmap(df, prefix=""):
    df["ym"] = df["year"].astype(str) + "-" + df["month"].apply(lambda m: f"{m:02d}")
    pivot = df.pivot_table(index="year", columns="month", values="flight_count", aggfunc="sum")
    pivot = pivot.sort_index(ascending=False)
    month_names = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]
    pivot = pivot.reindex(columns=range(1, 13))

    fig, ax = plt.subplots(figsize=(12, 8))
    im = ax.imshow(pivot.values, cmap="YlGnBu", aspect="auto", interpolation="nearest")
    ax.set_xticks(range(12))
    ax.set_xticklabels(month_names)
    ax.set_yticks(range(len(pivot)))
    ax.set_yticklabels(pivot.index.astype(int))
    ax.set_xlabel("Month")
    ax.set_ylabel("Year")

    for i in range(len(pivot)):
        for j in range(12):
            v = pivot.iloc[i, j]
            if pd.notna(v):
                ax.text(j, i, f"{int(v):,}", ha="center", va="center", fontsize=7, color="black" if v < pivot.values.max() / 2 else "white")

    fig.suptitle("Year-Month flight count heatmap", fontsize=13)
    plt.colorbar(im, ax=ax, label="Flights", shrink=0.6)
    fig.tight_layout()
    _save(fig, "year_heatmap.png", prefix)


def chart_annual_utilisation(yearly, prefix=""):
    fig, ax = plt.subplots(figsize=(10, 5))
    years = yearly.index.astype(int).values
    labels = [f"{s}/{s+1}" for s in years]
    x = np.arange(len(years))
    w = 0.25
    ax.bar(x - w, yearly["avg"].values, w, label="Mean", color=PALETTE[0])
    ax.bar(x, yearly["median"].values, w, label="Median", color=PALETTE[1])
    ax.bar(x + w, yearly["p90"].values, w, label="P90", color=PALETTE[2])
    ax.set_xticks(x)
    ax.set_xticklabels(labels, fontsize=8)
    ax.set_xlabel("Season")
    ax.set_ylabel("Flights per flying day")
    ax.legend()
    fig.suptitle("Annual utilisation (flights per flying day)", fontsize=13)
    fig.tight_layout()
    _save(fig, "annual_utilisation.png", prefix)


def chart_weekend_weather_vs_nofly(df, prefix=""):
    we = df[df["is_weekend"]].copy()
    if we.empty:
        return
    we["season"] = we["season"].astype(int)
    grouped = we.groupby("season").agg(
        bad_weather=("flyable", lambda x: (~x.values).sum()),
        no_flights=("missed_opportunity", "sum"),
    ).reset_index()
    labels = [f"{s}/{s+1}" for s in grouped["season"].values]

    fig, ax = plt.subplots(figsize=(10, 5))
    x = np.arange(len(grouped))
    w = 0.35
    ax.bar(x - w / 2, grouped["bad_weather"].values, w, label="Bad weather weekends", color="#888888")
    ax.bar(x + w / 2, grouped["no_flights"].values, w, label="No flights (weather fine)", color=PALETTE[1])
    ax.set_xticks(x)
    ax.set_xticklabels(labels, fontsize=8)
    ax.set_xlabel("Season")
    ax.set_ylabel("Weekend days")
    ax.legend()
    fig.suptitle("Weekend days: bad weather vs no flights", fontsize=13)
    fig.tight_layout()
    _save(fig, "weekend_badweather_vs_nofly.png", prefix)


def chart_flights_vs_flying_days_ratio(df, prefix=""):
    df["season"] = df["season"].astype(int)
    grouped = df.groupby("season").agg(
        total_flights=("flight_count", "sum"),
        flying_days=("had_flights", "sum"),
        no_fly_days=("had_flights", lambda x: (~x.values).sum()),
    ).reset_index()
    grouped["flights_per_flying_day"] = (grouped["total_flights"] / grouped["flying_days"]).round(1)
    grouped["flights_per_nofly_day"] = (grouped["total_flights"] / grouped["no_fly_days"]).round(1)
    labels = [f"{s}/{s+1}" for s in grouped["season"].values]

    fig, ax = plt.subplots(figsize=(10, 5))
    x = np.arange(len(grouped))
    ax.plot(x, grouped["flights_per_flying_day"].values, "o-", color=PALETTE[0], linewidth=2, markersize=6, label="Flights per flying day")
    ax.plot(x, grouped["flights_per_nofly_day"].values, "s--", color=PALETTE[1], linewidth=2, markersize=6, label="Flights per no-fly day")
    ax.set_xticks(x)
    ax.set_xticklabels(labels, fontsize=8)
    ax.set_xlabel("Season")
    ax.set_ylabel("Flights per day")
    ax.legend()
    for i, v in enumerate(grouped["flights_per_flying_day"].values):
        ax.text(i, v + 0.3, str(v), ha="center", va="bottom", fontsize=7, color=PALETTE[0])
    for i, v in enumerate(grouped["flights_per_nofly_day"].values):
        ax.text(i, v - 0.5, str(v), ha="center", va="top", fontsize=7, color=PALETTE[1])

    fig.suptitle("Flights per flying day vs flights per no-fly day", fontsize=13)
    fig.tight_layout()
    _save(fig, "flights_vs_nofly_ratio.png", prefix)


def chart_distinct_people_per_flying_day(df, prefix=""):
    flying = df[df["had_flights"]].copy()
    if flying.empty:
        return
    flying["season"] = flying["season"].astype(int)
    grouped = flying.groupby("season")["distinct_people"].agg(["mean", "median", lambda x: x.quantile(0.9)])
    grouped.columns = ["avg", "median", "p90"]
    grouped = grouped.round(1)
    labels = [f"{s}/{s+1}" for s in grouped.index]

    fig, ax = plt.subplots(figsize=(10, 5))
    x = np.arange(len(grouped))
    ax.plot(x, grouped["avg"].values, "o-", color=PALETTE[0], linewidth=2, markersize=6, label="Mean")
    ax.plot(x, grouped["median"].values, "s--", color=PALETTE[1], linewidth=2, markersize=6, label="Median")
    ax.plot(x, grouped["p90"].values, "^:", color=PALETTE[2], linewidth=2, markersize=6, label="P90")
    ax.set_xticks(x)
    ax.set_xticklabels(labels, fontsize=8)
    ax.set_xlabel("Season")
    ax.set_ylabel("Distinct people")
    ax.legend()
    for i, v in enumerate(grouped["avg"].values):
        ax.text(i, v + 0.3, str(v), ha="center", va="bottom", fontsize=7, color=PALETTE[0])
    fig.suptitle("Distinct people per flying day", fontsize=13)
    fig.tight_layout()
    _save(fig, "distinct_people_per_flying_day.png", prefix)


def chart_wasted_days(df, prefix=""):
    df["season"] = df["season"].astype(int)
    grouped = df.groupby("season").agg(
        wasted=("missed_opportunity", "sum"),
        flyable=("flyable", "sum"),
    ).reset_index()
    grouped["used_pct"] = ((grouped["flyable"] - grouped["wasted"]) / grouped["flyable"] * 100).round(1)
    labels = [f"{s}/{s+1}" for s in grouped["season"].values]

    fig, ax = plt.subplots(figsize=(10, 5))
    x = np.arange(len(grouped))
    colors = [PALETTE[1] if w > 0 else "#888888" for w in grouped["wasted"].values]
    ax.bar(x, grouped["wasted"].values, 0.6, color=colors, edgecolor="#333", linewidth=0.5)
    ax.set_xticks(x)
    ax.set_xticklabels(labels, fontsize=8)
    ax.set_xlabel("Season")
    ax.set_ylabel("Wasted days")
    for i, v in enumerate(grouped["wasted"].values):
        if v > 0:
            ax.text(i, v + 1, str(int(v)), ha="center", va="bottom", fontsize=7)
    for i, pct in enumerate(grouped["used_pct"].values):
        ax.text(i, -3, f"{pct}%", ha="center", va="top", fontsize=6, color="#555")
    ax.set_ylim(bottom=-8)
    fig.suptitle("Wasted days (good weather, no flying)", fontsize=13)
    fig.tight_layout()
    _save(fig, "wasted_days.png", prefix)


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

def main():
    parser = argparse.ArgumentParser(description="Weather-Flight correlation analysis")
    parser.add_argument("--port", type=int, default=33060, help="MySQL port (default 33060 for Vagrant)")
    parser.add_argument("--start", default=START_DATE, help=f"Start date (default {START_DATE})")
    parser.add_argument("--no-fetch", action="store_true", help="Skip weather fetch, use cache only")
    parser.add_argument("--db-user", help="MySQL username (overrides config/database.php)")
    parser.add_argument("--db-password", help="MySQL password (overrides config/database.php)")
    args = parser.parse_args()

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

    # 1. Weather
    weather = already_cached_weather()
    if weather is None or not args.no_fetch:
        today = date.today().isoformat()
        weather = fetch_weather_from_api(args.start, today)
        CACHE_FILE.write_text(json.dumps(weather, indent=2), encoding="utf-8")
        print(f"[+] Cached weather to {CACHE_FILE}")
    elif args.no_fetch and weather is not None:
        print("[*] Using cached weather (--no-fetch)")

    # 2. Flights
    cfg = load_db_config()
    if args.db_user:
        cfg["username"] = args.db_user
    if args.db_password:
        cfg["password"] = args.db_password
    flights = query_flights_from_db(cfg, args.port)

    # 3. Merge & analyse
    df = merge_and_analyze(flights, weather, args.start)

    # 3b. Filter to weekends only
    prefix = ""
    df = df[df["is_weekend"]].copy()
    print(f"[*] Filtered to weekends: {len(df)} days")

    # 4. Annual utilisation
    flying = df[df["had_flights"]].copy()
    yearly = None
    if not flying.empty:
        flying["season"] = flying["season"].astype(int)
        yearly = flying.groupby("season")["flight_count"].agg(["mean", "median", lambda x: x.quantile(0.9)])
        yearly.columns = ["avg", "median", "p90"]
        yearly = yearly.round(1)

    # 5. Export CSVs
    monthly, dow = export_csvs(df, prefix)

    # 6. Charts
    print("[*] Generating charts...")
    chart_monthly_flights_vs_flyable(df, monthly, prefix)
    chart_flights_vs_precipitation_and_wind(df, prefix)
    chart_weekend_missed_days(df, prefix)
    chart_year_heatmap(df, prefix)
    chart_weekend_weather_vs_nofly(df, prefix)
    chart_flights_vs_flying_days_ratio(df, prefix)
    chart_wasted_days(df, prefix)
    chart_distinct_people_per_flying_day(df, prefix)
    if yearly is not None:
        chart_annual_utilisation(yearly, prefix)

    # 6. Summary stats
    total = len(df)
    flight_days = df["had_flights"].sum()
    flyable_days = df["flyable"].sum()
    missed = df["missed_opportunity"].sum()
    total_flights = int(df["flight_count"].sum())
    flt_per_day = total_flights / flight_days if flight_days else 0
    print()
    print("=" * 55)
    print(f"  Period:                 {args.start} to {date.today()}")
    print(f"  Total days:             {total}")
    print(f"  Days with flying:       {flight_days} ({flight_days / total * 100:.1f}%)")
    print(f"  Flyable days:           {flyable_days} ({flyable_days / total * 100:.1f}%)")
    print(f"  Missed opps:            {missed} ({missed / total * 100:.1f}%)")
    print(f"  Total flights:          {total_flights}")
    print(f"  Flights per flying day: {flt_per_day:.1f}")
    print()

    # 7. Annual utilisation table
    if yearly is not None:
        print("  Annual utilisation (flights per flying day)")
        print(f"  {'Season':>7}  {'Avg':>6}  {'Median':>6}  {'P90':>6}")
        print(f"  {'-'*7}  {'-'*6}  {'-'*6}  {'-'*6}")
        for yr, row in yearly.iterrows():
            label = f"{int(yr)}/{int(yr)+1}"
            print(f"  {label:>7}  {row['avg']:>6.1f}  {row['median']:>6.1f}  {row['p90']:>6.1f}")

    # 8. Distinct people per flying day table
    yearly_people = None
    if not flying.empty:
        yearly_people = flying.groupby("season")["distinct_people"].agg(["mean", "median", lambda x: x.quantile(0.9)])
        yearly_people.columns = ["avg", "median", "p90"]
        yearly_people = yearly_people.round(1)
    if yearly_people is not None:
        print()
        print("  Distinct people per flying day")
        print(f"  {'Season':>7}  {'Avg':>6}  {'Median':>6}  {'P90':>6}")
        print(f"  {'-'*7}  {'-'*6}  {'-'*6}  {'-'*6}")
        for yr, row in yearly_people.iterrows():
            label = f"{int(yr)}/{int(yr)+1}"
            print(f"  {label:>7}  {row['avg']:>6.1f}  {row['median']:>6.1f}  {row['p90']:>6.1f}")
    print("=" * 55)
    print(f"\nAll output in {OUTPUT_DIR}/")


if __name__ == "__main__":
    main()
